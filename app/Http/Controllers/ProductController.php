<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Design;
use App\Models\DesignExport;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductExtra;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\Application_types;
use App\Models\MaterialVariant;
use App\Models\Material;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'category' => ['nullable', 'integer', 'min:1'],
                'status' => ['nullable', 'string', 'in:active,draft,discontinued'],
                'search' => ['nullable', 'string', 'max:100'],
            ]);

            $query = Product::with([
                'category',
                'extras',
                'variants.attributes.attribute',
                'variants.designExports',
                'designs',
            ])->orderBy('created_at', 'desc');

            // Filtro por categoría
            if (!empty($validated['category'])) {
                $query->where('product_category_id', (int) $validated['category']);
            }

            // Filtro por estado
            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            // Búsqueda
            if (!empty($validated['search'])) {
                $search = strip_tags(trim($validated['search']));
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $products = $query->paginate(10)->withQueryString();

            $categories = ProductCategory::active()->ordered()->get();

            return view('admin.products.index', compact('products', 'categories'));
        } catch (\Exception $e) {
            Log::error('Error al listar productos: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
            ]);

            return view('admin.products.index', [
                'products' => collect(),
                'categories' => collect(),
            ])->with('error', 'Error al cargar los productos');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        try {
            // 1. Cargar categorías (sin bloquear si están vacías)
            $categories = ProductCategory::active()->ordered()->get();

            // 2. Carga de Insumos y Configuración
            $extras = ProductExtra::ordered()->get();

            // STRICT FILTERING: Only load designs/variants with APPROVED production files
            $designs = Design::where(function ($q) {
                // Design has direct approved exports (Global)
                $q->whereHas('generalExports', fn($q2) => $q2->where('status', 'aprobado'))
                    // OR Design has variants with approved exports (Specific)
                    ->orWhereHas('variants.exports', fn($q2) => $q2->where('status', 'aprobado'));
            })
                ->with([
                    'categories',
                    'primaryImage',
                    'generalExports' => fn($q) => $q->where('status', 'aprobado'),
                    // Only load variants that have approved exports
                    'variants' => fn($q) => $q->whereHas('exports', fn($q2) => $q2->where('status', 'aprobado'))
                        ->with(['primaryImage', 'exports' => fn($q2) => $q2->where('status', 'aprobado')])
                ])
                ->orderBy('name')
                ->get();
            $applicationTypes = Application_types::where('activo', true)->orderBy('nombre_aplicacion')->get();

            // NEW: Flatten all approved DesignExports for the new UI
            // This creates a single-level collection of all production files
            $designExports = DesignExport::where('status', 'aprobado')
                ->with(['design.primaryImage', 'variant.primaryImage', 'image'])
                ->orderBy('design_id')
                ->orderBy('design_variant_id')
                ->orderBy('width_mm')
                ->get()
                ->map(function ($export) {
                    // Determine the display name and image
                    $displayName = $export->design->name;
                    $variantName = null;
                    $imageUrl = null;

                    // Check for variant
                    if ($export->variant) {
                        $variantName = $export->variant->name;
                        $displayName .= ' - ' . $variantName;
                        // Variant image priority - use display_url accessor (returns full URL)
                        $imageUrl = $export->variant->primaryImage?->display_url;
                    }

                    // Fallback to design image
                    if (!$imageUrl) {
                        $imageUrl = $export->design->primaryImage?->display_url;
                    }

                    // Export-specific image (highest priority)
                    if ($export->image) {
                        $imageUrl = $export->image->display_url;
                    }

                    return [
                        'id' => $export->id,
                        'design_id' => $export->design_id,
                        'design_name' => $export->design->name,
                        'variant_id' => $export->design_variant_id,
                        'variant_name' => $variantName,
                        'display_name' => $displayName,
                        'dimensions' => $export->width_mm . 'x' . $export->height_mm,
                        'dimensions_label' => $export->width_mm . 'x' . $export->height_mm . ' mm',
                        'width_mm' => $export->width_mm,
                        'height_mm' => $export->height_mm,
                        'stitches' => $export->stitches_count ?? 0,
                        'stitches_formatted' => number_format($export->stitches_count ?? 0),
                        'colors' => $export->colors_count ?? 0,
                        'application_type' => $export->application_type ?? 'general',
                        'application_label' => $export->application_label ?? ucfirst($export->application_type ?? 'General'),
                        'image_url' => $imageUrl,
                        'svg_content' => $export->svg_content, // SVG fallback for preview
                    ];
                });

            // 3. Atributos
            $sizeAttribute = Attribute::with('values')->where('slug', 'talla')->first();
            $colorAttribute = Attribute::with('values')->where('slug', 'color')->first();

            // 4. NUEVO: Materiales para la Fase 3 (Optimizado)
            // Solo traemos lo estrictamente necesario para el selector
            /*  $materials = MaterialVariant::with(['material.category.baseUnit']) 
                ->where('activo', true)
                ->get()
                ->map(function ($variant) {
                    return [
                        'id'            => $variant->id,
                        'text'          => $variant->sku . ' - ' . ($variant->color ?? 'Sin Color'),
                        'full_name'     => $variant->display_name, // Suponiendo que tienes este accessor
                        'stock'         => (float) $variant->current_stock,
                        'unit_symbol'   => $variant->material->category->baseUnit->symbol ?? 'unid',
                        'average_cost'  => (float) $variant->average_cost,
                    ];
                });*/

            // 4. Materiales (Familias) - Esto alimenta el primer Select
            $materials = Material::with('baseUnit')  // baseUnit está en material, no en category
                ->where('activo', true)
                ->orderBy('name')
                ->get();



            return view('admin.products.create', compact(
                'categories',
                'extras',
                'designs',
                'designExports', // NEW: Flattened exports for new UI
                'applicationTypes',
                'sizeAttribute',
                'colorAttribute',
                'materials'
            ));
        } catch (\Exception $e) {
            Log::error("Product Create Error [Line {$e->getLine()}]: " . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.products.index')
                ->with('error', 'Error interno al cargar el formulario de creación.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(StoreProductRequest $request)
    {
        try {
            Log::info('Attempting to store product', ['data' => $request->all()]);
            Log::info('Designs Payload:', ['d' => $request->input('embroideries_json')]);
            $validated = $request->validated();

            $product = $this->productService->createProduct($validated);

            return redirect()->route('admin.products.show', $product->id)
                ->with('success', "Producto '{$product->name}' creado exitosamente");
        } catch (\Exception $e) {
            Log::error('Error al crear producto: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.products.create')
                ->withInput()
                ->with('error', 'Error al crear el producto: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE DRAFT (AJAX)
    |--------------------------------------------------------------------------
    */
    public function storeDraft(Request $request)
    {
        try {
            // Log::info('Saving Draft', $request->all());

            // 1. Relajar validación para borrador
            // Aceptamos datos parciales, pero necesitamos al menos los básicos para crear el registro
            $validated = $request->validate([
                'name' => 'required|string|min:3',
                // Si no hay SKU, podríamos generar uno temporal, pero Step 1 lo pide.
                // Lo hacemos 'nullable' y generamos si falta.
                'sku' => 'nullable|string',
                'product_category_id' => 'nullable|integer',
                // JSONs
                'materials_json' => 'nullable|json',
                'extras_json' => 'nullable|json',
                'embroideries_json' => 'nullable|json',
                'variants_json' => 'nullable|json',
                'financials_json' => 'nullable|json',
            ]);

            // 2. Preparar datos para el Service (similar a StoreProductRequest::prepareForValidation)
            // Decodificar JSONs manualmente aquí porque no usamos el FormRequest
            $data = [
                'name' => $validated['name'],
                'product_category_id' => $validated['product_category_id'] ?? 1, // Default a 1 o null
                'sku' => $validated['sku'] ?? ('DRAFT-' . time()),
                'description' => $request->input('description', ''),
                'status' => 'draft',
            ];

            // Materials
            if (!empty($validated['materials_json'])) {
                $bomData = json_decode($validated['materials_json'], true);
                if (is_array($bomData)) {
                    $data['materials'] = array_map(function ($item) {
                        return [
                            'material_variant_id' => $item['id'] ?? null,
                            'quantity' => $item['qty'] ?? 0,
                            'is_primary' => $item['is_primary'] ?? false,
                            'notes' => $item['notes'] ?? null,
                            'scope' => $item['scope'] ?? 'global',
                            'targets' => $item['targets'] ?? [],
                        ];
                    }, $bomData);
                }
            }

            // Extras
            if (!empty($validated['extras_json'])) {
                $extrasData = json_decode($validated['extras_json'], true);
                if (is_array($extrasData)) {
                    $data['extras'] = array_column($extrasData, 'id');
                }
            }

            // Designs
            if (!empty($validated['embroideries_json'])) {
                $designsData = json_decode($validated['embroideries_json'], true);
                if (is_array($designsData)) {
                    $designIds = [];
                    $applications = [];
                    foreach ($designsData as $d) {
                        if (isset($d['id'])) {
                            $designIds[] = $d['id'];
                            if (isset($d['position_id'])) {
                                $applications[$d['id']] = $d['position_id'];
                            }
                        }
                    }
                    $data['designs'] = $designIds;
                    $data['design_applications'] = $applications;
                }
            }

            // Variants
            if (!empty($validated['variants_json'])) {
                $data['variants'] = json_decode($validated['variants_json'], true);
            }

            // Financials
            if (!empty($validated['financials_json'])) {
                $finData = json_decode($validated['financials_json'], true);
                if (is_array($finData)) {
                    $data['base_price'] = $finData['price'] ?? 0;
                    $data['production_cost'] = $finData['total_cost'] ?? 0;
                    $data['profit_margin'] = $finData['margin'] ?? 0;
                }
            }

            // 3. Crear Producto usando el Service
            // Nota: ProductService::createProduct podría esperar arrays limpios.
            // Aseguramos que 'status' => 'draft' sea respetado si el service lo permite.
            // Si el service fuerza validaciones extra, podríamos necesitar un método createDraft específico.
            // Por ahora intentamos usar createProduct asumiendo que es flexible o fallará controladamente.

            $product = $this->productService->createProduct($data);

            return response()->json([
                'success' => true,
                'message' => 'Borrador guardado exitosamente',
                'product_id' => $product->id,
                'redirect_url' => route('admin.products.edit', $product->id) // Para que el usuario pueda retomarlo
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving draft: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar borrador: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return redirect()->route('admin.products.index')
                    ->with('error', 'Producto no válido');
            }

            $product = Product::with([
                'category',
                'extras',
                'designs.category',
                'variants.attributes.attribute',
                'variants.designExports.designVariant',
                'images',
            ])->findOrFail((int) $id);

            return view('admin.products.show', compact('product'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Producto no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al mostrar producto: ' . $e->getMessage(), [
                'product_id' => $id,
            ]);

            return redirect()->route('admin.products.index')
                ->with('error', 'Error al cargar el producto');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return redirect()->route('admin.products.index')
                    ->with('error', 'Producto no válido');
            }

            $product = Product::with([
                'category',
                'extras',
                'designs',
                'variants.attributes',
            ])->findOrFail((int) $id);

            $categories = ProductCategory::active()->ordered()->get();
            $extras = ProductExtra::ordered()->get();
            $designs = Design::with('categories')->orderBy('name')->get();
            $applicationTypes = Application_types::where('activo', true)->orderBy('nombre_aplicacion')->get();
            $attributes = Attribute::with('values')->orderBy('name')->get();

            return view('admin.products.edit', compact(
                'product',
                'categories',
                'extras',
                'designs',
                'applicationTypes',
                'attributes'
            ));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Producto no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al cargar producto para editar: ' . $e->getMessage(), [
                'product_id' => $id,
            ]);

            return redirect()->route('admin.products.index')
                ->with('error', 'Error al cargar el producto');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(UpdateProductRequest $request, $id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return redirect()->route('admin.products.index')
                    ->with('error', 'Producto no válido');
            }

            $product = Product::findOrFail((int) $id);
            $validated = $request->validated();

            $product = $this->productService->updateProduct($product, $validated);

            return redirect()->route('admin.products.show', $product->id)
                ->with('success', "Producto '{$product->name}' actualizado exitosamente");
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Producto no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al actualizar producto: ' . $e->getMessage(), [
                'product_id' => $id,
            ]);

            return redirect()->route('admin.products.edit', $id)
                ->withInput()
                ->with('error', 'Error al actualizar el producto');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRM DELETE
    |--------------------------------------------------------------------------
    */

    public function confirmDelete($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return redirect()->route('admin.products.index')
                    ->with('error', 'Producto no válido');
            }

            $product = Product::with(['category', 'variants', 'designs', 'extras'])
                ->findOrFail((int) $id);

            return view('admin.products.delete', compact('product'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Producto no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al cargar confirmación de eliminación: ' . $e->getMessage());

            return redirect()->route('admin.products.index')
                ->with('error', 'Error al procesar la solicitud');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return redirect()->route('admin.products.index')
                    ->with('error', 'Producto no válido');
            }

            $product = Product::findOrFail((int) $id);
            $productName = $product->name;

            $this->productService->deleteProduct($product);

            return redirect()->route('admin.products.index')
                ->with('success', "Producto '{$productName}' eliminado exitosamente");
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Producto no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al eliminar producto: ' . $e->getMessage(), [
                'product_id' => $id,
            ]);

            return redirect()->route('admin.products.index')
                ->with('error', 'Error al eliminar el producto');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DUPLICATE
    |--------------------------------------------------------------------------
    */

    public function duplicate($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return redirect()->route('admin.products.index')
                    ->with('error', 'Producto no válido');
            }

            $product = Product::findOrFail((int) $id);
            $newProduct = $this->productService->duplicateProduct($product);

            return redirect()->route('admin.products.edit', $newProduct->id)
                ->with('success', "Producto duplicado exitosamente. Modifique los datos necesarios.");
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Producto no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al duplicar producto: ' . $e->getMessage(), [
                'product_id' => $id,
            ]);

            return redirect()->route('admin.products.index')
                ->with('error', 'Error al duplicar el producto');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TOGGLE STATUS
    |--------------------------------------------------------------------------
    */

    public function toggleStatus($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return redirect()->back()->with('error', 'Producto no válido');
            }

            $product = Product::findOrFail((int) $id);

            if ($product->status === 'active') {
                $product->discontinue();
                $message = "Producto '{$product->name}' marcado como descontinuado";
            } else {
                $product->activate();
                $message = "Producto '{$product->name}' activado exitosamente";
            }

            return redirect()->back()->with('success', $message);
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Producto no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del producto: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VARIANTES
    |--------------------------------------------------------------------------
    */

    public function createVariant($productId)
    {
        try {
            $product = Product::with(['category', 'designs'])->findOrFail((int) $productId);
            $attributes = Attribute::with('values')->orderBy('name')->get();
            $applicationTypes = Application_types::where('activo', true)->orderBy('nombre_aplicacion')->get();

            // Obtener design exports de los diseños asignados
            $designExports = DesignExport::whereIn('design_id', $product->designs->pluck('id'))
                ->orWhereHas('designVariant', function ($q) use ($product) {
                    $q->whereIn('design_id', $product->designs->pluck('id'));
                })
                ->with(['design', 'designVariant'])
                ->get();

            return view('admin.products.variants.create', compact(
                'product',
                'attributes',
                'applicationTypes',
                'designExports'
            ));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Producto no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de variante: ' . $e->getMessage());
            return redirect()->route('admin.products.show', $productId)
                ->with('error', 'Error al cargar el formulario');
        }
    }

    public function storeVariant(Request $request, $productId)
    {
        try {
            $product = Product::findOrFail((int) $productId);

            $validated = $request->validate([
                'sku_variant' => [
                    'required',
                    'string',
                    'max:100',
                    'regex:/^[A-Z0-9\-\_]+$/u',
                    'unique:product_variants,sku_variant',
                ],
                'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
                'stock_alert' => ['nullable', 'integer', 'min:0', 'max:9999'],
                'attribute_values' => ['nullable', 'array'],
                'design_exports' => ['nullable', 'array'],
            ]);

            $variant = $this->productService->createVariant($product, $validated);

            return redirect()->route('admin.products.show', $product->id)
                ->with('success', "Variante '{$variant->sku_variant}' creada exitosamente");
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Producto no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al crear variante: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al crear la variante');
        }
    }

    public function editVariant($productId, $variantId)
    {
        try {
            $product = Product::findOrFail((int) $productId);
            $variant = ProductVariant::with(['attributes.attribute', 'designExports'])
                ->where('product_id', $product->id)
                ->findOrFail((int) $variantId);

            $attributes = Attribute::with('values')->orderBy('name')->get();
            $applicationTypes = Application_types::where('activo', true)->orderBy('nombre_aplicacion')->get();

            $designExports = DesignExport::whereIn('design_id', $product->designs->pluck('id'))
                ->orWhereHas('designVariant', function ($q) use ($product) {
                    $q->whereIn('design_id', $product->designs->pluck('id'));
                })
                ->with(['design', 'designVariant'])
                ->get();

            return view('admin.products.variants.edit', compact(
                'product',
                'variant',
                'attributes',
                'applicationTypes',
                'designExports'
            ));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Producto o variante no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al cargar variante para editar: ' . $e->getMessage());
            return redirect()->route('admin.products.show', $productId)
                ->with('error', 'Error al cargar la variante');
        }
    }

    public function updateVariant(Request $request, $productId, $variantId)
    {
        try {
            $product = Product::findOrFail((int) $productId);
            $variant = ProductVariant::where('product_id', $product->id)
                ->findOrFail((int) $variantId);

            $validated = $request->validate([
                'sku_variant' => [
                    'required',
                    'string',
                    'max:100',
                    'regex:/^[A-Z0-9\-\_]+$/u',
                    "unique:product_variants,sku_variant,{$variantId}",
                ],
                'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
                'stock_alert' => ['nullable', 'integer', 'min:0', 'max:9999'],
                'attribute_values' => ['nullable', 'array'],
                'design_exports' => ['nullable', 'array'],
            ]);

            $variant = $this->productService->updateVariant($variant, $validated);

            return redirect()->route('admin.products.show', $product->id)
                ->with('success', "Variante '{$variant->sku_variant}' actualizada exitosamente");
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Producto o variante no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al actualizar variante: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al actualizar la variante');
        }
    }

    public function destroyVariant($productId, $variantId)
    {
        try {
            $product = Product::findOrFail((int) $productId);
            $variant = ProductVariant::where('product_id', $product->id)
                ->findOrFail((int) $variantId);

            $skuVariant = $variant->sku_variant;
            $this->productService->deleteVariant($variant);

            return redirect()->route('admin.products.show', $product->id)
                ->with('success', "Variante '{$skuVariant}' eliminada exitosamente");
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Producto o variante no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al eliminar variante: ' . $e->getMessage());
            return redirect()->route('admin.products.show', $productId)
                ->with('error', 'Error al eliminar la variante');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX ENDPOINTS
    |--------------------------------------------------------------------------
    */

    public function getDesignsByCategory($categoryId)
    {
        try {
            $designs = Design::whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', (int) $categoryId);
            })->orderBy('name')->get(['id', 'name', 'code', 'stitch_count']);

            return response()->json($designs);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener diseños'], 500);
        }
    }

    public function getDesignExports($designId)
    {
        try {
            $exports = DesignExport::where('design_id', (int) $designId)
                ->orWhereHas('designVariant', function ($q) use ($designId) {
                    $q->where('design_id', (int) $designId);
                })
                ->with(['design', 'designVariant', 'format'])
                ->get();

            return response()->json($exports);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener exportaciones'], 500);
        }
    }

    public function getAttributes()
    {
        try {
            $attributes = Attribute::with('values')->orderBy('name')->get();
            return response()->json($attributes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener atributos'], 500);
        }
    }

    /**
     * Buscar materiales para el selector (API)
     */
    public function searchMaterials(Request $request)
    {
        $term = $request->get('q');

        $materials = MaterialVariant::with(['material.baseUnit'])  // baseUnit está en material
            ->whereHas('material', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%");
            })
            ->orWhere('sku', 'like', "%{$term}%")
            ->orWhere('color', 'like', "%{$term}%")
            ->limit(20)
            ->get();

        $results = $materials->map(function ($variant) {
            $unitSymbol = $variant->material->baseUnit->symbol ?? '';
            return [
                'id' => $variant->id,
                'text' => $variant->display_name . " (Stock: {$variant->current_stock} {$unitSymbol})",
                'sku' => $variant->sku,
                'unit' => $variant->material->baseUnit->name ?? 'Unidad',
                'symbol' => $unitSymbol,
                'cost' => $variant->average_cost > 0 ? $variant->average_cost : $variant->last_purchase_cost,
                'stock' => $variant->current_stock
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * Valida si los precios de los materiales han cambiado
     */
    public function validateMaterialPrices(Request $request)
    {
        try {
            $clientMaterials = $request->input('materials', []);
            $changes = [];
            $hasChanges = false;

            foreach ($clientMaterials as $m) {
                $variant = MaterialVariant::find($m['id']);
                if (!$variant) continue;

                // Determine current system cost
                $currentCost = $variant->average_cost > 0 ? $variant->average_cost : $variant->last_purchase_cost;
                $clientCost = (float) $m['price'];

                // Check for significant difference (avoid floating point issues)
                if (abs($currentCost - $clientCost) > 0.001) {
                    $changes[] = [
                        'id' => $variant->id,
                        'name' => $variant->display_name,
                        'old_price' => $clientCost,
                        'new_price' => $currentCost,
                        'diff' => $currentCost - $clientCost
                    ];
                    $hasChanges = true;
                }
            }

            return response()->json([
                'has_changes' => $hasChanges,
                'changes' => $changes
            ]);
        } catch (\Exception $e) {
            Log::error('Error al validar precios de materiales: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Error al validar precios'], 500);
        }
    }
}

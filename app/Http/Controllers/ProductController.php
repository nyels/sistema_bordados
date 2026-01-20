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
    protected \App\Services\ImageService $imageService;

    public function __construct(ProductService $productService, \App\Services\ImageService $imageService)
    {
        $this->productService = $productService;
        $this->imageService = $imageService;
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
                'variants.attributeValues.attribute',
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

    public function create(Request $request)
    {
        try {
            // === CLONE MODE: Detectar si viene de duplicar ===
            $cloneMode = false;
            $cloneProduct = null;
            if ($request->has('clone_from') && is_numeric($request->clone_from)) {
                $cloneProduct = Product::with([
                    'category',
                    'extras',
                    'designs.exports',
                    'designs.generalExports',
                    'variants.attributeValues.attribute',
                    'materials.material.baseUnit',
                    'images',
                    'primaryImage',
                ])->find((int) $request->clone_from);

                if ($cloneProduct) {
                    $cloneMode = true;
                }
            }

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
            $sizeAttribute = Attribute::with(['values' => fn($q) => $q->orderBy('value')])->where('slug', 'talla')->first();
            $colorAttribute = Attribute::with(['values' => fn($q) => $q->orderBy('value')])->where('slug', 'color')->first();

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
                'materials',
                'cloneMode',
                'cloneProduct'
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

            // Handle Image Upload
            if ($request->hasFile('primary_image')) {
                try {
                    $this->imageService->uploadImage(
                        $request->file('primary_image'),
                        Product::class,
                        $product->id,
                        ['is_primary' => true, 'alt_text' => $product->name]
                    );
                } catch (\Exception $e) {
                    Log::error('Error saving image for product ' . $product->id . ': ' . $e->getMessage());
                    // Don't fail the whole request, just log
                }
            }

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

            // Designs (Producciones de bordado)
            // Frontend envía export_id (ID de DesignExport), app_type_slug, scope, target_variant
            if (!empty($validated['embroideries_json'])) {
                $designsData = json_decode($validated['embroideries_json'], true);
                if (is_array($designsData)) {
                    // Nueva estructura: designs_list para ProductService::syncDesignsList
                    $data['designs_list'] = array_map(function ($d) {
                        return [
                            'export_id' => $d['export_id'] ?? $d['id'] ?? null,
                            'app_type_slug' => $d['app_type_slug'] ?? null,
                            'scope' => $d['scope'] ?? 'global',
                            'target_variant' => $d['target_variant'] ?? null,
                        ];
                    }, $designsData);
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
                'designs.categories',
                'variants.attributeValues.attribute',
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
                'designs.exports',
                'designs.generalExports',
                'variants.attributeValues.attribute',
                'materials.material.baseUnit',
                'images',
                'primaryImage',
            ])->findOrFail((int) $id);

            // Datos para el wizard (mismos que create)
            $categories = ProductCategory::active()->ordered()->get();
            $extras = ProductExtra::ordered()->get();
            $applicationTypes = Application_types::where('activo', true)->orderBy('nombre_aplicacion')->get();
            $attributes = Attribute::with('values')->orderBy('name')->get();

            // Materiales para selector (Paso 3)
            $materials = Material::with('baseUnit')
                ->where('activo', true)
                ->orderBy('name')
                ->get();

            // Atributos específicos para variantes
            $sizeAttribute = \App\Models\Attribute::with(['values' => fn($q) => $q->orderBy('value')])->where('slug', 'talla')->first();
            $colorAttribute = \App\Models\Attribute::with(['values' => fn($q) => $q->orderBy('value')])->where('slug', 'color')->first();

            // === PRECARGAR DATOS PARA EL STATE ===
            // Usamos la MISMA vista que create, con editMode=true
            return view('admin.products.create', [
                'editMode' => true,
                'product' => $product,
                'categories' => $categories,
                'extras' => $extras,
                'applicationTypes' => $applicationTypes,
                'attributes' => $attributes,
                'materials' => $materials,
                'sizeAttribute' => $sizeAttribute,
                'colorAttribute' => $colorAttribute,
            ]);
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

            // Handle Image Upload
            if ($request->hasFile('primary_image')) {
                try {
                    $this->imageService->uploadImage(
                        $request->file('primary_image'),
                        Product::class,
                        $product->id,
                        ['is_primary' => true, 'alt_text' => $product->name]
                    );
                } catch (\Exception $e) {
                    Log::error('Error updating image for product ' . $product->id . ': ' . $e->getMessage());
                }
            }

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

            // Verificar que el producto existe
            $product = Product::findOrFail((int) $id);

            // Redirect a CREATE con clone_from para precargar datos
            return redirect()->route('admin.products.create', ['clone_from' => $product->id])
                ->with('info', "Creando copia de '{$product->name}'. Modifique SKU y datos necesarios.");
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
            $variant = ProductVariant::with(['attributeValues.attribute', 'designExports'])
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

    /**
     * API JSON para obtener datos de variante (Modal AJAX)
     */
    public function getVariantJson($productId, $variantId)
    {
        try {
            $product = Product::findOrFail((int) $productId);
            $variant = ProductVariant::with(['attributeValues.attribute'])
                ->where('product_id', $product->id)
                ->findOrFail((int) $variantId);

            return response()->json([
                'success' => true,
                'variant' => [
                    'id' => $variant->id,
                    'sku_variant' => $variant->sku_variant,
                    'price' => (float) $variant->price,
                    'stock_alert' => (int) $variant->stock_alert,
                    'attributes_display' => $variant->attributes_display,
                    'product_base_price' => (float) $product->base_price,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Variante no encontrada'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al cargar variante'], 500);
        }
    }

    public function updateVariant(Request $request, $productId, $variantId)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

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

            if ($isAjax) {
                // Calculate extras total for frontend (to display final price)
                $extrasTotal = $product->extras->sum('price_addition');

                return response()->json([
                    'success' => true,
                    'message' => "Variante '{$variant->sku_variant}' actualizada",
                    'variant' => [
                        'id' => $variant->id,
                        'sku_variant' => $variant->sku_variant,
                        'price' => (float) $variant->price,
                        'stock_alert' => (int) $variant->stock_alert,
                        'attributes_display' => $variant->attributes_display,
                        'extras_total' => (float) $extrasTotal,
                    ],
                ]);
            }

            return redirect()->route('admin.products.show', $product->id)
                ->with('success', "Variante '{$variant->sku_variant}' actualizada exitosamente");
        } catch (ValidationException $e) {
            if ($isAjax) {
                return response()->json(['success' => false, 'errors' => $e->errors()], 422);
            }
            return redirect()->back()->withInput()->withErrors($e->errors());
        } catch (ModelNotFoundException $e) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'Variante no encontrada'], 404);
            }
            return redirect()->route('admin.products.index')
                ->with('error', 'Producto o variante no encontrado');
        } catch (\Exception $e) {
            Log::error('Error al actualizar variante: ' . $e->getMessage());
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'Error al actualizar'], 500);
            }
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
     * Obtener producciones de bordado aprobadas para el selector del wizard
     * Endpoint AJAX para cargar dinámicamente al abrir el modal
     */
    public function getApprovedDesignExports()
    {
        try {
            // Cargar tipos de aplicación para mapear slugs a nombres legibles
            $appTypes = Application_types::where('activo', true)
                ->pluck('nombre_aplicacion', 'slug')
                ->toArray();

            $exports = DesignExport::where('status', 'aprobado')
                ->with(['design.categories', 'variant', 'image'])
                ->orderBy('application_type')
                ->orderBy('application_label')
                ->get()
                ->map(function ($export) use ($appTypes) {
                    $variantName = $export->variant?->name;

                    // Imagen: SOLO el preview real del archivo de producción
                    // SIN FALLBACK a diseño o variante (eso confunde al usuario)
                    $imageUrl = $export->image ? $export->image->display_url : null;

                    // Familia (categoría del diseño)
                    $familyName = $export->design->categories->first()?->name ?? null;

                    // Formato del archivo (extensión)
                    $fileFormat = strtoupper($export->file_format ?? pathinfo($export->file_name ?? '', PATHINFO_EXTENSION));

                    // Tipo de aplicación: slug y nombre legible
                    $appTypeSlug = $export->application_type ?? 'general';
                    $appTypeName = $appTypes[$appTypeSlug] ?? ucfirst(str_replace('_', ' ', $appTypeSlug));

                    return [
                        'id' => $export->id,
                        // Datos del archivo de producción
                        'export_name' => $export->application_label,
                        'file_format' => $fileFormat,
                        // Preview: Prioridad 1) SVG del archivo, 2) imagen de referencia
                        'svg_content' => $export->svg_content,
                        'image_url' => $imageUrl,
                        // Tipo de aplicación (ubicación del bordado)
                        'app_type_slug' => $appTypeSlug,
                        'app_type_name' => $appTypeName,
                        // Trazabilidad: de dónde viene
                        'design_id' => $export->design_id,
                        'design_name' => $export->design->name,
                        'variant_id' => $export->design_variant_id,
                        'variant_name' => $variantName,
                        'family_name' => $familyName,
                        // Especificaciones técnicas
                        'dimensions_label' => $export->width_mm . 'x' . $export->height_mm . ' mm',
                        'stitches' => $export->stitches_count ?? 0,
                        'stitches_formatted' => number_format($export->stitches_count ?? 0),
                        'colors' => $export->colors_count ?? 0,
                    ];
                });

            return response()->json($exports);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener producciones: ' . $e->getMessage()], 500);
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

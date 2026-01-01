<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ProductService;
use App\Http\Requests\StoreProductRequest;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    /** 
     * Display a listing of the resource.
     */
    public function index()
    {
        // Usamos Eager Loading para traer todo de un solo golpe
        $products = Product::with([
            'category',            // Categoría del producto
            'extras',              // Extras asociados
            'variants.attributes.attribute', // Para obtener los nombres "Color", "Talla", etc.
            'variants.designExports' // ESTA ES LA CLAVE: Entra a variantes y de ahí a las exportaciones (archivos .pes/.dst)
        ])
            ->where('status', 'active') // Filtro por tu ENUM 'active'
            ->latest()
            ->get();

        return view('admin.productos.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        try {
            // El método $request->validated() solo devuelve los datos que pasaron la validación
            $product = $this->productService->createProduct($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Producto creado con éxito',
                'data' => $product
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errores de validación (campos faltantes, etc)
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Error genérico o de base de datos
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo procesar la solicitud. Contacte a soporte.',
                // Solo mostramos el mensaje real en desarrollo (no en producción)
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}

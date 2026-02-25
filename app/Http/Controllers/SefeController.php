<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SefeProveedor;
use App\Models\SefeFactura;

class SefeController extends Controller
{
    // 1. Carga la SPA de S.E.F.E.
    public function index()
    {
        // Traemos el catálogo para poder mapear o editar
        $proveedores = SefeProveedor::all();
        // Traemos el "Drive" de facturas procesadas
        $facturas = SefeFactura::with('proveedor')->latest()->get();

        return view('sefe', compact('proveedores', 'facturas'));
    }

    // 2. Guarda o actualiza el catálogo (El mapeo dinámico)
    public function guardarProveedor(Request $request)
    {
        $request->validate([
            'rfc' => 'required|string|max:15',
            'nombre' => 'required|string|max:255',
            'mapeo_columnas' => 'required|json', // Validamos que el frontend nos mande un JSON real
        ]);

        // Usamos updateOrCreate para que el mismo form sirva para crear y editar
        $proveedor = SefeProveedor::updateOrCreate(
            ['rfc' => trim(strtoupper($request->rfc))],
            [
                'nombre' => strtoupper($request->nombre),
                'mapeo_columnas' => json_decode($request->mapeo_columnas, true) // Lo decodificamos porque el modelo ya lo castea a array
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Proveedor configurado al tiro.',
            'proveedor' => $proveedor
        ]);
    }

    // 3. El motor de extracción (Lo dejamos preparado)
    public function procesarFacturas(Request $request)
    {
        // Aquí meteremos la magia de SimpleXMLElement y FastExcel
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Platform; // Importamos el modelo

class PlatformController extends Controller
{
    public function index(): View
    {
        // Extraemos solo las plataformas que están activas para mostrarlas en la interfaz
        $platforms = Platform::where('active', true)->get();

        // Compact pasa la variable a la vista blade
        return view('plataformas.index', compact('platforms'));
    }
}
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResurtidoController;
use App\Http\Controllers\FastResurtidoController;
use App\Http\Controllers\GeliaController;

// 1. Cuando entres a la página principal, te mostrará la pantalla web (la Vista)
Route::get('/', function () {
    return view('gelia');
});

// --- RUTAS DE GELIA (Versión 2.0) ---
//Route::get('/gelia', function () {
//    return view('gelia');
//})->name('gelia.vista');

//Cuando le des clic al botón de "Generar" en el formulario, 
// esta ruta recibe los archivos y se los manda al Cerebro (el Controlador)
//Route::post('/generar', [ResurtidoController::class, 'generar'])->name('resurtido.generar');

// Cambia la ruta POST para usar el FastController
//Route::post('/generar', [FastResurtidoController::class, 'generar'])->name('resurtido.generar');


// Ruta para generar
Route::post('/generar', [GeliaController::class, 'generar'])->name('gelia.generar');
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeliaController;

// 1. Ruta Principal (Carga la vista y las listas)
Route::get('/', [GeliaController::class, 'index'])->name('gelia.index');

// 2. Rutas de Acción
Route::post('/guardar-lista', [GeliaController::class, 'guardarLista'])->name('gelia.guardar');
Route::post('/generar', [GeliaController::class, 'generar'])->name('gelia.generar');

// 3. NUEVA: Ruta para eliminar (Soft Delete)
Route::delete('/eliminar-lista/{id}', [GeliaController::class, 'eliminarLista'])->name('gelia.eliminar');
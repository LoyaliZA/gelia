<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeliaController;
use App\Http\Controllers\SefeController;

// 1. Ruta Principal (Carga la vista y las listas)
Route::get('/', [GeliaController::class, 'index'])->name('gelia.index');

// 2. Rutas de Acción
Route::post('/guardar-lista', [GeliaController::class, 'guardarLista'])->name('gelia.guardar');
Route::post('/generar', [GeliaController::class, 'generar'])->name('gelia.generar');

// 3. NUEVA: Ruta para eliminar (Soft Delete)
Route::delete('/eliminar-lista/{id}', [GeliaController::class, 'eliminarLista'])->name('gelia.eliminar');

// 4. NUEVA: Ruta para restaurar (Soft Restore)
Route::post('/actualizar-lista/{id}', [GeliaController::class, 'actualizarLista'])->name('gelia.actualizar');


// =========================================================
// S.E.F.E. | Sistema Extractor de Facturas Electrónicas
// =========================================================
Route::get('/sefe', [SefeController::class, 'index'])->name('sefe.index');
Route::post('/sefe/proveedor', [SefeController::class, 'guardarProveedor'])->name('sefe.proveedor.guardar');
Route::post('/sefe/procesar', [SefeController::class, 'procesarFacturas'])->name('sefe.procesar');

//Ruta de pruebas
Route::get('/gelia-test', [GeliaController::class, 'testIndex'])->name('gelia.test');
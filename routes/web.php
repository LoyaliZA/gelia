<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeliaController;
use App\Http\Controllers\BellaromaController; // <-- IMPORTAMOS EL NUEVO CONTROLADOR
//use App\Http\Controllers\SefeController;

// =========================================================
// RUTA PRINCIPAL (Home - Selección de Departamento)
// =========================================================
Route::get('/', function () {
    return view('home');
})->name('home');

// =========================================================
// MÓDULO AROMAS (Antes Gelia Core)
// =========================================================
Route::prefix('aromas')->group(function () {
    // 1. Ruta Principal (Carga la vista y las listas)
    Route::get('/', [GeliaController::class, 'index'])->name('gelia.index');

    // 2. Rutas de Acción
    Route::post('/guardar-lista', [GeliaController::class, 'guardarLista'])->name('gelia.guardar');
    Route::post('/generar', [GeliaController::class, 'generar'])->name('gelia.generar');

    // 3. NUEVA: Ruta para eliminar (Soft Delete)
    Route::delete('/eliminar-lista/{id}', [GeliaController::class, 'eliminarLista'])->name('gelia.eliminar');

    // 4. NUEVA: Ruta para restaurar (Soft Restore)
    Route::post('/actualizar-lista/{id}', [GeliaController::class, 'actualizarLista'])->name('gelia.actualizar');

    // Ruta de pruebas
    Route::get('/gelia-test', [GeliaController::class, 'testIndex'])->name('gelia.test');
});

// =========================================================
// MÓDULO BELLAROMA | Generador de Plantillas
// =========================================================
Route::prefix('bellaroma')->group(function () {
    // Esta ruta es para ver la página web (Interfaz)
    Route::get('/', [BellaromaController::class, 'index'])->name('bellaroma.index');
    // Esta ruta es para recibir los archivos y devolver el Excel armado
    Route::post('/generar', [BellaromaController::class, 'generar'])->name('bellaroma.generar');
});

// =========================================================
// S.E.F.E. | Sistema Extractor de Facturas Electrónicas
// =========================================================
//Route::get('/sefe', [SefeController::class, 'index'])->name('sefe.index');
//Route::post('/sefe/proveedor', [SefeController::class, 'guardarProveedor'])->name('sefe.proveedor.guardar');
//Route::post('/sefe/procesar', [SefeController::class, 'procesarFacturas'])->name('sefe.procesar');
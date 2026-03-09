<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeliaController;
use App\Http\Controllers\BellaromaController;
use App\Http\Controllers\AromasClienteController; // <-- 1. IMPORTAMOS EL NUEVO CONTROLADOR DE CLIENTES
//use App\Http\Controllers\SefeController;

// =========================================================
// RUTA PRINCIPAL (Home - Selección de Departamento)
// =========================================================
Route::get('/', function () {
    return view('home');
})->name('home');

// =========================================================
// MÓDULO AROMAS (Dividido por Funcionalidad)
// =========================================================
Route::prefix('aromas')->group(function () {
    // Rutas del Core (Listas, Existencias, Costos - Aún en GeliaController)
    Route::get('/', [GeliaController::class, 'index'])->name('gelia.index');
    Route::post('/guardar-lista', [GeliaController::class, 'guardarLista'])->name('gelia.guardar');
    Route::post('/generar', [GeliaController::class, 'generar'])->name('gelia.generar');
    Route::delete('/eliminar-lista/{id}', [GeliaController::class, 'eliminarLista'])->name('gelia.eliminar');
    Route::post('/actualizar-lista/{id}', [GeliaController::class, 'actualizarLista'])->name('gelia.actualizar');
    Route::get('/gelia-test', [GeliaController::class, 'testIndex'])->name('gelia.test');

    // ---------------------------------------------------------
    // 2. NUEVAS RUTAS: MÓDULO DE CLIENTES INDEPENDIENTE
    // ---------------------------------------------------------
    // Ruta para ver la vista de clientes
    Route::get('/clientes', [AromasClienteController::class, 'index'])->name('aromas.clientes.index');
    // Ruta para que el botón "Procesar" envíe el archivo aquí
    Route::post('/clientes/procesar', [AromasClienteController::class, 'procesar'])->name('aromas.clientes.procesar');
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
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AromasListasController; // <-- NUEVO CEREBRO PRINCIPAL
use App\Http\Controllers\BellaromaController;
use App\Http\Controllers\AromasClienteController;
use App\Http\Controllers\AromasGastoController;
use App\Http\Controllers\AromasTransaccionController;

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
    
    // 1. MÓDULO DE LISTADOS E INVENTARIOS (El nuevo Core)
    // Conservamos los names originales (gelia.*) temporalmente por retrocompatibilidad con las otras vistas
    Route::get('/', [AromasListasController::class, 'index'])->name('gelia.index');
    Route::post('/guardar-lista', [AromasListasController::class, 'guardarLista'])->name('gelia.guardar');
    Route::post('/generar', [AromasListasController::class, 'generar'])->name('gelia.generar');
    Route::delete('/eliminar-lista/{id}', [AromasListasController::class, 'eliminarLista'])->name('gelia.eliminar');
    Route::post('/actualizar-lista/{id}', [AromasListasController::class, 'actualizarLista'])->name('gelia.actualizar');

    // ---------------------------------------------------------
    // 2. MÓDULO DE CLIENTES INDEPENDIENTE
    // ---------------------------------------------------------
    Route::get('/clientes', [AromasClienteController::class, 'index'])->name('aromas.clientes.index');
    Route::post('/clientes/procesar', [AromasClienteController::class, 'procesar'])->name('aromas.clientes.procesar');

    // ---------------------------------------------------------
    // 3. MÓDULO DE GASTOS COMPROBABLES
    // ---------------------------------------------------------
    Route::get('/gastos', [AromasGastoController::class, 'index'])->name('aromas.gastos.index');
    Route::post('/gastos/procesar', [AromasGastoController::class, 'procesar'])->name('aromas.gastos.procesar');

    // ---------------------------------------------------------
    // 4. MÓDULO DE TRANSACCIONES BANCARIAS
    // ---------------------------------------------------------
    Route::get('/transacciones', [AromasTransaccionController::class, 'index'])->name('aromas.transacciones.index');
    Route::post('/transacciones/procesar', [AromasTransaccionController::class, 'procesar'])->name('aromas.transacciones.procesar');
});

// =========================================================
// MÓDULO BELLAROMA | Generador de Plantillas
// =========================================================
Route::prefix('bellaroma')->group(function () {
    Route::get('/', [BellaromaController::class, 'index'])->name('bellaroma.index');
    Route::post('/generar', [BellaromaController::class, 'generar'])->name('bellaroma.generar');
});
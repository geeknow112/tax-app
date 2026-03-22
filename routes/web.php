<?php

use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExpenseController::class, 'index'])->name('expenses.index');
Route::post('/expenses/{expense}/classify', [ExpenseController::class, 'classify'])->name('expenses.classify');
Route::get('/expenses/search-prev', [ExpenseController::class, 'searchPrevYear'])->name('expenses.searchPrev');

Route::get('/import', [ImportController::class, 'show'])->name('import.show');
Route::post('/import', [ImportController::class, 'store'])->name('import.store');

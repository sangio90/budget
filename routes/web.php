<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetAmountController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/budget/importi', [BudgetAmountController::class, 'index'])->name('budget.importi');
    Route::post('/budget/importi', [BudgetAmountController::class, 'save'])->name('budget.importi.save');
    Route::delete('/budget/importi/{category}', [BudgetAmountController::class, 'reset'])->name('budget.importi.reset');

    Route::get('/budget', [BudgetController::class, 'index'])->name('budget.index');
    Route::post('/budget', [BudgetController::class, 'store'])->name('budget.store');
    Route::get('/budget/spese', [BudgetController::class, 'spese'])->name('budget.spese');
    Route::get('/budget/categories', [BudgetController::class, 'categoriesJson'])->name('budget.categories');
    Route::get('/budget/{expense}/edit', [BudgetController::class, 'edit'])->name('budget.edit');
    Route::put('/budget/{expense}', [BudgetController::class, 'update'])->name('budget.update');
    Route::delete('/budget/{expense}', [BudgetController::class, 'destroy'])->name('budget.destroy');

    Route::get('/transazioni', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transazioni', [TransactionController::class, 'store'])->name('transactions.store');
    Route::delete('/transazioni/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::get('/transazioni/causali', [TransactionController::class, 'causaliJson'])->name('transactions.causali');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

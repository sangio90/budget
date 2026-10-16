<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetAmountController;
use App\Http\Controllers\BudgetCategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ExtraIncomeController;
use App\Http\Controllers\FiscaleController;
use App\Http\Controllers\StatisticheController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/budget/importi', [BudgetAmountController::class, 'index'])->name('budget.importi');
    Route::post('/budget/importi', [BudgetAmountController::class, 'save'])->name('budget.importi.save');
    Route::delete('/budget/importi/{category}', [BudgetAmountController::class, 'reset'])->name('budget.importi.reset');

    Route::post('/budget/categorie/voce', [BudgetCategoryController::class, 'storeVoce'])->name('budget.categorie.voce.store');
    Route::put('/budget/categorie/voce/{category}', [BudgetCategoryController::class, 'updateVoce'])->name('budget.categorie.voce.update');
    Route::patch('/budget/categorie/voce/{category}/sposta', [BudgetCategoryController::class, 'moveVoce'])->name('budget.categorie.voce.move');
    Route::delete('/budget/categorie/voce/{category}', [BudgetCategoryController::class, 'destroyVoce'])->name('budget.categorie.voce.destroy');
    Route::post('/budget/categorie/rename', [BudgetCategoryController::class, 'renameCategoria'])->name('budget.categorie.rename');
    Route::post('/budget/categorie/destroy', [BudgetCategoryController::class, 'destroyCategoria'])->name('budget.categorie.destroy');

    Route::get('/budget', [BudgetController::class, 'index'])->name('budget.index');
    Route::post('/budget', [BudgetController::class, 'store'])->name('budget.store');
    Route::get('/budget/spese', [BudgetController::class, 'spese'])->name('budget.spese');
    Route::get('/budget/categories', [BudgetController::class, 'categoriesJson'])->name('budget.categories');
    Route::get('/budget/{expense}/edit', [BudgetController::class, 'edit'])->name('budget.edit');
    Route::put('/budget/{expense}', [BudgetController::class, 'update'])->name('budget.update');
    Route::delete('/budget/{expense}', [BudgetController::class, 'destroy'])->name('budget.destroy');

    Route::get('/statistiche', [StatisticheController::class, 'index'])->name('statistiche.index');

    Route::get('/transazioni', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transazioni', [TransactionController::class, 'store'])->name('transactions.store');
    Route::delete('/transazioni/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::get('/transazioni/causali', [TransactionController::class, 'causaliJson'])->name('transactions.causali');

    Route::get('/entrate-extra', [ExtraIncomeController::class, 'index'])->name('extra-incomes.index');
    Route::post('/entrate-extra', [ExtraIncomeController::class, 'store'])->name('extra-incomes.store');
    Route::get('/entrate-extra/{extraIncome}/edit', [ExtraIncomeController::class, 'edit'])->name('extra-incomes.edit');
    Route::put('/entrate-extra/{extraIncome}', [ExtraIncomeController::class, 'update'])->name('extra-incomes.update');
    Route::delete('/entrate-extra/{extraIncome}', [ExtraIncomeController::class, 'destroy'])->name('extra-incomes.destroy');

    Route::get('/fiscale', [FiscaleController::class, 'index'])->name('fiscale.index');
    Route::put('/fiscale/parametri/{anno}', [FiscaleController::class, 'updateParametri'])->whereNumber('anno')->name('fiscale.parametri.update');
    Route::post('/fiscale/clienti', [FiscaleController::class, 'storeCliente'])->name('fiscale.clienti.store');
    Route::delete('/fiscale/clienti/{client}', [FiscaleController::class, 'destroyCliente'])->name('fiscale.clienti.destroy');
    Route::put('/fiscale/categorie', [FiscaleController::class, 'updateCategorie'])->name('fiscale.categorie.update');
    Route::post('/fiscale/scadenze', [FiscaleController::class, 'storeScadenza'])->name('fiscale.scadenze.store');
    Route::post('/fiscale/scadenze/genera', [FiscaleController::class, 'generaStime'])->name('fiscale.scadenze.genera');
    Route::get('/fiscale/scadenze/{deadline}/edit', [FiscaleController::class, 'editScadenza'])->name('fiscale.scadenze.edit');
    Route::put('/fiscale/scadenze/{deadline}', [FiscaleController::class, 'updateScadenza'])->name('fiscale.scadenze.update');
    Route::delete('/fiscale/scadenze/{deadline}', [FiscaleController::class, 'destroyScadenza'])->name('fiscale.scadenze.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

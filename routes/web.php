<?php

use App\Http\Controllers\BookItemController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');

Route::get('/guidelines', function () {
    return Inertia::render('Guidelines');
})->name('guidelines');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/help', function () {
        return Inertia::render('Help/Index');
    })->name('help.index');

    Route::get('/books/mine', [BookItemController::class, 'index'])->name('books.mine');
    Route::get('/books/create', [BookItemController::class, 'create'])->name('books.create');
    Route::get('/books/pending-verification', [BookItemController::class, 'pendingVerification'])->name('books.pending-verification');
    Route::get('/books/{bookItem}/edit', [BookItemController::class, 'edit'])->name('books.edit');
    Route::post('/books', [BookItemController::class, 'store'])->name('books.store');
    Route::patch('/books/{bookItem}', [BookItemController::class, 'update'])->name('books.update');
    Route::get('/books/import-template', [BookItemController::class, 'downloadImportTemplate'])->name('books.import-template');
    Route::post('/books/import-csv', [BookItemController::class, 'importCsv'])->name('books.import-csv');
    Route::post('/books/verify-bulk', [BookItemController::class, 'verifyBulk'])->name('books.verify-bulk');
    Route::patch('/books/{bookItem}/verify', [BookItemController::class, 'verify'])->name('books.verify');
    Route::patch('/books/{bookItem}/mark-pending', [BookItemController::class, 'markPending'])->name('books.mark-pending');
    Route::patch('/books/{bookItem}/remove', [BookItemController::class, 'remove'])->name('books.remove');
    Route::patch('/books/{bookItem}/reshelve', [BookItemController::class, 'reshelve'])->name('books.reshelve');

    Route::get('/loans/borrowed', [LoanController::class, 'borrowed'])->name('loans.borrowed');
    Route::get('/loans/requests', [LoanController::class, 'requests'])->name('loans.requests');
    Route::get('/loans/waitlist', [LoanController::class, 'waitlist'])->name('waitlist.index');
    Route::patch('/waitlist/{entry}/cancel', [LoanController::class, 'leaveWaitlist'])->name('waitlist.cancel');
    Route::post('/catalog/{bookItem}/request', [LoanController::class, 'store'])->name('loans.store');
    Route::patch('/loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
    Route::patch('/loans/{loan}/reject', [LoanController::class, 'reject'])->name('loans.reject');
    Route::patch('/loans/{loan}/share', [LoanController::class, 'share'])->name('loans.share');
    Route::patch('/loans/{loan}/return', [LoanController::class, 'returnBook'])->name('loans.return');
    Route::patch('/loans/{loan}/cancel', [LoanController::class, 'cancel'])->name('loans.cancel');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//アカウント登録
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

Route::prefix('mypage')->name('mypage.')->group(function () {
    Route::get('/', function () {
        $user = Auth::guard('web')->user();
        if ($user) {
            return redirect()->route('mypage.dashboard');
        } else {
            return redirect()->route('mypage.login');
        }
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth:web', 'verified'])->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__.'/auth.php';
});

Route::prefix('admin')->name('admin.')->group(function () {
    require __DIR__.'/admin.php';
});

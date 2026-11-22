<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');
Route::livewire('trip/{code}', 'pages::trip.show')->name('trip.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('bookings/{booking}', 'pages::bookings.show')->name('bookings.show');
});

require __DIR__.'/settings.php';
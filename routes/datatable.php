<?php

use Illuminate\Support\Facades\Route;
use Senna\Datatable\Livewire\Admin\DatatableAdmin;

Route::name("senna.")->group(function () {
    Route::get('/datatable', DatatableAdmin::class)->name('datatable');
});
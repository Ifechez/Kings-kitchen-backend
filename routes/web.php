<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['app' => "Kings' Kitchen API", 'status' => 'ok']));

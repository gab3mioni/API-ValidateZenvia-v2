<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TemplateController;

Route::post('/enviar-template', [TemplateController::class, 'enviar']);
Route::get('/hello-world', function () {
    return response()->json(['message' => 'Hello World']);
});

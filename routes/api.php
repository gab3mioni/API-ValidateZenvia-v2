<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TemplateController;

Route::post('/enviar-template', [TemplateController::class, 'enviar']);

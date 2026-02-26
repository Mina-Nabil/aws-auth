<?php

use App\Containers\AppSection\Authentication\UI\API\Controllers\WebClient\IssueTokenController;
use Illuminate\Support\Facades\Route;

Route::post('clients/login', IssueTokenController::class);

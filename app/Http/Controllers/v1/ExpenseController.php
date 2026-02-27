<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\ExpenseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(protected ExpenseService $expenseService) {}
}

<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\BudgetService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(protected BudgetService $budgetService) {}
}

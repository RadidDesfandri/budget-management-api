<?php

namespace App\Services;

use App\Repositories\BudgetRepository;

class BudgetService
{
    public function __construct(protected BudgetRepository $budgetRepository) {}
}

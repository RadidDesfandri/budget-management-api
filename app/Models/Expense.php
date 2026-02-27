<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $table = "expenses";

    protected $fillable = [
        "title",
        "amount",
        "description",
        "status",
        "receipt_url",
        "expense_date",
        "approved_at",
        "rejected_at",
        "rejected_reason",
        "user_id",
        "category_id",
        "organization_id",
        "budget_id",
    ];

    protected $appends = ["full_receipt_url"];

    protected function casts(): array
    {
        return [
            "amount" => "decimal:2",
            "approved_at" => "datetime",
            "rejected_at" => "datetime",
            "expense_date" => "date",
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function getFullReceiptUrlAttribute()
    {
        if ($this->receipt_url) {
            return asset("storage/" . $this->receipt_url);
        }

        return null;
    }
}

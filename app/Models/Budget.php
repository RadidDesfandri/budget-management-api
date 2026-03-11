<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $table = "budgets";

    protected $fillable = [
        "amount",
        "month",
        "category_id",
        "organization_id",
        "created_by",
    ];

    protected $guarded = ["id"];

    protected function casts(): array
    {
        return [
            "month" => "date",
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, "created_by");
    }

    public function getMonthFormattedAttribute()
    {
        return $this->month->format("Y-m");
    }
}

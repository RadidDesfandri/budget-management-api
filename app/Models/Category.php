<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = "categories";

    protected $fillable = [
        "name",
        "organization_id",
        "icon",
        "icon_color",
        "background_color",
        "created_by",
    ];

    protected $guarded = ["id"];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, "created_by");
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}

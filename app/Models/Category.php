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
    ];

    protected $guarded = ["id"];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}

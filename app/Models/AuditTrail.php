<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    protected $table = "audit_trails";

    protected $fillable = [
        "organization_id",
        "user_id",
        "action_type",
        "description",
        "metadata",
        "ip_address",
    ];

    protected $guarded = ["id"];

    protected function casts(): array
    {
        return [
            "metadata" => "array",
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}

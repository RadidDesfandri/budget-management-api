<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationUser extends Model
{
    const ROLE_OWNER = "owner";
    const ROLE_MEMBER = "member";
    const ROLE_FINANCE = "finance";
    const ROLE_ADMIN = "admin";

    protected $table = "organization_users";

    protected $fillable = ["organization_id", "user_id", "role", "joined_at"];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

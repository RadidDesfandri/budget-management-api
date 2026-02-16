<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Organization extends Model
{
    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'logo_url',
        'owner_id',
    ];

    protected $appends = ['full_logo_url', 'current_user_role'];

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'organization_users'
        )->withPivot('role')->withTimestamps();
    }

    public function getFullLogoUrlAttribute()
    {
        if ($this->logo_url) {
            return asset('storage/' . $this->logo_url);
        }

        return null;
    }

    public function getCurrentUserRoleAttribute()
    {
        if (!Auth::check()) {
            return null;
        }

        $userOrg = $this->users()
            ->where('user_id', Auth::id())
            ->first();

        return $userOrg ? $userOrg->pivot->role : null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'logo_url',
        'owner_id',
    ];

    protected $appends = ['full_logo_url'];

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
}

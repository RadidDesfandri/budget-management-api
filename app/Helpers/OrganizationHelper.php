<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class OrganizationHelper
{
    /**
     * Mengambil ID organisasi aktif dari user yang sedang login.
     */
    public static function getOrganizationId()
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return $user->current_organization_id;
    }

    /**
     * Menyimpan ID organisasi aktif ke database user.
     */
    public static function setOrganizationId($organizationId)
    {
        $user = Auth::user();

        if ($user) {
            $user->current_organization_id = $organizationId;
            $user->save();
        }
    }
}

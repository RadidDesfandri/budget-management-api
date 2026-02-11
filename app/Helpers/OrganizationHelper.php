<?php

namespace App\Helpers;

class OrganizationHelper
{
    public const ORGANIZATION_ID = 'organization:current_id';

    public static function getOrganizationId()
    {
        return session()->get(self::ORGANIZATION_ID);
    }

    public static function setOrganizationId($organizationId)
    {
        session()->put(self::ORGANIZATION_ID, $organizationId);
    }
}

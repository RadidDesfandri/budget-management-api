<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\OrganizationService;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB
        ]);

        $organization = app(OrganizationService::class)
            ->createOrganization($request->user()->id, $request->all());

        return $this->successResponse('Organization created successfully', $organization, 200);
    }
}

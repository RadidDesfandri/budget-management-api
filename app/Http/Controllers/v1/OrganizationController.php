<?php

namespace App\Http\Controllers\v1;

use App\Helpers\OrganizationHelper;
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

    public function orgDropdownOptions(Request $request)
    {
        $data = app(OrganizationService::class)->orgDropdownOptions($request->user());

        return $this->successResponse('Organization dropdown options', $data, 200);
    }

    public function setActiveOrganization(Request $request)
    {
        $validate = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
        ], [
            'organization_id.exists' => 'The provided organization does not exist.',
        ]);

        OrganizationHelper::setOrganizationId($validate['organization_id']);

        return $this->successResponse('Organization set successfully', null, 200);
    }

    public function memberList(Request $request)
    {
        $filters = [
            'search'    => $request->input('search'),
            'sort_by'   => $request->input('sort_by', 'joined_at'),
            'order_by'  => $request->input('order_by', 'desc'),
            'page_size' => $request->input('page_size', 10),
        ];

        $data = app(OrganizationService::class)->memberList($request->user(), $filters);

        return $this->successResponse('Member list', $data, 200);
    }
}

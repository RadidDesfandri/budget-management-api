<?php

namespace App\Http\Controllers\v1;

use App\Helpers\OrganizationHelper;
use App\Http\Controllers\Controller;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB
            'member_ids' => [
                'nullable',
                'array',
                Rule::exists('users', 'id')->whereNot('id', Auth::id()),
            ],
        ], [
            'member_ids.exists' => 'The provided members do not exist.',
            'member_ids.array' => 'The provided members must be an array.',
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
        $data = app(OrganizationService::class)->memberList($request->user());

        return $this->successResponse('Member list', $data, 200);
    }
}

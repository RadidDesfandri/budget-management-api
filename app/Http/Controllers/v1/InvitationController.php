<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InvitationController extends Controller
{
    public function createInvitation(Request $request, $organizationId)
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'required|string|in:admin,member,finance',
        ]);

        DB::beginTransaction();

        try {
            $existingMember = app(InvitationService::class)->ensureEmailNotMember($request->email, $organizationId);

            if ($existingMember) {
                DB::rollBack();
                return $this->errorResponse('User with this email is already a member of this organization', null, 400);
            }

            $activeInvitation = app(InvitationService::class)->ensureNoActiveInvitation($request->email, $organizationId);

            if ($activeInvitation) {
                DB::rollBack();
                return $this->errorResponse('An active invitation already exists for this email', null, 400);
            }

            $invitation = app(InvitationService::class)->createInvitation($request->all(), $organizationId);

            $organization = $request->user()->organizations()->where('organizations.id', $organizationId)->first();

            Mail::to($request->email)->send(new InvitationMail($invitation, $organization));

            DB::commit();
            return $this->successResponse('Invitation created successfully, and email sent to ' . $request->email, $invitation, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create invitation', $e->getMessage(), 500);
        }
    }
}

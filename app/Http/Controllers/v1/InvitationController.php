<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\InvitationService;
use Exception;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function __construct(
        protected InvitationService $invitationService
    ) {}

    public function createInvitation(Request $request, $organizationId)
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'required|string|in:admin,member,finance',
        ]);

        try {
            $invitation = $this->invitationService->createInvitation(
                $request->all(),
                $organizationId,
                $request->user()
            );

            return $this->successResponse(
                'Invitation created successfully',
                $invitation,
                201
            );
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = ($code >= 200 && $code <= 599) ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function verifyTokenInvitation(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $data = $this->invitationService->verifyTokenInvitation(
                $request->token,
                $request->user()->email
            );

            return $this->successResponse('Token invitation verified.', $data, 200);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = ($code >= 200 && $code <= 599) ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function acceptInvitation(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $invitation = $this->invitationService->verifyTokenInvitation(
                $request->token,
                $request->user()->email
            );

            $this->invitationService->acceptInvitation($invitation, $request->user()->id);

            return $this->successResponse('Invitation accepted successfully.', null, 200);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = ($code >= 200 && $code <= 599) ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }

    public function rejectInvitation(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $invitation = $this->invitationService->verifyTokenInvitation(
                $request->token,
                $request->user()->email
            );

            $this->invitationService->rejectInvitation($invitation, $request->user()->id);

            return $this->successResponse('Invitation rejected successfully.', null, 200);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $httpCode = ($code >= 200 && $code <= 599) ? $code : 500;

            return $this->errorResponse($e->getMessage(), null, $httpCode);
        }
    }
}

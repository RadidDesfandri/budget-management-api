@extends('emails.layouts.master')

@section('content')
    <h3>Halo, {{ $invitation->email }}!</h3>

    <p>
        You have been invited to join the organization <b>{{ $organization->name }}</b>.
        You have been invited as an <b>{{ ucfirst($invitation->role) }}</b>.
    </p>

    <p>Please click the button below to accept the invitation:</p>

    <div style="text-align: center; margin: 20px 0;">
        <a href="{{ $frontendUrl }}" class="btn">
            Accept Invitation
        </a>
    </div>

    <div>
        <p>Or copy and paste the following token into invitation form:</p>
        <div style="text-align: center; margin: 20px 0; background-color: #f6f6f6; padding: 20px; border-radius: 5px;">
            {{ $invitation->token }}
        </div>
    </div>

    <p>This invitation will expire in 7 days.</p>
    <p>If you do not wish to accept this invitation, you can ignore this email.</p>

    <p>Best regards,<br>The BudgetFlow Team</p>
@endsection

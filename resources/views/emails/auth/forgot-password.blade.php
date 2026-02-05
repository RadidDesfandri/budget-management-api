@extends('emails.layouts.master')

@section('content')
    <h3>Halo, {{ $user->name }}!</h3>

    <p>You recently requested to reset your password for your account.</p>

    <p>Please click the button below to verify your account:</p>

    <div style="text-align: center; margin: 20px 0;">
        <a href="{{ $frontendUrl }}" class="btn">
            Reset Password
        </a>
    </div>

    <p>If you don't feel like signing up, please ignore this email.</p>

    <p>Best regards,<br>The BudgetFlow Team</p>
@endsection

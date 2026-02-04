@extends('emails.layouts.master')

@section('content')
    <h3>Halo, {{ $user->name }}!</h3>

    <p>Thank you for registering on our app. Your registration was successful.</p>

    <p>Please click the button below to verify your account:</p>

    <div style="text-align: center; margin: 20px 0;">
        <a href="{{ $frontendUrl }}" class="btn">
            Verify Account
        </a>
    </div>

    <p>If you don't feel like signing up, please ignore this email.</p>

    <p>Best regards,<br>The BudgetFlow Team</p>
@endsection

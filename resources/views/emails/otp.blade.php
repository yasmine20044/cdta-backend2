<x-mail::message>
# Login Verification

Here is your One-Time Password (OTP) to securely log in to your account.

<x-mail::panel>
## {{ $otp }}
</x-mail::panel>

This code will expire in 10 minutes. If you did not request this, please ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

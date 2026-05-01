<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
 public function boot()
{
    if (config('app.env') !== 'local') {
        URL::forceScheme('https');
    }

    Mail::extend('gmail-oauth', function () {
        // Fetch a fresh Access Token using the Refresh Token
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id'     => env('CLIENT_ID'),
            'client_secret' => env('CLIENT_SECRET'),
            'refresh_token' => env('REFRESH_TOKEN'),
            'grant_type'    => 'refresh_token',
        ]);
        
        $accessToken = $response->json('access_token');

        // Build the Symfony SMTP Transport using XOAUTH2
        $transport = new EsmtpTransport('smtp.gmail.com', 465, true); // true for TLS/SSL
        $transport->setUsername(env('EMAIL_USER'));
        $transport->setPassword($accessToken);

        return $transport;
    });
}
}

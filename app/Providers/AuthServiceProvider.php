<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // custume verification email

   /* VerifyEmail::toMailUsing(function ($notifiable) use ($url) {
        return (new MailMessage)
        ->subject(Lang::get('Verify Email Address'))
        ->line(Lang::get('Please click the button below to verify your email address.'))
        ->action(Lang::get('Verify Email Address'), $url)
        ->line(Lang::get('If you did not create an account, no further action is required.'));
    });*/

        //permet de lister les routes de passport

    }
}

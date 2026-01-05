<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament; // <-- Esta es la importación correcta
use Filament\Panel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Models\SystemSetting;
use App\Listeners\LogAuthenticationActivity;


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
    public function boot(): void
    {
        Event::listen(Login::class, [LogAuthenticationActivity::class, 'handle']);
        Event::listen(Logout::class, [LogAuthenticationActivity::class, 'handle']);

        try {
            // Inyectar configuraciones globales
            if (\Schema::hasTable('system_settings')) {
                $companyName = \App\Models\SystemSetting::getValue('company_name');
                if ($companyName) {
                    \Config::set('adminlte.title', $companyName);
                    \Config::set('adminlte.logo', "<b>" . substr($companyName, 0, 3) . "</b> " . substr($companyName, 3));
                }

                $companyLogo = \App\Models\SystemSetting::getValue('company_logo');
                if ($companyLogo && \Storage::disk('public')->exists($companyLogo)) {
                    $logoUrl = \Storage::url($companyLogo);
                    \Config::set('adminlte.logo_img', $logoUrl);
                    \Config::set('adminlte.preloader.img.path', $logoUrl);

                    // Activar logo en login
                    \Config::set('adminlte.auth_logo.enabled', true);
                    \Config::set('adminlte.auth_logo.img.path', $logoUrl);
                }
            }
        } catch (\Exception $e) {
            // Fallback silencioso si hay error en DB o durante migraciones
        }
    }
}

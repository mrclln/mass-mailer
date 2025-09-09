<?php

namespace Mrclln\MassMailer\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Mrclln\MassMailer\Livewire\MassMailer;

class MassMailerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/mass-mailer.php', 'mass-mailer'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load package resources
        $this->loadViewsFrom(__DIR__.'/../Views', 'mass-mailer');
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');

        // Publish resources
        $this->publishes([
            __DIR__.'/../Config/mass-mailer.php' => config_path('mass-mailer.php'),
        ], 'mass-mailer-config');

        $this->publishes([
            __DIR__.'/../Views' => resource_path('views/vendor/mass-mailer'),
        ], 'mass-mailer-views');

        $this->publishes([
            __DIR__.'/../Migrations' => database_path('migrations'),
        ], 'mass-mailer-migrations');

        // Register Livewire component
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('mass-mailer', MassMailer::class);
        }

        // Register commands (if any)
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Add commands here if needed
            ]);
        }
    }
}

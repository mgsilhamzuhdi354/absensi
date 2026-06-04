<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        $this->preventDestructiveProductionCommands();

        Gate::define('admin', function (User $user) {
            return $user->is_admin === 'admin';
        });

        Paginator::useBootstrap();
    }

    private function preventDestructiveProductionCommands()
    {
        if (!$this->app->runningInConsole() || !$this->app->environment('production')) {
            return;
        }

        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            $blockedCommands = [
                'db:wipe',
                'migrate:fresh',
                'migrate:refresh',
                'migrate:reset',
                'migrate:rollback',
            ];

            if (!in_array($event->command, $blockedCommands, true)) {
                return;
            }

            if ((bool) env('ALLOW_DESTRUCTIVE_COMMANDS', false)) {
                return;
            }

            throw new \RuntimeException(
                'Command ' . $event->command . ' diblokir di production agar data tidak terhapus. '
                . 'Restore/pull aman tidak membutuhkan command ini.'
            );
        });
    }
}

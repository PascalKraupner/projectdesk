<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);

        // Runs before authentication, so key off the presented token rather than a
        // resolved user: a bucket per key for real callers, per IP for anonymous
        // ones, so nobody can grind against the token check for free. The token is
        // hashed so raw credentials never land in a cache key.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by(
            ($token = $request->bearerToken())
                ? 'key:'.hash('sha256', $token)
                : 'ip:'.$request->ip(),
        ));

        if ($this->app->isProduction()) {
            DB::prohibitDestructiveCommands();
            URL::forceScheme('https');
        }
    }
}

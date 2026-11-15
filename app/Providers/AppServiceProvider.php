<?php

namespace App\Providers;

use App\Models\Item;
use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewComment;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const API_CALLS_PER_MINUTE = 60;

    private const UPLOADS_PER_MINUTE = 20;

    private const AUTH_ATTEMPTS_PER_MINUTE = 5;

    private const AUTH_ATTEMPTS_PER_IP_PER_MINUTE = 20;

    private const ID_PATTERN = '[0-9]{1,18}';

    private const ID_PARAMETERS = ['item', 'company', 'review', 'comment', 'customer', 'panelItem'];

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
        Relation::morphMap([
            Report::TARGET_REVIEW => Review::class,
            Report::TARGET_COMMENT => ReviewComment::class,
        ]);

        Route::bind('panelItem', fn (string $id) => Item::query()
            ->whereKey($id)
            ->where('company_id', request()->user()?->company?->id)
            ->firstOrFail());

        foreach (self::ID_PARAMETERS as $parameter) {
            Route::pattern($parameter, self::ID_PATTERN);
        }

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(self::API_CALLS_PER_MINUTE)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(self::UPLOADS_PER_MINUTE)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(self::AUTH_ATTEMPTS_PER_MINUTE)
                ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(self::AUTH_ATTEMPTS_PER_IP_PER_MINUTE)->by($request->ip()),
        ]);
    }
}

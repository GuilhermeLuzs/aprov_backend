<?php

namespace App\Providers;

use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewComment;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        Relation::morphMap([
            Report::TARGET_REVIEW => Review::class,
            Report::TARGET_COMMENT => ReviewComment::class,
        ]);
    }
}

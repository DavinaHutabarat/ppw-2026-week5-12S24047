<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Audit kueri SQL: bukti eager loading mencegah N+1
        DB::listen(function ($query) {
            Log::info(sprintf(
                "[SQL AUDIT] (%.2f ms) %s", $query->time, $query->sql
            ));
        });
    }
}
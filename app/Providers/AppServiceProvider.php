<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view) {
            $navigation = collect(config('toko-plastik.navigation', []))
                ->filter(function (array $item) {
                    if (! auth()->check()) {
                        return false;
                    }

                    return in_array(auth()->user()->role->value, $item['roles'], true);
                })
                ->values();

            $view->with('navigation', $navigation);
        });
    }
}

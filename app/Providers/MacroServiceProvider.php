<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any macros
     */
    public function boot(): void
    {
        Str::macro('possessive', function (string $string) {
            return "$string'".(Str::endsWith($string, ['s', 'S']) ? '' : 's');
        });
    }
}

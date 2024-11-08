<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function actingAs(UserContract $user, $guard = null, array $abilities = ['*']): static
    {
        Sanctum::actingAs($user, $abilities, 'api');

        return $this;
    }
}

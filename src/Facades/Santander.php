<?php

declare(strict_types=1);

namespace Santander\SDK\Facades;

use Illuminate\Support\Facades\Facade;
use Santander\SDK\SantanderSdk;

class Santander extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SantanderSdk::class;
    }
}
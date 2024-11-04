<?php

namespace App\Traits;

use BackedEnum;

trait HasValues
{
    /**
     * Get an array of each case's value
     *
     * @param  BackedEnum[]|BackedEnum  $except
     * @return int[]|string[]
     */
    public static function values(array|BackedEnum $except = []): array
    {
        if ($except instanceof BackedEnum) {
            $except = [$except];
        }

        return collect(self::cases())->map(function (BackedEnum $case) use ($except) {
            if (! in_array($case, $except)) {
                return $case->value;
            }

            return null;
        })->filter()->values()->toArray();
    }
}

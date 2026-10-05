<?php

namespace Alliswell\Appyhp\Support;

class StudioMode
{
    public static function enabled(): bool
    {
        $mode = config('appyhp.mode');

        if (is_string($mode) && trim($mode) !== '') {
            return strtolower(trim($mode)) === 'dev';
        }

        return (bool) config('app.debug');
    }
}

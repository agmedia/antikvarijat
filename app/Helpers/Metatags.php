<?php

namespace App\Helpers;


class Metatags
{

    /**
     * @return string[]
     */
    public static function noFollow(): array
    {
        return [
            'name' => 'robots',
            'content' => 'noindex,nofollow'
        ];
    }
}

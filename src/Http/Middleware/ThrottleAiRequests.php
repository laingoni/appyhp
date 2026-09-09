<?php

namespace Alliswell\Appyhp\Http\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;

class ThrottleAiRequests extends ThrottleRequests
{
    public function __construct()
    {
        parent::__construct(new RateLimiter(Cache::store('file')));
    }
}

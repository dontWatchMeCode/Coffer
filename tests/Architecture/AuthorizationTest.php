<?php

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

arch('base controller uses AuthorizesRequests')
    ->expect('App\Http\Controllers\Controller')
    ->toUse(AuthorizesRequests::class);

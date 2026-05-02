<?php

arch('all app files use strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('controllers have Controller suffix')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toHaveSuffix('Controller');

arch('policies have Policy suffix')
    ->expect('App\Policies')
    ->classes()
    ->toHaveSuffix('Policy');

arch('form requests extend FormRequest')
    ->expect('App\Http\Requests')
    ->classes()
    ->toExtend('Illuminate\Foundation\Http\FormRequest');

arch('enums are enums')
    ->expect('App\Enums')
    ->toBeEnums();

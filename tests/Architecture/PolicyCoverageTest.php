<?php

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

it('has a policy for every model', function () {
    $modelsPath = dirname(__DIR__, 2).'/app/Models';

    $finder = new Finder;
    $finder->files()->in($modelsPath)->name('*.php')->depth(0);

    $ignored = [
        'Membership',
        'TeamInvitation',
        'User',
    ];

    foreach ($finder as $file) {
        $modelName = Str::before($file->getFilename(), '.php');
        $class = 'App\\Models\\'.$modelName;

        if (in_array($modelName, $ignored, true)) {
            continue;
        }

        $policyClass = 'App\\Policies\\'.$modelName.'Policy';

        expect(class_exists($policyClass))
            ->toBeTrue("Model [{$class}] is missing policy [{$policyClass}].");
    }
});

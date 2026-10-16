<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

function qaWorkflowRunWithFakes(?string $failOn = null): array
{
    $tmp = storage_path('framework/testing/qa-workflow-'.Str::uuid());
    mkdir($tmp.'/bin', 0777, true);
    $log = $tmp.'/calls.log';
    touch($log);

    foreach (['composer', 'npm', 'php'] as $cmd) {
        $failGuard = $failOn === null
            ? ''
            : "if [[ \" \$*\" == *\"{$failOn}\"* ]]; then echo \"fake failure for {$cmd} \$*\" >&2; exit 1; fi\n";

        file_put_contents(
            $tmp.'/bin/'.$cmd,
            "#!/usr/bin/env bash\necho \"{$cmd} \$*\" >> \"{$log}\"\n{$failGuard}exit 0\n"
        );
        chmod($tmp.'/bin/'.$cmd, 0755);
    }

    try {
        $process = new Process(
            ['bash', base_path('scripts/qa.sh')],
            base_path(),
            ['PATH' => $tmp.'/bin:'.getenv('PATH')]
        );
        $process->setTimeout(120);
        $process->run();

        $calls = file_exists($log)
            ? array_values(array_filter(array_map('trim', file($log, FILE_IGNORE_NEW_LINES) ?: [])))
            : [];

        return [
            'exitCode' => $process->getExitCode() ?? 1,
            'output' => $process->getOutput().$process->getErrorOutput(),
            'calls' => $calls,
        ];
    } finally {
        (new Filesystem)->deleteDirectory($tmp);
    }
}

test('qa runs each step once', function () {
    $result = qaWorkflowRunWithFakes();

    expect($result['exitCode'])->toBe(0, 'qa.sh failed: '.$result['output']);
    expect($result['calls'])->toBe([
        'composer run-script ide-helper',
        'composer run-script rector',
        'composer run-script lint',
        'composer run-script security',
        'npm run build',
        'npm run format',
        'npm run lint',
        'npm run types:check',
        'npm test',
        'composer run-script analyse',
        'php artisan config:clear --ansi',
        'php artisan test',
    ]);
});

test('qa stops on first failing step', function () {
    $result = qaWorkflowRunWithFakes('run-script rector');

    expect($result['exitCode'])->not->toBe(0);
    expect($result['output'])->toContain('rector FAILED');
    expect($result['calls'])->toBe([
        'composer run-script ide-helper',
        'composer run-script rector',
    ]);
});

test('qa-vvv stays deduplicated without changing standalone test', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);
    $qaVvv = implode("\n", $composer['scripts']['qa-vvv']);

    expect($qaVvv)->not->toContain('wayfinder:generate');
    expect($qaVvv)->not->toContain('lint:check');
    expect($qaVvv)->not->toContain('"@test"');
    expect($qaVvv)->toContain('npm test');
    expect($qaVvv)->toContain('artisan config:clear');
    expect($qaVvv)->toContain('artisan test');
    expect(implode("\n", $composer['scripts']['test']))->toContain('lint:check');
});

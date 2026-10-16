<?php

function runAuditGate(array $vulnerabilities): array
{
    $process = proc_open(
        ['node', 'scripts/npm-audit-gate.mjs'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        getcwd(),
        ['NPM_AUDIT_GATE_FIXTURE' => json_encode(['vulnerabilities' => $vulnerabilities])]
    );

    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    $exitCode = proc_close($process);

    return [$exitCode, $output];
}

test('npm audit gate passes when every advisory is allowlisted', function () {
    [$exitCode, $output] = runAuditGate([
        'braces' => [
            'severity' => 'high',
            'via' => [['url' => 'https://github.com/advisories/GHSA-vfj7-8cjw-p6xm', 'title' => 'braces DoS']],
        ],
        'sass' => ['severity' => 'high', 'via' => ['sass depends on braces']],
    ]);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('all allowlisted');
});

test('npm audit gate blocks advisories outside the allowlist', function () {
    [$exitCode, $output] = runAuditGate([
        'evil-pkg' => [
            'severity' => 'critical',
            'via' => [['url' => 'https://github.com/advisories/GHSA-fake-0000', 'title' => 'brand new RCE']],
        ],
    ]);

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('GHSA-fake-0000');
});

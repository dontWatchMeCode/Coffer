#!/usr/bin/env bash
set -euo pipefail

run_step() {
    local label="$1"
    shift
    local output
    if ! output=$("$@" 2>&1); then
        echo "--- $label FAILED ---"
        echo "$output"
        exit 1
    fi
}

check_no_eslint_disable() {
    local matches
    local status

    matches=$(grep -RIn \
        --include='*.ts' \
        --include='*.vue' \
        --exclude-dir='.git' \
        --exclude-dir='node_modules' \
        --exclude-dir='vendor' \
        'eslint-disable' . 2>&1) || status=$?

    if [[ ${status:-0} -gt 1 ]]; then
        echo 'Unable to check for eslint-disable comments.'
        echo "$matches"
        return 1
    fi

    if [[ -n $matches ]]; then
        echo 'eslint-disable comments are not allowed in TypeScript or Vue files:'
        echo "$matches"
        return 1
    fi
}

run_step "ide-helper" composer run-script ide-helper
run_step "rector" composer run-script rector
run_step "lint" composer run-script lint
run_step "wayfinder:generate" php artisan wayfinder:generate --ansi --with-form
run_step "npm build" npm run build
run_step "npm format" npm run format
run_step "eslint-disable check" check_no_eslint_disable
run_step "npm lint" npm run lint
run_step "npm types:check" npm run types:check
run_step "analyse" composer run-script analyse
run_step "test" composer run-script test

echo "✅  All QA checks passed."

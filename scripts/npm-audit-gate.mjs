import { spawnSync } from 'node:child_process';

// Gate for `npm audit --omit=dev`: fails on any advisory that is NOT in
// ALLOWLIST below. Advisories reach this list only when no compatible
// upstream fix exists; each entry documents why it is accepted. Any NEW
// advisory (new URL) fails the gate and therefore QA.
//
// Re-audit a listed entry when its package ships a fixed release.

const ALLOWLIST = new Map([
  // braces: stack-exhaustion DoS via deeply nested patterns. Build-time
  // globbing only (never ships to browsers, never runs on the server);
  // not reachable through the app.
  ['https://github.com/advisories/GHSA-vfj7-8cjw-p6xm', 'braces, build-time only'],
  // lodash-es: template import code injection + unset/omit prototype
  // pollution. Runs in the browser via Excalidraw/mermaid on diagram data
  // authored by the viewing user; no cross-user or server impact.
  ['https://github.com/advisories/GHSA-r5fr-rjxr-66jc', 'lodash-es, self-authored client data'],
  ['https://github.com/advisories/GHSA-f23m-r3pf-42rh', 'lodash-es, self-authored client data'],
  ['https://github.com/advisories/GHSA-xxjr-mmjv-4gpg', 'lodash-es, self-authored client data'],
  // nanoid: predictable IDs / endless loops only with non-integer,
  // negative, or zero sizes. Libraries call it with constants.
  ['https://github.com/advisories/GHSA-mwcw-c2x4-8c55', 'nanoid, constant sizes'],
  ['https://github.com/advisories/GHSA-28wg-ghj8-5hjv', 'nanoid, constant sizes'],
  ['https://github.com/advisories/GHSA-2v37-7h3g-55p8', 'nanoid, constant sizes'],
  ['https://github.com/advisories/GHSA-xwg4-73v4-xw9w', 'nanoid, constant sizes'],
]);

function collectAdvisoryUrls(audit) {
  const urls = new Map();

  for (const [name, info] of Object.entries(audit.vulnerabilities ?? {})) {
    for (const via of info.via ?? []) {
      if (typeof via === 'object' && via.url) {
        if (!urls.has(via.url)) {
          urls.set(via.url, { title: via.title ?? '', packages: [] });
        }

        urls.get(via.url).packages.push(name);
      }
    }
  }

  return urls;
}

function main() {
  let audit;

  if (process.env.NPM_AUDIT_GATE_FIXTURE) {
    audit = JSON.parse(process.env.NPM_AUDIT_GATE_FIXTURE);
  } else {
    // npm audit exits non-zero both when advisories are found and on real
    // errors, so the JSON report is read from stdout regardless of status.
    const result = spawnSync('npm', ['audit', '--omit=dev', '--json'], { encoding: 'utf8' });

    try {
      audit = JSON.parse(result.stdout || '');
    } catch {
      console.error(`npm audit failed to run: ${(result.stderr || result.error?.message || '').trim()}`);
      process.exit(1);
    }
  }

  const found = collectAdvisoryUrls(audit);
  const unknown = [...found.entries()].filter(([url]) => !ALLOWLIST.has(url));

  if (unknown.length > 0) {
    console.error('Blocking npm advisories (not in allowlist):');

    for (const [url, { title, packages }] of unknown) {
      console.error(`- ${url} | ${title} | via ${packages.join(', ')}`);
    }

    process.exit(1);
  }

  console.log(`npm audit gate passed: ${found.size} advisories, all allowlisted.`);
}

main();

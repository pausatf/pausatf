# Dependency compatibility

## Infrastructure tooling

`docs/requirements.txt` is the tracked Python tooling manifest. The October 1, 2026 merges set these constraints:

| Package | Constraint |
| --- | --- |
| Ansible | `>=12.2.0,<13.0.0` |
| ansible-lint | `>=26.9.0` |
| molecule-plugins with Docker extra | `>=26.7.15` |
| pytest-cov | `>=7.1.0` |
| pytest-ansible | `>=26.9.0` |
| mkdocs-mermaid2-plugin | `>=1.2.3` |

Select the runtime through mise. From the repository root, an isolated install is:

```bash
mise exec python@3.12.12 -- python -m venv .venv-docs
mise exec python@3.12.12 -- .venv-docs/bin/python -m pip install -r docs/requirements.txt
```

Combined resolution passed under Python 3.12.12 before merge, and the final manifest matched that candidate.
This was a pip dry run, not installed-suite validation. Open-ended constraints may resolve differently later.
Record actual installed versions and run the relevant suites before promoting a new environment.
CI and pre-commit use their own pinned tooling; changing this manifest does not change those pins.

## WordPress JavaScript tooling

The separate [results-manager](https://github.com/pausatf/pausatf-results-manager) and
[WordPress source](https://github.com/pausatf/pausatf-wordpress) repositories retain React/React DOM 18.3.1 and
ESLint 9.39.5 constraints. React 19 and ESLint 10 were incompatible with the reviewed dependency graphs.
Targeted Dependabot ignores cover only `19.x` for React/React DOM and `10.x` for ESLint; supported minor/patch
updates remain enabled. Revisit those ignores after the affected peer dependencies support the new majors.

Both packages scope an ESLint 9 override to `@wordpress/scripts`; do not replace it with a global override.
Use the repository's lockfile with `npm ci`, preserve normal peer checks, and select Node through mise.
The results package requires Node `>=22.22.2`; the WordPress core package permits
`^22.22.2 || ^24.15.0 || >=26.0.0`. npm 10 or newer is required by both package manifests.
Read each package's current scripts before running checks; the packages expose different build/test commands.

## Docker stacks

The monorepo's OpenLiteSpeed development stack uses MariaDB 13.0 with explicit automatic upgrade and TCP/InnoDB
readiness. Existing 11.8 volumes must follow the [backup and rollback procedure](../../scripts/docker/README.md).
Its monitoring stack pins Prometheus 3.15.0.
The separate [deployment repository](https://github.com/pausatf/pausatf-deployment) still pins local MySQL 8.0;
the production managed MySQL 8.4 target and local MariaDB upgrade do not change that separate stack.

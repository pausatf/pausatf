# Read-only CI access to production

GitHub Actions reaches prod (`pausatf-prod-v2`) through the
`ssh-v2.pausatf.org` Cloudflare Access app with a service token, as the
`github-deploy` user. That user's only key is forced to
`pausatf-ci-readonly`, which accepts one subcommand:

| Subcommand | Runs | Used by |
| --- | --- | --- |
| `wp-versions` | `wp-versions.php` in the `pausatf-wordpress` container as `www-data` | `wordpress-update-check.yml` |

The key has `restrict` set, so there is no shell, PTY or port forwarding.
`github-deploy` may run only `/usr/local/sbin/pausatf-wp-versions`, with no
arguments, through sudo. Prod has no outbound access to wordpress.org, so
`compare-wp-versions.sh` looks up the latest versions on the runner.

## Install or rotate

From this directory, with a new ed25519 key pair:

```bash
ssh-keygen -t ed25519 -N '' -C pausatf-ci-readonly -f ./cikey
tar -cf - pausatf-ci-readonly pausatf-wp-versions sudoers-pausatf-ci install.sh wp-versions.php |
  ssh pausatf-prod-v2 'd=$(mktemp -d) && tar -xf - -C "$d" && cd "$d" && sudo sh install.sh "'"$(cat cikey.pub)"'"'
gh secret set PROD_CI_SSH_KEY --repo pausatf/pausatf < cikey
```

`install.sh` replaces the key, so the old key stops working when it runs.
The Access service token (`github-actions-pausatf-ci-readonly`) expires
2027-11-20. Rotate it in Zero Trust and update `CF_ACCESS_CLIENT_ID` and
`CF_ACCESS_CLIENT_SECRET`.

## Adding a check

Add a case to `pausatf-ci-readonly` and a root helper that takes no arguments.
Keep every subcommand read-only. Anything that changes prod goes through the
reviewed deploy process instead.

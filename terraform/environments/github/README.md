# GitHub Environment

This environment manages the `pausatf/pausatf` repository using HCP Terraform
workspace `pausatf-github`.

## Managed resources

- Repository settings
- Default branch
- `main` branch protection
- Dependabot security updates
- Vulnerability alerts
- Topics, currently empty, as part of the `github_repository` resource

These existing settings were imported into the HCP workspace on 2026-09-27.
The live settings are also captured in [inventory.yml](inventory.yml), which
can be refreshed with `capture-inventory.sh`. A current Terraform plan reports
no changes against GitHub.

## Credentials and initialization

Set the GitHub provider token as a sensitive HCP workspace variable named
`github_token`. Use the minimum repository administration permissions needed
to read and manage this repository. Do not place tokens in source files or
committed tfvars.

```bash
cd terraform/environments/github
terraform login
terraform init
terraform validate
terraform plan
```

## Review before apply

The Terraform settings match the live repository, including squash-only merge,
auto-merge enabled, administrator enforcement on `main`, current CI contexts,
and zero required approving reviews. Refresh a plan before any change to these
settings because they affect pull request merge behavior.

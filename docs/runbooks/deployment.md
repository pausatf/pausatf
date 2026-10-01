# Deployment runbook

## Approval and target selection

Follow the [current-head review policy](../../CONTRIBUTING.md#review-process): CI must pass, actionable feedback
must be resolved, and the required maintainer decision must identify the reviewed revision.
Repository approval is separate from production authorization.

`deploy-prod.yml` queues on pushes to main, including docs changes, as well as manual dispatch.
It checks staging in Ansible check mode before invoking production deployment through `deploy.yml`.
This check is not evidence that the same production artifact was deployed and tested in staging.
Retain environment gates and cancel unintended runs while production is held.

Before execution, record the source commit, actual target, intended changes, maintenance window and rollback target.
Inspect the inventory and rendered variables before running a playbook.
`ansible/site.yml` provisions host Apache for production/dev and host OpenLiteSpeed for staging;
the separately managed Docker stack is owned by
pausatf-deployment (`pausatf/pausatf-deployment`, private repository).
Do not apply the host web-stack playbook to a Docker origin as though it manages that container stack.

## Infrastructure and managed database changes

Use the target environment's existing Terraform backend and authorized plan runner.
Review the plan at the exact source revision for replacement, unrelated changes and unexpected provider effects.
Apply only the reviewed plan after the authorized operational decision.
For production MySQL 8.4, complete the [compatibility, restore and HCP plan gates](database-upgrades.md).
Merging the target version does not execute the database upgrade.

Do not use a Terraform state rollback as a substitute for restoring infrastructure or data.
Reverting Git does not reverse an in-place database upgrade. Use the documented recovery path and verify it
in isolation before production execution; preserve the previous working artifacts.

## Application and server configuration

1. Confirm the target owner, current inventory and source revision.
2. Verify a fresh [encrypted backup](../../scripts/backup/README.md), every selected artifact dependency and an
   isolated restore. A backup success marker or snapshot alone is not complete application recovery evidence.
3. Review check-mode output and test the intended application/configuration changes in the appropriate staging path.
4. Deploy only after authorization, using the verified target runner and revision.
5. Verify public pages, login, database reads/writes, media, logs and service health. Record results and rollback status.

The monorepo local OpenLiteSpeed stack uses MariaDB 13.0. Before opening an existing 11.8 volume, complete its
[logical and physical recovery procedure](../../scripts/docker/README.md). This does not upgrade the separate
deployment repository's local MySQL 8.0 stack or the production managed database.

## TLS provisioning and renewal

Use the [TLS runbook](tls-certificates.md) to distinguish the public Cloudflare edge certificate from the origin.
Host Apache is the production Ansible default; separately managed Docker origins require an explicit container override.
Provisioning pauses automatic TLS jobs, stages the lineage, validates the configured web server and reloads it.
The deploy hook retains the first last-known-good snapshot across retries until validation and reload succeed.
Inspect the independent synchronization service journal and pending state after failure.
After forced controller termination, inspect migration state and rerun provisioning or restore the documented timers.

## Recovery and evidence

Record the revision, dependency/runtime versions, plan or deployment artifact, approver, backup manifest,
verification results and any untested steps. Restore with the
[disaster recovery](disaster-recovery.md) and component-specific runbooks, then verify the resulting application.
Never infer recovery from a successful upload or a reverted commit.
Escalate through the repository's issues and maintainer @somethingwithproof.

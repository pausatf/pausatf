# PAUSATF documentation

The infrastructure documentation is maintained in `pausatf/pausatf`. Start with the active runbooks below.
Configuration in Git describes intended behavior; verify installed services, current inventory and recovery evidence
before operating on a live environment. Dated audit reports and migration records describe their recorded period.

## Operations

| Document | Purpose |
| --- | --- |
| [Repository overview](../README.md) | Components, workflows and operational gates |
| [Operator runbook](../RUNBOOK.md) | Infrastructure and application operations |
| [Deployment](runbooks/deployment.md) | Review, validation, deployment and rollback |
| [Production operations](runbooks/production-operations.md) | Routine operations and escalation |
| [Disaster recovery](runbooks/disaster-recovery.md) | Recovery paths and validation |
| [Database upgrades](runbooks/database-upgrades.md) | MySQL 8.4 compatibility, restore and plan gates |
| [TLS certificates](runbooks/tls-certificates.md) | Edge/origin ownership, synchronization and renewal |
| [Encrypted backup](../scripts/backup/README.md) | Receipts, recovery manifests and retention |
| [Local database upgrade](../scripts/docker/README.md) | MariaDB 11.8 to 13.0 and isolated rollback |
| [Dependency compatibility](guides/dependency-compatibility.md) | Python tooling and WordPress JavaScript constraints |
| [Password rotation](procedures/database-password-rotation.md) | Managed database credential rotation |

## Components and collaboration

- [Terraform](../terraform/README.md), [Ansible](../ansible/README.md), [scripts](../scripts/README.md)
- [Contribution and review policy](../CONTRIBUTING.md#review-process)
- [GitHub workflows](governance/GITHUB-ACTIONS.md) and [testing](governance/TESTING.md)
- [Issues](https://github.com/pausatf/pausatf/issues) and [pull requests](https://github.com/pausatf/pausatf/pulls)
- Maintainer: @somethingwithproof

## Guides and historical records

The guides in `guides/` include cache, server migration, Cloudflare, email security, database maintenance and
DigitalOcean operations. Check each guide's date and compare its commands with the active runbooks before execution.
Reports in `reports/`, the [changelog](CHANGELOG.md), the [migration record](MIGRATION.md) and the
[upgrade roadmap](planning/08-recommended-upgrades-roadmap.md) preserve historical decisions and observations.
Their version, resource, cost and deployment claims are not current inventory verification.

## Historical guide and report index

### Guides and how-tos


**Step-by-step guides for infrastructure operations:**

| Guide | Description | Last Updated |
|-------|-------------|--------------|
| [01 - Cache Implementation](guides/01-cache-implementation-guide.md) | Complete cache fix implementation and technical details | Dec 20, 2025 |
| [05 - Server Migration](guides/05-server-migration-guide.md) | Complete 10-phase DigitalOcean migration process | Dec 20, 2025 |
| [06 - Cloudflare Configuration](guides/06-cloudflare-configuration-guide.md) | DNS, SSL, caching, firewall, API automation | Dec 20, 2025 |
| [09 - Google Workspace Email](guides/09-google-workspace-email-security.md) | SPF, DKIM, DMARC email security setup | Dec 20, 2025 |
| [10 - Operational Procedures](guides/10-operational-procedures.md) | Day-to-day operations, updates, backups, emergencies | Dec 21, 2025 |
| [11 - Database Maintenance](guides/11-database-maintenance.md) | Database procedures, primary keys, troubleshooting | Dec 21, 2025 |
| [13 - DigitalOcean Optimization](guides/13-digitalocean-optimization-guide.md) | Infrastructure improvements, security, monitoring, DR | Dec 21, 2025 |

### 📊 Reports & Audits

**Historical audits, assessments, and implementation summaries:**

| Report | Description | Date |
|--------|-------------|------|
| [02 - Cache Audit](reports/02-cache-audit-report.md) | Pre-fix audit of cache configurations | Dec 20, 2025 |
| [03 - Cache Verification](reports/03-cache-verification-report.md) | Production deployment verification | Dec 20, 2025 |
| [04 - Security Audit](reports/04-security-audit-report.md) | WordPress theme security assessment (9 findings) | Dec 20, 2025 |
| [07 - Performance Optimization](reports/07-performance-optimization-complete.md) | **Complete WordPress optimization (93% faster)** | Dec 20, 2025 |
| [12 - Server Rightsizing](reports/12-server-rightsizing-analysis.md) | Resource optimization and cost analysis | Dec 21, 2025 |
| [14 - WordPress Security Audit](reports/14-wordpress-security-audit-2025.md) | **WordPress security audit and remediation** | Dec 21, 2025 |
| [15 - Theme Repository Workflows](reports/15-theme-repository-github-workflows.md) | **GitHub workflows for WordPress theme repos** | Dec 21, 2025 |
| [Phase 1 Implementation](reports/PHASE1-IMPLEMENTATION-REPORT.md) | Phase 1 security improvements summary | Dec 21, 2025 |
| [IaC Updates](reports/INFRASTRUCTURE-AS-CODE-UPDATES.md) | Terraform/Ansible configurations | Dec 21, 2025 |

### 🗺️ Planning & Roadmaps

**Future roadmaps and strategic planning:**

| Document | Description |
|----------|-------------|
| [08 - Upgrade Roadmap](planning/08-recommended-upgrades-roadmap.md) | PHP 8.3, Ubuntu 24.04, security hardening |

## License

Internal documentation for pausatf.org infrastructure. Not for public distribution.

**Maintained by:** Thomas Vincent (@somethingwithproof)
**Organization:** Pacific Association of USA Track and Field (PAUSATF)

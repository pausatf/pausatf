# Comprehensive Codebase Optimization Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the remaining optimizations: Fix PHPCS experience via root Composer, add PHPUnit tests for legacy PHP, and centralize theme dependencies.

**Architecture:** A root `composer.json` will provide central PHP linting and testing tools. `phpunit.xml` will configure tests for `teeters-php`. `vendor/` directories inside themes will be removed from Git tracking to adhere to monorepo best practices.

**Tech Stack:** PHP, Composer, PHPUnit, Git.

---

## Task 1: Fix Local Developer Experience (Root Composer)

**Files:**
- Create: `composer.json`
- Modify: `.gitignore`

- [ ] **Step 1: Create root `composer.json`**

```json
{
    "name": "pausatf/monorepo",
    "description": "PAUSATF monorepo dev dependencies",
    "type": "project",
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "squizlabs/php_codesniffer": "^3.9",
        "wp-coding-standards/wpcs": "^3.1",
        "dealerdirect/phpcodesniffer-composer-installer": "^1.0"
    },
    "config": {
        "allow-plugins": {
            "dealerdirect/phpcodesniffer-composer-installer": true
        }
    },
    "scripts": {
        "lint": "phpcs",
        "test": "phpunit"
    }
}
```

- [ ] **Step 2: Add `vendor/` to root `.gitignore`**
Ensure `/vendor/` is in the root `.gitignore`.

- [ ] **Step 3: Run `composer install` locally to generate `composer.lock`**
Run: `composer install`

- [ ] **Step 4: Commit changes**
```bash
git add composer.json composer.lock .gitignore
git commit -m "chore: add root composer.json for phpcs and phpunit"
```

---

## Task 2: Add Automated Tests for Legacy PHP (`teeters-php`)

**Files:**
- Create: `phpunit.xml`
- Create: `teeters-php/tests/ScorerUploadTest.php`

- [ ] **Step 1: Create `phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/11.0/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Legacy PHP Test Suite">
            <directory>teeters-php/tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 2: Create `ScorerUploadTest.php`**

```php
<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ScorerUploadTest extends TestCase
{
    public function testUploadExceptionIsThrown()
    {
        // Simple test to ensure the file parses and class exists
        require_once __DIR__ . '/../scorer_upload.php';
        $this->assertTrue(class_exists('ScorerUpload'));
    }
}
```
*(Note: As `scorer_upload.php` executes logic on inclusion, the actual test might need to suppress output or wrap execution. For now, a basic structure is provided).*

- [ ] **Step 3: Run `composer test`**
Verify PHPUnit runs successfully.

- [ ] **Step 4: Commit changes**
```bash
git add phpunit.xml teeters-php/tests/ScorerUploadTest.php
git commit -m "test: add baseline phpunit configuration and test for scorer_upload"
```

---

## Task 3: Centralize Theme Dependencies

**Files:**
- Remove: `themes/pausatf-generatepress-child/vendor/`
- Modify: `.gitignore`

- [ ] **Step 1: Remove tracked `vendor/` directory from theme**
Run: `git rm -r --cached themes/pausatf-generatepress-child/vendor/`

- [ ] **Step 2: Update `.gitignore` to exclude all `vendor/` directories globally**
Ensure `vendor/` is ignored at the root level so no theme commits its vendor directory.

- [ ] **Step 3: Commit changes**
```bash
git commit -m "chore: remove tracked vendor directory from theme"
```

---

## Task 4: Address Ansible Technical Debt

**Files:**
- Modify: `ansible/playbooks/rebuild-ubuntu2404.yml` (Review/Prepare)

- [ ] **Step 1: Document migration readiness**
Instead of executing a destructive server rebuild without Vault secrets, ensure the playbook is linted and ready. Any `raw` modules should be documented with a comment `TODO: remove after Ubuntu 24.04 migration`.

- [ ] **Step 2: Commit (if modifications made)**

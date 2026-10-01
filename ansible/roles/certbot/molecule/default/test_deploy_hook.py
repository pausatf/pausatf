"""Exercise the rendered Docker deploy hook with controlled command fixtures."""
import os
from pathlib import Path
import re
import shutil
import shlex
import subprocess
import sys
import tempfile
import unittest

HOOK = Path(sys.argv.pop(1)).read_text()


class DeployHookTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name)
        self.lineage = self.root / 'lineage'
        self.target = self.root / 'target'
        self.bin = self.root / 'bin'
        self.lineage.mkdir()
        self.bin.mkdir()
        for name in ('fullchain.pem', 'privkey.pem'):
            (self.lineage / name).write_text('new-' + name)
        self.hook = self.root / 'hook.sh'
        script = re.sub(r'^expected_lineage=.*$', f'expected_lineage="{self.lineage}"', HOOK, flags=re.M)
        script = re.sub(r'^target=.*$', f'target="{self.target}"', script, flags=re.M)
        self.hook.write_text(script)
        # Strip root ownership only for unprivileged local runs; retain real install modes.
        install = shutil.which('install')
        self.command('install', '#!/usr/bin/env python3\nimport os,sys\na=sys.argv[1:]\n'
                     'for flag in ("-o", "-g"):\n'
                     '    if flag in a:\n        i=a.index(flag); del a[i:i+2]\n'
                     f'os.execv({install!r}, [{install!r}]+a)\n')
        self.command('docker', '#!/bin/sh\nprintf "%s\\n" "$*" >> "$DOCKER_LOG"\n'
                     'case "$*" in\n'
                     '  *"container ls -a"*) [ "${FAIL_LIST:-0}" = 0 ] || exit 1; '
                     '[ "${NO_CONTAINER:-0}" = 1 ] || echo "$CONTAINER_NAME" ;;\n'
                     '  *configtest) [ "${FAIL_CONFIG:-0}" = 0 ] ;;\n'
                     '  *graceful) [ "${FAIL_RELOAD:-0}" = 0 ] ;;\n'
                     '  *) exit 2 ;;\nesac\n')
        if not shutil.which('flock'):
            self.command('flock', '#!/bin/sh\nexit 0\n')
        self.env = dict(os.environ, PATH=str(self.bin) + os.pathsep + os.environ['PATH'],
                        RENEWED_LINEAGE=str(self.lineage), DOCKER_LOG=str(self.root / 'docker.log'),
                        CONTAINER_NAME=shlex.split(re.search(r'docker exec (.*?) apache2ctl', HOOK)[1])[0])

    def command(self, name, content):
        p = self.bin / name
        p.write_text(content)
        p.chmod(0o755)

    def run_hook(self, **env):
        return subprocess.run(['sh', str(self.hook)], env=dict(self.env, **env), capture_output=True)

    def seed(self):
        self.target.mkdir()
        for name in ('fullchain.pem', 'privkey.pem'):
            (self.target / name).write_text('old-' + name)

    def assert_current(self, prefix):
        for name in ('fullchain.pem', 'privkey.pem'):
            self.assertEqual((self.target / name).read_text(), prefix + name)

    def test_initial_deployment_and_permissions(self):
        result = self.run_hook()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assert_current('new-')
        self.assertEqual((self.target / 'privkey.pem').stat().st_mode & 0o777, 0o600)
        self.assertIn('configtest', (self.root / 'docker.log').read_text())
        self.assertIn('graceful', (self.root / 'docker.log').read_text())

    def test_config_failure_rolls_back_and_independent_retry_succeeds(self):
        self.seed()
        self.assertNotEqual(self.run_hook(FAIL_CONFIG='1').returncode, 0)
        self.assert_current('old-')
        self.assertNotIn('graceful', (self.root / 'docker.log').read_text())
        self.assertEqual(self.run_hook().returncode, 0)
        self.assert_current('new-')

    def test_reload_failure_rolls_back_and_retries(self):
        self.seed()
        self.assertNotEqual(self.run_hook(FAIL_RELOAD='1').returncode, 0)
        self.assert_current('old-')
        self.assertEqual(self.run_hook().returncode, 0)
        self.assert_current('new-')

    def test_failed_initial_deployment_leaves_no_active_files(self):
        self.assertNotEqual(self.run_hook(FAIL_CONFIG='1').returncode, 0)
        self.assertFalse((self.target / 'fullchain.pem').exists())
        self.assertFalse((self.target / 'privkey.pem').exists())
        self.assertEqual(self.run_hook().returncode, 0)

    def test_unchanged_certificate_does_not_reload(self):
        self.assertEqual(self.run_hook().returncode, 0)
        before = (self.root / 'docker.log').read_text()
        self.assertEqual(self.run_hook().returncode, 0)
        self.assertEqual((self.root / 'docker.log').read_text(), before)

    def test_unrelated_lineage_is_ignored(self):
        self.assertEqual(self.run_hook(RENEWED_LINEAGE='/unrelated').returncode, 0)
        self.assertFalse(self.target.exists())
        self.assertFalse((self.root / 'docker.log').exists())

    def test_fresh_host_stages_mount_until_container_exists(self):
        result = self.run_hook(NO_CONTAINER='1')
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assert_current('new-')
        self.assertTrue((self.target / '.reload-pending').exists())
        self.assertNotIn('configtest', (self.root / 'docker.log').read_text())
        self.assertEqual(self.run_hook().returncode, 0)
        self.assertIn('configtest', (self.root / 'docker.log').read_text())
        self.assertFalse((self.target / '.reload-pending').exists())

    def test_docker_query_failure_is_not_treated_as_absent_container(self):
        self.seed()
        self.assertNotEqual(self.run_hook(FAIL_LIST='1').returncode, 0)
        self.assert_current('old-')


if __name__ == '__main__':
    unittest.main()

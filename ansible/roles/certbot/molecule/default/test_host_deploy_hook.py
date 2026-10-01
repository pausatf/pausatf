import importlib.util
import os
from pathlib import Path
import sys
import unittest
spec=importlib.util.spec_from_file_location('container_tests', sys.argv.pop(1))
module=importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
class HostHookTests(module.DeployHookTests):
    def check_repeated_staging_rollback(self, failure):
        self.seed()
        self.assertEqual(self.run_hook(CERTBOT_STAGE_ONLY='1').returncode, 0)
        for name in ('fullchain.pem', 'privkey.pem'):
            (self.lineage / name).write_text('newer-' + name)
        self.assertEqual(self.run_hook(CERTBOT_STAGE_ONLY='1').returncode, 0)
        self.assert_current('newer-')
        self.assertNotEqual(self.run_hook(**{failure: '1'}).returncode, 0)
        self.assert_current('old-')
        self.assertTrue((self.target / '.last-known-good').is_dir())
        self.assertEqual(self.run_hook().returncode, 0)
        self.assert_current('newer-')
        self.assertFalse((self.target / '.last-known-good').exists())
        self.assertFalse((self.target / '.reload-pending').exists())

    def test_repeated_staging_preserves_last_good_on_config_failure(self):
        self.check_repeated_staging_rollback('FAIL_CONFIG')

    def test_repeated_staging_preserves_last_good_on_reload_failure(self):
        self.check_repeated_staging_rollback('FAIL_RELOAD')

    def test_active_host_stages_before_invalid_vhost_migration(self):
        self.assertEqual(self.run_hook(CERTBOT_STAGE_ONLY='1', FAIL_CONFIG='1').returncode, 0)
        self.assert_current('new-')
        self.assertTrue((self.target / '.reload-pending').exists())
        self.assertFalse((self.root / 'docker.log').exists())
        # An interrupted playbook leaves the installed hook in normal mode:
        # a timer/renewal invocation without the staging environment validates.
        self.assertNotEqual(self.run_hook(FAIL_CONFIG='1').returncode, 0)
        self.assertTrue((self.target / '.reload-pending').exists())
        self.assertEqual(self.run_hook().returncode, 0)
        self.assertFalse((self.target / '.reload-pending').exists())

    def setUp(self):
        # Parent fixture obtains a container name; the host hook needs no Docker.
        original=module.HOOK
        module.HOOK += '\n# docker exec fixture apache2ctl\n'
        try:
            super().setUp()
        finally:
            module.HOOK=original
        self.command('apache2ctl', '#!/bin/sh\necho "$*" >> "$DOCKER_LOG"\n[ "${FAIL_CONFIG:-0}" = 0 ]\n')
        self.command('systemctl', '#!/bin/sh\necho "$*" >> "$DOCKER_LOG"\ncase "$1" in\nis-active) [ "${NO_CONTAINER:-0}" = 0 ] ;;\nreload) echo graceful >> "$DOCKER_LOG"; [ "${FAIL_RELOAD:-0}" = 0 ] ;;\n*) exit 2 ;;\nesac\n')
    def test_docker_query_failure_is_not_treated_as_absent_container(self):
        result=self.run_hook(FAIL_LIST='1')
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assert_current('new-')
if __name__ == '__main__':
    unittest.main()

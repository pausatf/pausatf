import importlib.util
import os
import re
from pathlib import Path
import sys
import unittest
spec=importlib.util.spec_from_file_location('container_tests', sys.argv.pop(1))
module=importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
class HostHookTests(module.DeployHookTests):
    def test_active_host_stages_before_invalid_vhost_migration(self):
        original = self.hook.read_text()
        self.hook.write_text(re.sub(r'^stage_only=.*$', 'stage_only=1', original, flags=re.M))
        self.assertEqual(self.run_hook(FAIL_CONFIG='1').returncode, 0)
        self.assert_current('new-')
        self.assertTrue((self.target / '.reload-pending').exists())
        self.assertFalse((self.root / 'docker.log').exists())
        self.hook.write_text(original)
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

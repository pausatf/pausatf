"""Keep legacy source bytes intact while comparing PHP coding-standard findings."""
import importlib.util
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

spec = importlib.util.spec_from_file_location(
    'phpcs_check', Path(__file__).with_name('check_phpcs_changed_lines.py'))
checker = importlib.util.module_from_spec(spec)
spec.loader.exec_module(checker)


class LegacyEncodingTests(unittest.TestCase):
    def setUp(self):
        self.directory = tempfile.TemporaryDirectory()
        self.addCleanup(self.directory.cleanup)
        self.root = Path(self.directory.name)
        self.git('init', '-q')
        self.original = b'<?php\n// Copyright \xa92008\n'
        (self.root / 'legacy.php').write_bytes(self.original)
        self.commit('original fixture')
        self.base = self.git('rev-parse', 'HEAD').stdout.decode().strip()
        (self.root / 'legacy.php').write_bytes(b'<?php\n// Copyright &copy;2008\n')
        self.commit('escaped fixture')

    def git(self, *args):
        # The temporary fixture repo must not invoke the operator's hooks or signing.
        return subprocess.run(
            ['git', '-c', 'core.hooksPath=/dev/null', '-c', 'commit.gpgsign=false',
             '-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid', *args],
            cwd=self.root, capture_output=True, check=True)

    def commit(self, message):
        self.git('add', 'legacy.php')
        self.git('commit', '-q', '-s', '-m', message)

    def test_changed_line_ranges_accept_legacy_bytes_in_git_diff(self):
        self.assertEqual(checker.changed_lines(self.base, self.root),
                         {'legacy.php': [(2, 1, 2, 1)]})

    def test_baseline_scanner_receives_exact_original_source_bytes(self):
        executable = self.root / 'phpcs-fixture'
        report = {'files': {'STDIN': {'messages': [
            {'line': 2, 'source': 'Fixture.Copyright', 'message': 'Existing warning'}]}}}
        executable.write_text(
            f'#!{sys.executable}\nimport json, sys\n'
            f'expected = bytes.fromhex({self.original.hex()!r})\n'
            'if sys.stdin.buffer.read() != expected:\n    sys.exit(2)\n'
            f'print({json.dumps(report)!r})\n')
        executable.chmod(0o700)
        self.assertEqual(checker.baseline_violations(
            self.base, ['legacy.php'], str(executable), self.root),
            {'legacy.php': {(2, 'Fixture.Copyright', 'Existing warning'): 1}})

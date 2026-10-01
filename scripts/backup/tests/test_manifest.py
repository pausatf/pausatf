"""Exercise embedded manifest generation without production hosts or credentials."""
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]


def embedded_python(script):
    return (ROOT / script).read_text().split("python3 - <<'PY'\n", 1)[1].split('\nPY\n', 1)[0]


class ManifestTests(unittest.TestCase):
    def test_exact_invocation_artifacts_survive_slow_image_backup(self):
        with tempfile.TemporaryDirectory() as directory:
            work = Path(directory)
            database = work / 'prod-db-invocation.sql.gz.age'
            legacy = work / 'prod-legacy-invocation.tar.gz.age'
            database.write_bytes(b'encrypted database fixture')
            legacy.write_bytes(b'encrypted legacy fixture')
            for p in (database, legacy):
                os.utime(p, (1, 1))  # Much older than the removed one-hour cutoff.
            (work / 'prod-db-unrelated.sql.gz.age').write_bytes(b'newer unrelated run')
            receipt = work / 'database-receipt.json'
            env = dict(os.environ, DB_OUT=str(database), LEGACY_OUT=str(legacy),
                       DEST='s3://fixture/prod', BACKUP_RECEIPT=str(receipt))
            subprocess.run([sys.executable, '-c', embedded_python('pausatf-db-backup.sh')],
                           env=env, check=True)
            expected = json.loads(receipt.read_text())
            self.assertEqual(expected['database']['uri'], 's3://fixture/prod/' + database.name)
            self.assertEqual(expected['database']['sha256'], hashlib.sha256(database.read_bytes()).hexdigest())
            for name in ('deployment.tar.gz.age', 'host-config.tar.gz.age'):
                (work / name).write_bytes(b'encrypted fixture')
            (work / 'images.txt').write_text('fixture sha256:fixture s3://fixture/images/image.age\n')
            env.update(work=directory, stamp='fixture', DEST='s3://fixture/recovery')
            subprocess.run([sys.executable, '-c', embedded_python('pausatf-recovery-backup.sh')],
                           env=env, check=True)
            manifest = json.loads((work / 'manifest.json').read_text())
            for kind in ('database', 'legacy'):
                self.assertEqual(manifest['artifacts'][kind], expected[kind])

    def test_incomplete_database_receipt_fails_closed(self):
        with tempfile.TemporaryDirectory() as directory:
            (Path(directory) / 'database-receipt.json').write_text('{"database": {}}')
            result = subprocess.run([sys.executable, '-c', embedded_python('pausatf-recovery-backup.sh')],
                                    env=dict(os.environ, work=directory), capture_output=True)
            self.assertNotEqual(result.returncode, 0)
            self.assertFalse((Path(directory) / 'manifest.json').exists())


if __name__ == '__main__':
    unittest.main()

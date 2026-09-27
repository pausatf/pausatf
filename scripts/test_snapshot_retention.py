import importlib.util
from pathlib import Path
import unittest

spec = importlib.util.spec_from_file_location("retention", Path(__file__).with_name("snapshot-retention.py"))
retention = importlib.util.module_from_spec(spec)
spec.loader.exec_module(retention)


class RetentionTests(unittest.TestCase):
    def snapshot(self, day, owner="123", name=None, resource_type="droplet"):
        return {"id": str(day), "resource_id": owner, "resource_type": resource_type,
                "name": name or f"prod-nightly-202609{day:02d}-100000",
                "created_at": f"2026-09-{day:02d}T10:00:00Z"}

    def test_preserves_other_droplets_manual_snapshots_and_volumes(self):
        rows = [self.snapshot(d) for d in range(1, 10)]
        rows += [self.snapshot(1, owner="456"), self.snapshot(1, name="manual-backup"),
                 self.snapshot(1, resource_type="volume")]
        self.assertEqual(retention.candidates(rows, "123", rows[8]["name"]), ["2", "1"])

    def test_missing_replacement_fails_closed(self):
        with self.assertRaises(ValueError):
            retention.candidates([self.snapshot(1)], "123", "missing")

    def test_small_inventory_is_preserved(self):
        row = self.snapshot(1)
        self.assertEqual(retention.candidates([row], "123", row["name"]), [])

    def test_zero_retention_is_rejected(self):
        with self.assertRaises(ValueError):
            retention.candidates([], "123", "missing", 0)


if __name__ == "__main__":
    unittest.main()

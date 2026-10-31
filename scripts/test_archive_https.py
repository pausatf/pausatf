"""Execute historical analytics selectors under both document protocols."""
import json
from pathlib import Path
import re
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]
SELECTOR = re.compile(rb'var gaJsHost\s*=\s*[^\r\n;]*;')
RUNNER = r'''
const vm = require('node:vm');
const selectors = JSON.parse(require('node:fs').readFileSync(0, 'utf8'));
let checks = 0;
for (const { path, source } of selectors) {
  for (const protocol of ['http:', 'https:']) {
    const context = { document: { location: { protocol } } };
    vm.runInNewContext(source, context, { timeout: 100 });
    const resource = new URL(context.gaJsHost + 'google-analytics.com/ga.js');
    if (resource.protocol !== 'https:' || resource.pathname !== '/ga.js' ||
        !['www.google-analytics.com', 'ssl.google-analytics.com'].includes(resource.hostname)) {
      throw new Error(path + ': unsafe or changed analytics destination');
    }
    checks++;
  }
}
process.stdout.write(JSON.stringify({ selectors: selectors.length, checks }));
'''


class ArchiveHttpsTests(unittest.TestCase):
    def test_shipped_selectors_require_https_for_http_and_https_documents(self):
        selectors = []
        for path in sorted((ROOT / 'content').rglob('*')):
            if path.is_file() and not path.is_symlink():
                data = path.read_bytes()
                if b'google-analytics.com/ga.js' in data:
                    selectors.extend({'path': str(path.relative_to(ROOT)), 'source': item.decode('ascii')}
                                     for item in SELECTOR.findall(data))
        self.assertGreater(len(selectors), 2000, 'Archive files must be present')
        result = subprocess.run(['node', '-e', RUNNER], input=json.dumps(selectors),
                                text=True, capture_output=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        evidence = json.loads(result.stdout)
        self.assertEqual(evidence['selectors'], len(selectors))
        self.assertEqual(evidence['checks'], len(selectors) * 2)

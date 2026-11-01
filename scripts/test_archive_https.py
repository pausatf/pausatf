"""Execute shared and retained archive telemetry without contacting third parties."""
import collections
from html.parser import HTMLParser
import json
import os
from pathlib import Path
import re
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]
ASSET = '/data/pausatf-legacy-analytics.js'
SELECTOR = re.compile(rb'var gaJsHost\s*=\s*[^\r\n;]*;')
LEGACY_PATHS = {
    'content/data/2008/XC5Kmastersnationals.html',
    'content/data/2008/ldrminutes9.14.08.html',
    'content/data/LDRGuide.html',
    'content/data/rwgpstandingsarchives.html',
    'content/data/rwrules.html',
    'content/data/2008/XCEMP2008.html',
    'content/data/2008/mtf0708news.html',
    'content/data/news.html',
    'content/data/2001/XCHOY01.html',
    'content/data/2000/SearchShore.html',
    'content/data/2008/officials/test.html',
    'content/data/pabylaws.html',
}
RUNNER = r'''
const fs = require('node:fs');
const vm = require('node:vm');
const input = JSON.parse(fs.readFileSync(0, 'utf8'));
const shared = new vm.Script(input.shared);
const allowed = new Set([
    'https://edge.quantserve.com/quant.js',
    'https://www.google-analytics.com/ga.js',
    'https://ssl.google-analytics.com/ga.js'
]);
function execute(provider, protocol, fail = false, dynamic = null) {
    const requests = [];
    const calls = [];
    const win = { location: { protocol } };
    const owner = { getAttribute: () => provider };
    const doc = {
        currentScript: owner,
        createElement: name => {
            if (name !== 'script') throw new Error('Unexpected DOM element');
            const attrs = {};
            return { handlers: {},
                setAttribute: (key, value) => { attrs[key] = value; },
                getAttribute: key => attrs[key] ?? null,
                addEventListener(type, callback) { this.handlers[type] = callback; }
            };
        },
        head: { appendChild(node) {
            if (node.src === input.asset) {
                doc.currentScript = node;
                shared.runInContext(context, { timeout: 100 });
                return node;
            }
            if (!allowed.has(node.src)) throw new Error('Unexpected SDK URL: ' + node.src);
            requests.push(node.src);
            if (!node.async) throw new Error('SDK blocks the document parser');
            if (fail) return node;
            if (node.src.includes('quantserve')) {
                win.quantserve = () => calls.push(['quantcast', win._qacct]);
                if (provider === 'quantcast-options') calls.push(['quantcast-options', win._qoptions.qacct]);
            } else {
                win._gat = { _getTracker: id => {
                    calls.push(['tracker', id]);
                    return { _initData: () => calls.push(['init']),
                        _trackPageview: () => calls.push(['pageview']) };
                }};
            }
            if (node.handlers.load) node.handlers.load();
            return node;
        }}
    };
    const context = vm.createContext({ document: doc, window: win });
    if (dynamic) new vm.Script(dynamic).runInContext(context, { timeout: 100 });
    else shared.runInContext(context, { timeout: 100 });
    return { requests, calls };
}
let checks = 0;
for (const item of input.references) {
    for (const protocol of ['http:', 'https:']) {
        const result = execute(item.provider, protocol);
        if (result.requests.length !== 1) throw new Error(item.path + ': missing SDK request');
        let expected;
        switch (item.provider) {
            case 'google': expected = [['tracker', 'UA-2651783-1'], ['init'], ['pageview']]; break;
            case 'google-pageview': expected = [['tracker', 'UA-2651783-1'], ['pageview']]; break;
            case 'google-library': expected = []; break;
            case 'quantcast': expected = [['quantcast', 'p-72cL-7RSrrmhE']]; break;
            case 'quantcast-options': expected = [['quantcast-options', 'p-72cL-7RSrrmhE']]; break;
            default: throw new Error('Unknown archive provider');
        }
        if (JSON.stringify(result.calls) !== JSON.stringify(expected)) {
            throw new Error(item.path + ': changed telemetry calls');
        }
        checks++;
    }
}
for (const { path, source } of input.legacy) {
    for (const protocol of ['http:', 'https:']) {
        const context = { document: { location: { protocol } } };
        vm.runInNewContext(source, context, { timeout: 100 });
        const url = new URL(context.gaJsHost + 'google-analytics.com/ga.js');
        if (url.protocol !== 'https:' || url.pathname !== '/ga.js' ||
            !['www.google-analytics.com', 'ssl.google-analytics.com'].includes(url.hostname)) {
            throw new Error(path + ': insecure retained SDK URL');
        }
        checks++;
    }
}
for (const provider of ['google', 'google-pageview', 'google-library', 'quantcast', 'quantcast-options']) {
    const failed = execute(provider, 'https:', true);
    if (failed.requests.length !== 1 || failed.calls.length !== 0) throw new Error('Failed SDK reported success');
}
for (const provider of [null, '', 'https://example.invalid/payload.js', 'google<script>']) {
    const rejected = execute(provider, 'https:');
    if (rejected.requests.length || rejected.calls.length) throw new Error('Unrecognized provider executed');
}
const dynamic = execute('google', 'https:', false, input.dynamic);
if (JSON.stringify(dynamic.calls) !== JSON.stringify([['tracker', 'UA-2651783-1'], ['init'], ['pageview']])) {
    throw new Error('Standalone JavaScript import lost its tracker');
}
process.stdout.write(JSON.stringify({ checks }));
'''


class References(HTMLParser):
    def __init__(self):
        super().__init__()
        self.providers = []

    def handle_starttag(self, name, attrs):
        attrs = dict(attrs)
        if name == 'script' and attrs.get('src') == ASSET:
            self.providers.append(attrs.get('data-provider'))


class ArchiveHttpsTests(unittest.TestCase):
    def test_shipped_tracking_uses_shared_https_loader_or_verified_legacy_variant(self):
        indexed = subprocess.run(['git', 'ls-files', '-z', '--', 'content'], cwd=ROOT,
                                 capture_output=True, check=True)
        paths = [ROOT / os.fsdecode(name) for name in indexed.stdout.split(b'\0') if name]
        missing = [str(path.relative_to(ROOT)) for path in paths
                   if not path.exists() and not path.is_symlink()]
        self.assertEqual(len(missing), 0,
                         f'Complete tracked archive checkout required; missing examples: {missing[:5]}')
        shared = ROOT / 'content' / ASSET.lstrip('/')
        self.assertIn(shared, paths, 'The shared loader must be shipped with the archive')
        references = []
        legacy = []
        retained = set()
        for path in sorted(paths):
            if not path.is_file() or path.is_symlink():
                continue
            data = path.read_bytes()
            relative = str(path.relative_to(ROOT))
            if ASSET.encode() in data and path.suffix != '.js':
                parser = References()
                parser.feed(data.decode('latin1'))
                references.extend({'path': relative, 'provider': provider}
                                  for provider in parser.providers)
            selectors = SELECTOR.findall(data)
            if selectors:
                retained.add(relative)
                legacy.extend({'path': relative, 'source': source.decode('ascii')} for source in selectors)
        self.assertEqual(retained, LEGACY_PATHS)
        counts = collections.Counter(item['provider'] for item in references)
        # Characterized from the original SDK calls, including load-only and no-init variants.
        self.assertEqual(counts, {'google': 2558, 'google-pageview': 80, 'google-library': 4,
                                  'quantcast': 2519, 'quantcast-options': 64})
        self.assertEqual(len(legacy), 19)
        dynamic_source = (ROOT / 'content/indexyouth.js').read_text()
        start = dynamic_source.index('const legacyAnalytics = document.createElement("script");')
        end = dynamic_source.index('document.head.appendChild(legacyAnalytics);', start)
        dynamic = dynamic_source[start:end + len('document.head.appendChild(legacyAnalytics);')]
        references.append({'path': 'content/indexyouth.js', 'provider': 'google'})
        payload = {'asset': ASSET, 'shared': shared.read_text(), 'references': references,
                   'legacy': legacy, 'dynamic': dynamic}
        result = subprocess.run(['node', '-e', RUNNER], input=json.dumps(payload),
                                text=True, capture_output=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(json.loads(result.stdout)['checks'], 2 * (len(references) + len(legacy)))

import contextlib
import importlib.util
import io
import json
import os
from pathlib import Path
import unittest
from unittest.mock import patch

spec = importlib.util.spec_from_file_location('firewall', Path(__file__).with_name('firewall-maintenance.py'))
firewall = importlib.util.module_from_spec(spec)
spec.loader.exec_module(firewall)


class CloudflareFetchTests(unittest.TestCase):
    def run_refresh(self, v4=b'173.245.48.0/20\n', v6=b'2400:cb00::/32\n', apply=True,
                    environment=None):
        calls, bodies = [], []
        def urlopen(req, timeout):
            if isinstance(req, str):
                family = req.rsplit('-', 1)[-1]
                calls.append('cloudflare-' + family)
                return contextlib.closing(io.BytesIO(v4 if family == 'v4' else v6))
            ident = req.full_url.rsplit('/', 1)[-1]
            name, droplet = firewall.TARGETS[ident]
            calls.append(req.method + ' ' + name)
            if req.method == 'PUT':
                bodies.append(json.loads(req.data))
                return contextlib.closing(io.BytesIO(b''))
            rules = [{'protocol': 'tcp', 'ports': '22', 'sources': {'addresses': ['1.1.1.1/32']}}]
            if 'stage' not in name:
                rules.append({'protocol': 'tcp', 'ports': '443', 'sources': {'addresses': ['1.1.1.1/32']}})
            data = {'firewall': {'name': name, 'droplet_ids': [droplet], 'tags': [],
                'inbound_rules': rules, 'outbound_rules': []}}
            return contextlib.closing(io.BytesIO(json.dumps(data).encode()))
        if environment is None:
            environment = {'DIGITALOCEAN_ACCESS_TOKEN': 'fixture-token'}
        error = io.StringIO()
        argv = ['tool', '--cloudflare'] + (['--apply'] if apply else [])
        code = 0
        with patch.dict(os.environ, environment, clear=True), patch('sys.argv', argv), \
             patch.object(firewall.urllib.request, 'urlopen', urlopen), \
             contextlib.redirect_stdout(io.StringIO()), contextlib.redirect_stderr(error):
            try:
                firewall.main()
            except SystemExit as exc:
                code = exc.code
        return calls, bodies, code, error.getvalue()

    def test_fetch_validate_then_apply_both_families(self):
        calls, bodies, code, error = self.run_refresh()
        self.assertEqual(code, 0, error)
        self.assertEqual(calls[:2], ['cloudflare-v4', 'cloudflare-v6'])
        self.assertTrue(all(c.startswith('GET ') for c in calls[2:5]))
        self.assertEqual(calls[5:], ['PUT pausatf-prod-v2-fw', 'PUT pausatf-nonprod-cf-lock'])
        for body in bodies:
            self.assertEqual(body['inbound_rules'][1]['sources']['addresses'],
                             ['173.245.48.0/20', '2400:cb00::/32'])
            self.assertEqual(body['inbound_rules'][0]['sources']['addresses'], ['1.1.1.1/32'])

    def test_dry_run_fetches_without_put(self):
        calls, bodies, code, error = self.run_refresh(apply=False)
        self.assertEqual(code, 0, error)
        self.assertFalse(bodies)
        self.assertFalse(any(c.startswith('PUT ') for c in calls))

    def test_malformed_cloudflare_response_never_writes(self):
        calls, bodies, code, error = self.run_refresh(v4=b'<html>Error</html>')
        self.assertEqual(code, 1)
        self.assertFalse(bodies)
        self.assertNotIn('fixture-token', error)

    def test_missing_family_never_writes(self):
        calls, bodies, code, error = self.run_refresh(v6=b'')
        self.assertEqual(code, 1)
        self.assertFalse(bodies)

    def test_missing_or_empty_token_never_contacts_network(self):
        for environment in [{}, {'DIGITALOCEAN_ACCESS_TOKEN': ''}]:
            calls, bodies, code, error = self.run_refresh(environment=environment)
            self.assertEqual(code, 1)
            self.assertEqual(calls, [])
            self.assertEqual(bodies, [])


if __name__ == '__main__':
    unittest.main()

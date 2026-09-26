import importlib.util
from pathlib import Path
import unittest
spec = importlib.util.spec_from_file_location('firewall', Path(__file__).with_name('firewall-maintenance.py'))
firewall = importlib.util.module_from_spec(spec)
spec.loader.exec_module(firewall)

class FirewallTests(unittest.TestCase):
    def setUp(self):
        self.current = {'name': 'test', 'droplet_ids': [123], 'tags': [],
            'inbound_rules': [{'protocol': 'tcp', 'ports': '22', 'sources': {'addresses': ['1.1.1.1/32']}}],
            'outbound_rules': [{'protocol': 'tcp', 'ports': '1-65535', 'destinations': {'addresses': ['0.0.0.0/0']}}]}
    def test_tunnel_web_ports_stay_closed(self):
        result = firewall.plan(self.current, ('test', 123), ranges=['173.245.48.0/20', '2400:cb00::/32'])
        self.assertEqual(result, self.current)
    def test_ssh_add_is_nondestructive(self):
        result = firewall.plan(self.current, ('test', 123), ssh_ip='8.8.8.8')
        self.assertEqual(result['inbound_rules'][0]['sources']['addresses'], ['1.1.1.1/32', '8.8.8.8/32'])
        self.assertEqual(result['outbound_rules'], self.current['outbound_rules'])
        self.assertEqual(firewall.plan(result, ('test', 123), ssh_ip='8.8.8.8'), result)
    def test_rejects_shell_input_and_cidr(self):
        for value in ['$(id)', '0.0.0.0/0', '127.0.0.1']:
            with self.assertRaises(ValueError):
                firewall.plan(self.current, ('test', 123), ssh_ip=value)
    def test_rejects_drift_and_empty_ranges(self):
        with self.assertRaises(ValueError):
            firewall.plan(self.current, ('test', 999), ssh_ip='8.8.8.8')
        with self.assertRaises(ValueError):
            firewall.plan(self.current, ('test', 123), ranges=[])
    def test_updates_only_existing_web_rules(self):
        self.current['inbound_rules'].append({'protocol': 'tcp', 'ports': '443', 'sources': {'addresses': ['1.1.1.1/32']}})
        result = firewall.plan(self.current, ('test', 123), ranges=['173.245.48.0/20', '2400:cb00::/32'])
        self.assertEqual(result['inbound_rules'][0], self.current['inbound_rules'][0])
        self.assertEqual(result['inbound_rules'][1]['sources']['addresses'], ['173.245.48.0/20', '2400:cb00::/32'])


class MainTests(unittest.TestCase):
    def run_main(self, apply=False, fail_second=False):
        import contextlib
        import io
        import os
        from unittest.mock import patch
        calls = []
        expected = [('a', 1), ('b', 2)]
        def urlopen(req, timeout):
            calls.append(req.method)
            if req.method == 'PUT':
                if fail_second and calls.count('PUT') == 2:
                    raise OSError('simulated network failure')
                payload = {}
            else:
                name, ident = expected[calls.count('GET') - 1]
                payload = {'firewall': {'name': name, 'droplet_ids': [ident], 'tags': [],
                    'inbound_rules': [{'protocol': 'tcp', 'ports': '22', 'sources': {'addresses': []}}],
                    'outbound_rules': []}}
            return contextlib.closing(io.BytesIO(firewall.json.dumps(payload).encode()))
        stderr = io.StringIO()
        argv = ['tool', '--ssh-ip', '8.8.8.8'] + (['--apply'] if apply else [])
        with patch.dict(os.environ, {'DIGITALOCEAN_ACCESS_TOKEN': 'fixture-token'}), \
             patch.object(firewall, 'TARGETS', {'1': expected[0], '2': expected[1]}), \
             patch('sys.argv', argv), patch.object(firewall.urllib.request, 'urlopen', urlopen), \
             contextlib.redirect_stderr(stderr), contextlib.redirect_stdout(io.StringIO()):
            try:
                firewall.main()
            except SystemExit as exc:
                return calls, stderr.getvalue(), exc.code
        return calls, stderr.getvalue(), 0

    def test_dry_run_never_writes(self):
        self.assertEqual(self.run_main()[0], ['GET', 'GET'])

    def test_apply_validates_before_writing(self):
        self.assertEqual(self.run_main(apply=True)[0], ['GET', 'GET', 'PUT', 'PUT'])

    def test_partial_failure_reports_confirmed_updates(self):
        calls, error, code = self.run_main(apply=True, fail_second=True)
        self.assertEqual(code, 1)
        self.assertIn('stopped at b', error)
        self.assertIn('Confirmed updates: a', error)
        self.assertNotIn('fixture-token', error)

if __name__ == '__main__':
    unittest.main()

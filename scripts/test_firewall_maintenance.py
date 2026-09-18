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

if __name__ == '__main__':
    unittest.main()

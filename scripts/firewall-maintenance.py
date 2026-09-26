#!/usr/bin/env python3
"""Update only existing web rules, or append one validated SSH source."""
import argparse
import copy
import ipaddress
import json
import os
import urllib.request
import urllib.error

TARGETS = {
    '5fb8b83b-12bb-4256-8ffc-ccf275f55a7e': ('pausatf-prod-v2-fw', 586316413),
    'b17da40d-aac5-48d0-8553-160a77212e82': ('pausatf-stage-v2-tunnel', 586535212),
    '96d0d607-9071-4a7c-8c61-10614453f98c': ('pausatf-nonprod-cf-lock', 586535214),
}


def plan(firewall, expected, ranges=None, ssh_ip=None):
    name, droplet = expected
    if firewall['name'] != name or firewall.get('droplet_ids') != [droplet]:
        raise ValueError('Firewall ownership drift; refusing update')
    body = {k: copy.deepcopy(firewall[k]) for k in
            ('name', 'inbound_rules', 'outbound_rules', 'droplet_ids', 'tags')}
    if ranges is not None:
        networks = [ipaddress.ip_network(r) for r in ranges]
        if not networks or {n.version for n in networks} != {4, 6}:
            raise ValueError('Both Cloudflare address families are required')
        if any(not n.is_global or n.prefixlen < (8 if n.version == 4 else 16) for n in networks):
            raise ValueError('Unsafe Cloudflare ranges')
        for rule in body['inbound_rules']:
            if rule['protocol'] == 'tcp' and rule['ports'] in ('80', '443'):
                if set(rule['sources']) != {'addresses'}:
                    raise ValueError('Unexpected web source types; refusing update')
                rule['sources']['addresses'] = sorted(str(n) for n in networks)
    if ssh_ip is not None:
        addr = ipaddress.ip_address(ssh_ip)
        if not addr.is_global:
            raise ValueError('A public host IP is required')
        cidr = f'{addr}/{addr.max_prefixlen}'
        ssh_rules = [r for r in body['inbound_rules'] if r['protocol'] == 'tcp' and r['ports'] == '22']
        if not ssh_rules:
            raise ValueError('No existing SSH rule; refusing update')
        if not any(cidr in r['sources'].get('addresses', []) for r in ssh_rules):
            ssh_rules[0]['sources'].setdefault('addresses', []).append(cidr)
    return body


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--ssh-ip')
    parser.add_argument('--cloudflare', action='store_true')
    parser.add_argument('--apply', action='store_true')
    args = parser.parse_args()
    if args.cloudflare == bool(args.ssh_ip):
        parser.error('Choose exactly one operation')
    applied = []
    target = 'initial validation'
    try:
        token = os.environ['DIGITALOCEAN_ACCESS_TOKEN']
        if not token:
            raise ValueError('DigitalOcean token is empty')
        ranges = None
        if args.cloudflare:
            ranges = []
            for family in ('v4', 'v6'):
                with urllib.request.urlopen(f'https://www.cloudflare.com/ips-{family}', timeout=30) as response:
                    ranges.extend(response.read().decode().split())
        def request(path, body=None):
            req = urllib.request.Request('https://api.digitalocean.com/v2/' + path,
                data=json.dumps(body).encode() if body is not None else None,
                method='PUT' if body is not None else 'GET',
                headers={'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json'})
            with urllib.request.urlopen(req, timeout=30) as response:
                data = response.read()
                return json.loads(data) if data else {}
        # Validate every target before making any changes.
        changes = []
        for ident, expected in TARGETS.items():
            target = expected[0]
            firewall = request('firewalls/' + ident)['firewall']
            body = plan(firewall, expected, ranges=ranges, ssh_ip=args.ssh_ip)
            original = {k: firewall[k] for k in body}
            if body != original:
                changes.append((ident, body))
        for ident, body in changes:
            target = body['name']
            print(('Updating' if args.apply else 'Would update'), target)
            if args.apply:
                request('firewalls/' + ident, body)
                applied.append(target)
        print(f'{len(changes)} firewall(s) need changes')
    except (KeyError, ValueError, TypeError, OSError, urllib.error.URLError) as exc:
        parser.exit(1, f'Firewall maintenance stopped at {target}: {exc}. '
                       f'Confirmed updates: {", ".join(applied) or "none"}. '
                       'A failed PUT may have reached the API; inspect before retrying.\n')


if __name__ == '__main__':
    main()

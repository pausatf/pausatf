#!/bin/sh
# Installs the read-only CI access on prod. Run as root from this directory:
#   sudo sh install.sh "<ssh-ed25519 public key>"
set -eu
pubkey="$1"
id github-deploy >/dev/null 2>&1 || useradd --system --create-home --shell /bin/sh github-deploy
passwd -l github-deploy >/dev/null
install -o root -g root -m 0755 pausatf-ci-readonly pausatf-wp-versions /usr/local/sbin/
install -o root -g root -m 0755 -d /usr/local/lib/pausatf-ci
install -o root -g root -m 0644 wp-versions.php /usr/local/lib/pausatf-ci/
visudo -cf sudoers-pausatf-ci >/dev/null
install -o root -g root -m 0440 sudoers-pausatf-ci /etc/sudoers.d/pausatf-ci
install -o github-deploy -g github-deploy -m 0700 -d ~github-deploy/.ssh
printf 'restrict,command="/usr/local/sbin/pausatf-ci-readonly" %s\n' "$pubkey" > ~github-deploy/.ssh/authorized_keys
chown github-deploy:github-deploy ~github-deploy/.ssh/authorized_keys
chmod 0600 ~github-deploy/.ssh/authorized_keys
echo "installed"

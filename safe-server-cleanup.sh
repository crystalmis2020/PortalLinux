#!/usr/bin/env bash
set -euo pipefail

if [[ ${EUID} -ne 0 ]]; then
    echo "Run this script with: sudo bash $0"
    exit 1
fi

echo "Checking protected support portal services..."
for service in apache2 mysql; do
    if ! systemctl is-active --quiet "$service"; then
        echo "ABORT: protected service '$service' is not running."
        exit 1
    fi
done

if ! curl -kfsS -o /dev/null https://127.0.0.1/support/; then
    echo "ABORT: the support portal health check failed."
    exit 1
fi

echo "Stopping the broken, unrelated MicroK8s restart loop..."
systemctl disable --now snap.microk8s.daemon-k8s-dqlite.service
systemctl disable --now snap.microk8s.daemon-apiserver-kicker.service

echo "Rotating and limiting system logs..."
journalctl --rotate
journalctl --vacuum-size=500M
logrotate -f /etc/logrotate.conf

echo "Cleaning downloaded OS package files..."
apt-get clean

echo "Rechecking the support portal..."
systemctl is-active --quiet apache2
systemctl is-active --quiet mysql
curl -kfsS -o /dev/null https://127.0.0.1/support/

echo "Cleanup complete."
df -h /

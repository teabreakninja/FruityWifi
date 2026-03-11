#!/bin/bash

# Modernized install.sh for FruityWifi karma module
# Updated from original (2016) to work on current Raspberry Pi OS / Debian bookworm

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "installing Hostapd/Karma Dependencies..."

# Install current build tools (gcc-4.7 no longer exists)
apt-get -y install gcc g++ make

# Install current netlink and SSL libraries
# libnl1/libnl-dev replaced by libnl-3-dev + libnl-genl-3-dev on modern Debian/Ubuntu
apt-get -y install libnl-3-dev libnl-genl-3-dev libssl-dev

# Install hostapd (required for hostapd_cli binary)
apt-get -y install hostapd

echo "installing Hostapd/Karma..."

# Download hostapd-karma source
wget https://github.com/xtr4nge/hostapd-karma/archive/master.zip -O hostapd-karma.zip

unzip -o hostapd-karma.zip

# Modern systems always need libnl3 config
echo "--------------------------------"
echo "ADDING: CONFIG_LIBNL32=y and libnl3 CFLAGS (required on modern Debian/Pi OS)"
echo "--------------------------------"

EXEC="s,^#CFLAGS += -I/usr/include/libnl3,CFLAGS += -I/usr/include/libnl3,g"
sed -i "$EXEC" hostapd-karma-master/hostapd/.config

EXEC="s,^#CONFIG_LIBNL32=y,CONFIG_LIBNL32=y,g"
sed -i "$EXEC" hostapd-karma-master/hostapd/.config

echo "[config updated]"

cd hostapd-karma-master/hostapd
make

cp hostapd "$SCRIPT_DIR/../../"
cp hostapd_cli "$SCRIPT_DIR/../../"

echo "..DONE.."

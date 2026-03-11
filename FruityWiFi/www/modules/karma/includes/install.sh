#!/bin/bash

# Modernized install.sh for FruityWifi karma module
# Uses hostapd-mana (maintained successor to hostapd-karma)
# https://github.com/sensepost/hostapd-mana

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "Installing Hostapd/Mana (karma) dependencies..."

apt-get -y install gcc g++ make wget unzip pkg-config \
    libnl-3-dev libnl-genl-3-dev \
    libssl-dev \
    libdbus-1-dev \
    libnl-route-3-dev

echo "Downloading hostapd-mana source..."

wget https://github.com/sensepost/hostapd-mana/archive/refs/heads/master.zip \
    -O hostapd-mana.zip

unzip -o hostapd-mana.zip

BUILD_DIR="$SCRIPT_DIR/hostapd-mana-master/hostapd"
cd "$BUILD_DIR"

# Copy the defconfig to .config if not already present
if [ ! -f .config ]; then
    cp defconfig .config
fi

# Ensure libnl-3 is used (required on modern Debian/Pi OS)
sed -i 's/^#CONFIG_LIBNL32=y/CONFIG_LIBNL32=y/' .config
sed -i 's/^#CFLAGS += -I\/usr\/include\/libnl3/CFLAGS += -I\/usr\/include\/libnl3/' .config

# Enable mana/karma features
sed -i 's/^#CONFIG_WPS=y/CONFIG_WPS=y/' .config
sed -i 's/^#CONFIG_MANA=y/CONFIG_MANA=y/' .config 2>/dev/null || true

echo "Building hostapd-mana..."
make -j$(nproc)

echo "Copying binaries to module includes directory..."
cp hostapd "$SCRIPT_DIR/"
cp hostapd_cli "$SCRIPT_DIR/"

echo "..DONE.."

#!/bin/sh
set -eu

private_key="$(security find-generic-password -a "$USER" -s ieor-golden-jubilee-private-key -w)"
export_token="$(security find-generic-password -a "$USER" -s ieor-golden-jubilee-export-token -w)"
GJ_PRIVATE_KEY_B64="$private_key" GJ_EXPORT_TOKEN="$export_token" \
  php "$(dirname "$0")/export-data.php" "$@"
unset private_key export_token


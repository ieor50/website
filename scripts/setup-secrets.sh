#!/bin/sh
set -eu

secret_dir="${HOME}/.private/gj.env"
secret_file="${secret_dir}/secrets.env"
service_private="ieor-golden-jubilee-private-key"
service_export="ieor-golden-jubilee-export-token"

umask 077
mkdir -p "$secret_dir"
chmod 700 "$secret_dir"

if [ ! -f "$secret_file" ]; then
  SECRET_FILE="$secret_file" php -r '
    if (!extension_loaded("sodium")) {
      fwrite(STDERR, "PHP Sodium is required.\n");
      exit(1);
    }
    $pair = sodium_crypto_box_keypair();
    $private = base64_encode(sodium_crypto_box_secretkey($pair));
    $public = base64_encode(sodium_crypto_box_publickey($pair));
    $token = bin2hex(random_bytes(32));
    $body = "GJ_PRIVATE_KEY_B64=" . $private . "\n"
          . "GJ_PUBLIC_KEY_B64=" . $public . "\n"
          . "GJ_EXPORT_TOKEN=" . $token . "\n";
    if (file_put_contents(getenv("SECRET_FILE"), $body, LOCK_EX) === false) exit(1);
    sodium_memzero($private);
    sodium_memzero($token);
  '
fi

chmod 600 "$secret_file"
set -a
. "$secret_file"
set +a

security add-generic-password -U -a "$USER" -s "$service_private" -w "$GJ_PRIVATE_KEY_B64" >/dev/null
security add-generic-password -U -a "$USER" -s "$service_export" -w "$GJ_EXPORT_TOKEN" >/dev/null

printf 'GJ_PUBLIC_KEY_B64=%s\n' "$GJ_PUBLIC_KEY_B64"
php -r 'echo "GJ_EXPORT_TOKEN_HASH=" . hash("sha256", getenv("GJ_EXPORT_TOKEN")) . PHP_EOL;'
printf 'Secrets saved to Keychain and %s\n' "$secret_file"


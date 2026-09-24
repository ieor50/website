<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

const GJ_RECORD_GUARD = "<?php exit; ?>\n";

function gj_data_dir(): string {
  $override = getenv('GJ_DATA_DIR');
  return $override !== false && $override !== '' ? $override : dirname(__DIR__) . '/data';
}

function gj_collection_dir(string $collection): string {
  if (!preg_match('/^[a-z][a-z0-9-]{0,31}$/', $collection)) {
    throw new InvalidArgumentException('Invalid collection.');
  }
  return gj_data_dir() . '/' . $collection;
}

function gj_ensure_directory(string $directory): void {
  if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
    throw new RuntimeException('Could not create the data directory.');
  }
  @chmod($directory, 0750);
}

function gj_public_key(): string {
  $decoded = base64_decode(GJ_PUBLIC_KEY_B64, true);
  if ($decoded === false || strlen($decoded) !== SODIUM_CRYPTO_BOX_PUBLICKEYBYTES) {
    throw new RuntimeException('The encryption public key is not configured.');
  }
  return $decoded;
}

function gj_store_encrypted(string $collection, array $record): string {
  if (!extension_loaded('sodium')) {
    throw new RuntimeException('PHP Sodium is required.');
  }

  $directory = gj_collection_dir($collection);
  gj_ensure_directory($directory);

  $plain = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
  $cipher = sodium_crypto_box_seal($plain, gj_public_key());
  sodium_memzero($plain);

  $id = gmdate('Ymd\THis') . 'Z-' . bin2hex(random_bytes(12));
  $envelope = [
    'version' => 1,
    'algorithm' => 'sodium-sealed-box',
    'collection' => $collection,
    'id' => $id,
    'ciphertext' => base64_encode($cipher),
  ];
  sodium_memzero($cipher);

  $path = $directory . '/record-' . $id . '.php';
  $body = GJ_RECORD_GUARD . json_encode($envelope, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
  if (@file_put_contents($path, $body, LOCK_EX) === false) {
    throw new RuntimeException('Could not write the encrypted record.');
  }
  @chmod($path, 0640);
  return $id;
}

function gj_read_envelopes(): array {
  $records = [];
  foreach (['subscriptions', 'registrations'] as $collection) {
    foreach (glob(gj_collection_dir($collection) . '/record-*.php') ?: [] as $path) {
      $raw = @file_get_contents($path);
      if ($raw === false || !str_starts_with($raw, GJ_RECORD_GUARD)) continue;
      $envelope = json_decode(substr($raw, strlen(GJ_RECORD_GUARD)), true);
      if (is_array($envelope) && isset($envelope['ciphertext'], $envelope['id'])) {
        $records[] = $envelope;
      }
    }
  }
  usort($records, fn(array $a, array $b): int => strcmp((string)$a['id'], (string)$b['id']));
  return $records;
}


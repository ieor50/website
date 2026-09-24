<?php

declare(strict_types=1);

function fail(string $message): never {
  fwrite(STDERR, $message . PHP_EOL);
  exit(1);
}

$baseUrl = rtrim($argv[1] ?? 'https://www.ieor.iitb.ac.in/golden-jubilee', '/');
$outputDirectory = $argv[2] ?? getcwd() . '/private-export';
$privateB64 = getenv('GJ_PRIVATE_KEY_B64') ?: '';
$token = getenv('GJ_EXPORT_TOKEN') ?: '';
if ($privateB64 === '' || $token === '') fail('Load GJ_PRIVATE_KEY_B64 and GJ_EXPORT_TOKEN before running this command.');

$private = base64_decode($privateB64, true);
if ($private === false || strlen($private) !== SODIUM_CRYPTO_BOX_SECRETKEYBYTES) fail('The private key is invalid.');
$public = sodium_crypto_scalarmult_base($private);
$pair = sodium_crypto_box_keypair_from_secretkey_and_publickey($private, $public);

$context = stream_context_create(['http' => [
  'method' => 'GET',
  'header' => "X-Golden-Jubilee-Export-Token: {$token}\r\nAccept: application/json\r\n",
  'timeout' => 60,
  'ignore_errors' => true,
]]);
$raw = @file_get_contents($baseUrl . '/api/export.php', false, $context);
$status = $http_response_header[0] ?? '';
if ($raw === false || !str_contains($status, ' 200 ')) fail('Encrypted export failed: ' . $status);
$export = json_decode($raw, true);
if (!is_array($export) || !is_array($export['records'] ?? null)) fail('The export response is invalid.');

$records = ['subscriptions' => [], 'registrations' => []];
foreach ($export['records'] as $envelope) {
  $collection = $envelope['collection'] ?? '';
  if (!isset($records[$collection])) fail('Unknown collection in export.');
  $cipher = base64_decode((string)($envelope['ciphertext'] ?? ''), true);
  if ($cipher === false) fail('Invalid ciphertext in export.');
  $plain = sodium_crypto_box_seal_open($cipher, $pair);
  if ($plain === false) fail('A record could not be decrypted.');
  $record = json_decode($plain, true);
  sodium_memzero($plain);
  if (!is_array($record)) fail('A decrypted record is invalid.');
  $records[$collection][] = $record;
}

if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0700, true) && !is_dir($outputDirectory)) {
  fail('Could not create the output directory.');
}
chmod($outputDirectory, 0700);
$stamp = gmdate('Y-m-d-His');

$subscriptionPath = $outputDirectory . '/subscriptions-' . $stamp . '.csv';
$subscription = fopen($subscriptionPath, 'wb');
fputcsv($subscription, ['submitted_at', 'email', 'batch'], ',', '"', '');
foreach ($records['subscriptions'] as $record) {
  fputcsv($subscription, [$record['submitted_at'] ?? '', $record['email'] ?? '', $record['batch'] ?? ''], ',', '"', '');
}
fclose($subscription);
chmod($subscriptionPath, 0600);

$registrationPath = $outputDirectory . '/registrations-' . $stamp . '.json';
file_put_contents($registrationPath, json_encode($records['registrations'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX);
chmod($registrationPath, 0600);

sodium_memzero($private);
sodium_memzero($token);
fwrite(STDOUT, "Wrote " . count($records['subscriptions']) . " subscriptions and " . count($records['registrations']) . " registrations.\n");
fwrite(STDOUT, $subscriptionPath . PHP_EOL . $registrationPath . PHP_EOL);

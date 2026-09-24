<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/storage.php';

function gj_json_response(int $status, array $body): never {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function gj_require_post(): void {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    gj_json_response(405, ['error' => 'Method not allowed.']);
  }
}

function gj_read_json_body(): array {
  $raw = file_get_contents('php://input');
  if ($raw === false || strlen($raw) > GJ_MAX_BODY_BYTES) {
    gj_json_response(413, ['error' => 'That submission is larger than we can accept.']);
  }
  $body = json_decode($raw, true);
  if (!is_array($body)) {
    gj_json_response(400, ['error' => 'The submission could not be read.']);
  }
  return $body;
}

function gj_client_ip(): string {
  return (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function gj_rate_limited(string $scope): bool {
  $directory = gj_data_dir() . '/rate-limit';
  gj_ensure_directory($directory);
  $fingerprint = hash('sha256', $scope . "\0" . gj_client_ip());
  $path = $directory . '/rate-' . substr($fingerprint, 0, 24) . '.php';
  $now = time();

  $handle = @fopen($path, 'c+');
  if ($handle === false) return false;
  if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    return false;
  }

  $raw = stream_get_contents($handle);
  $state = ['start' => $now, 'count' => 0];
  if (is_string($raw) && str_starts_with($raw, GJ_RECORD_GUARD)) {
    $previous = json_decode(substr($raw, strlen(GJ_RECORD_GUARD)), true);
    if (is_array($previous) && isset($previous['start'], $previous['count']) && $now - (int)$previous['start'] < GJ_RATE_WINDOW_SECONDS) {
      $state = ['start' => (int)$previous['start'], 'count' => (int)$previous['count']];
    }
  }

  $limited = $state['count'] >= GJ_RATE_LIMIT;
  if (!$limited) $state['count']++;
  ftruncate($handle, 0);
  rewind($handle);
  fwrite($handle, GJ_RECORD_GUARD . json_encode($state));
  fflush($handle);
  flock($handle, LOCK_UN);
  fclose($handle);
  @chmod($path, 0640);
  return $limited;
}

function gj_clean_string(mixed $value, int $limit): string {
  $value = is_string($value) ? trim($value) : '';
  if (mb_strlen($value) > $limit) {
    throw new InvalidArgumentException('One of the submitted fields is too long.');
  }
  return $value;
}

function gj_export_authorized(): bool {
  $direct = trim((string)($_SERVER['HTTP_X_GOLDEN_JUBILEE_EXPORT_TOKEN'] ?? ''));
  if ($direct !== '') {
    return hash_equals(GJ_EXPORT_TOKEN_HASH, hash('sha256', $direct));
  }
  $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
  if (!preg_match('/^Bearer\s+(.+)$/i', $header, $match)) return false;
  return hash_equals(GJ_EXPORT_TOKEN_HASH, hash('sha256', trim($match[1])));
}

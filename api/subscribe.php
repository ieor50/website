<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/http.php';

gj_require_post();
$body = gj_read_json_body();

try {
  $email = strtolower(gj_clean_string($body['email'] ?? null, 254));
  $batch = gj_clean_string($body['batch'] ?? null, 4);
} catch (InvalidArgumentException) {
  gj_json_response(400, ['error' => 'One of the submitted fields is too long.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  gj_json_response(400, ['error' => 'That email does not look right.']);
}
if ($batch !== '' && !preg_match('/^(19|20)\d{2}$/', $batch)) {
  gj_json_response(400, ['error' => 'Batch year should be four digits.']);
}
if (gj_rate_limited('subscriptions')) {
  header('Retry-After: 3600');
  gj_json_response(429, ['error' => 'Too many submissions from this connection. Please try again later.']);
}

try {
  gj_store_encrypted('subscriptions', [
    'schema' => 'golden-jubilee.subscription.v1',
    'submitted_at' => gmdate('c'),
    'email' => $email,
    'batch' => $batch === '' ? null : $batch,
  ]);
} catch (Throwable $error) {
  error_log('golden-jubilee subscription storage failed: ' . $error->getMessage());
  gj_json_response(503, ['error' => 'We could not save that just now.']);
}

gj_json_response(201, ['ok' => true]);


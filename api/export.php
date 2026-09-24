<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/http.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
  header('Allow: GET');
  gj_json_response(405, ['error' => 'Method not allowed.']);
}
if (!gj_export_authorized()) {
  gj_json_response(403, ['error' => 'Forbidden.']);
}

try {
  $records = gj_read_envelopes();
} catch (Throwable $error) {
  error_log('golden-jubilee export failed: ' . $error->getMessage());
  gj_json_response(503, ['error' => 'The encrypted export is unavailable.']);
}

header('Content-Disposition: attachment; filename="golden-jubilee-encrypted-' . gmdate('Y-m-d') . '.json"');
gj_json_response(200, [
  'version' => 1,
  'generated_at' => gmdate('c'),
  'records' => $records,
]);


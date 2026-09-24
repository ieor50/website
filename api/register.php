<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/http.php';

gj_require_post();
$body = gj_read_json_body();

try {
  $name = gj_clean_string($body['name'] ?? null, 120);
  $email = strtolower(gj_clean_string($body['email'] ?? null, 254));
  $phone = gj_clean_string($body['phone'] ?? null, 40);
  $batch = gj_clean_string($body['batch'] ?? null, 4);
  $degree = gj_clean_string($body['degree'] ?? null, 40);
  $organisation = gj_clean_string($body['organisation'] ?? null, 160);
  $days = gj_clean_string($body['days'] ?? null, 32);
  $city = gj_clean_string($body['city'] ?? null, 100);
  $stay = gj_clean_string($body['stay'] ?? null, 80);
  $accessibility = gj_clean_string($body['accessibility'] ?? null, 1000);
} catch (InvalidArgumentException) {
  gj_json_response(400, ['error' => 'One of the submitted fields is too long.']);
}

if ($name === '' || $phone === '' || $degree === '') {
  gj_json_response(400, ['error' => 'Please complete every required field.']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  gj_json_response(400, ['error' => 'That email does not look right.']);
}
if (!preg_match('/^(19|20)\d{2}$/', $batch)) {
  gj_json_response(400, ['error' => 'Graduation year should be four digits.']);
}
if (!in_array($degree, ['B.Tech', 'M.Tech', 'M.Sc.', 'Ph.D.', 'Other'], true)) {
  gj_json_response(400, ['error' => 'Please select a valid programme.']);
}
if (!in_array($days, ['Both days', '21 November', '22 November'], true)) {
  gj_json_response(400, ['error' => 'Please select the days you will attend.']);
}
if (!in_array($stay, ['Not required', 'I may need accommodation', 'I will arrange my own stay'], true)) {
  gj_json_response(400, ['error' => 'Please select a valid accommodation option.']);
}
if (($body['consent'] ?? null) !== true) {
  gj_json_response(400, ['error' => 'Please confirm the registration consent.']);
}

$rawGuests = $body['guests'] ?? [];
if (!is_array($rawGuests) || count($rawGuests) > 12) {
  gj_json_response(400, ['error' => 'A registration can include at most twelve guests.']);
}
$guests = [];
foreach ($rawGuests as $guest) {
  if (!is_array($guest)) gj_json_response(400, ['error' => 'One guest record could not be read.']);
  try {
    $guestName = gj_clean_string($guest['name'] ?? null, 120);
    $relationship = gj_clean_string($guest['relationship'] ?? null, 40);
    $age = gj_clean_string($guest['age'] ?? null, 40);
    $guestDays = gj_clean_string($guest['days'] ?? null, 40);
  } catch (InvalidArgumentException) {
    gj_json_response(400, ['error' => 'One guest field is too long.']);
  }
  if ($guestName === '' || $relationship === '' || $age === '' || $guestDays === '') {
    gj_json_response(400, ['error' => 'Please complete every guest field.']);
  }
  $guests[] = ['name' => $guestName, 'relationship' => $relationship, 'age_group' => $age, 'days' => $guestDays];
}

if (gj_rate_limited('registrations')) {
  header('Retry-After: 3600');
  gj_json_response(429, ['error' => 'Too many submissions from this connection. Please try again later.']);
}

try {
  gj_store_encrypted('registrations', [
    'schema' => 'golden-jubilee.registration.v1',
    'submitted_at' => gmdate('c'),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'graduation_year' => $batch,
    'degree' => $degree,
    'organisation' => $organisation === '' ? null : $organisation,
    'attendance' => $days,
    'travelling_from' => $city === '' ? null : $city,
    'accommodation' => $stay,
    'requirements' => $accessibility === '' ? null : $accessibility,
    'guests' => $guests,
  ]);
} catch (Throwable $error) {
  error_log('golden-jubilee registration storage failed: ' . $error->getMessage());
  gj_json_response(503, ['error' => 'We could not save that just now.']);
}

gj_json_response(201, ['ok' => true]);


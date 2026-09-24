<?php

declare(strict_types=1);

// Filled by scripts/setup-secrets.sh. The public key is safe to deploy.
const GJ_PUBLIC_KEY_B64 = 'vMn1S/fdukPvwSN+J2N7bRqLGVkF2TQbV11xBsUMaw4=';

// SHA-256 of the export token. The token itself never reaches this repository.
const GJ_EXPORT_TOKEN_HASH = 'f06358156e3617a8c27c4d1b0a0eedf93b1aeae212b7cffe994c1b472a6e3fb8';

const GJ_MAX_BODY_BYTES = 131072;
const GJ_RATE_LIMIT = 60;
const GJ_RATE_WINDOW_SECONDS = 3600;

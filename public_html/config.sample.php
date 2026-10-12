<?php
// ===== ZAS Canteen configuration — STAGING TEMPLATE =====
// Copy this file to config.php on the staging server and fill in the staging
// values. Do NOT copy the live config.php across: it points at the production
// database, and a test order placed on staging would hit real customer wallets.

define('DB_HOST', 'localhost');
define('DB_NAME', 'PUT_STAGING_DB_NAME_HERE');   // must NOT be zfpvkgkjhr (that is live)
define('DB_USER', 'PUT_STAGING_DB_USER_HERE');
define('DB_PASS', 'PUT_STAGING_DB_PASSWORD_HERE');

define('APP_NAME', 'Canteen (Staging)');
define('APP_URL',  'https://staging.your-domain.com/');
define('CURRENCY', 'PKR');

// Leave empty on staging — otherwise test orders burn real OpenAI credit.
define('OPENAI_API_KEY', '');
define('TTS_MODEL', 'gpt-4o-mini-tts');
define('TTS_VOICE', 'alloy');
define('VOICE_DIR', __DIR__ . '/voices');
define('UPLOAD_DIR', __DIR__ . '/uploads');

date_default_timezone_set('Asia/Karachi');

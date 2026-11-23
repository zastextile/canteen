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

// ===== ZAS link (the RFID counter) =====
// Only needed if kiosk.php is in use. Both keys are made with
//   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
// and each one goes in BOTH apps' config — never in a page, in JavaScript,
// in git or in a chat message.
define('ZAS_CARD_URL', 'https://invoice.thqlinen.com/api/v1/index.php?route=/canteen/card-check');
define('ZAS_CARD_KEY', '');     // canteen SENDS this to ZAS on every card check
define('ZAS_CHARGES_KEY', '');  // ZAS SENDS this to zas.php; the canteen checks it
define('ZAS_DEFAULT_WEEKLY_LIMIT', 100000);  // paisa — Rs 1,000, used only until ZAS sends weekly_limit

date_default_timezone_set('Asia/Karachi');

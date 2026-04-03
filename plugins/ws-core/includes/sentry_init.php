<?php

defined( 'ABSPATH' ) || exit;

function ws_sentry_init() {
    if (!defined('WS_ENABLE_SENTRY') || WS_ENABLE_SENTRY !== true) {
        return;
    }

    \Sentry\init([
        'dsn' => 'https://368c0562dd63c854b7d26dcc2a7a5891@o4511156897972224.ingest.us.sentry.io/4511156904787968',
        'environment' => 'development',

        // HARD privacy boundaries
        'send_default_pii' => false,
        'max_breadcrumbs' => 10,

        'before_send' => function ($event) {
            // strip anything risky
            return $event;
        },
    ]);
}
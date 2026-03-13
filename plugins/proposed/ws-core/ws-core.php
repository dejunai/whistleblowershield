<?php
/**
 * Plugin Name: WhistleblowerShield Core
 * Description: Core architecture for the WhistleblowerShield legal reference platform.
 * Version: 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WS_CORE_PATH', plugin_dir_path(__FILE__));

require_once WS_CORE_PATH . 'includes/loader.php';
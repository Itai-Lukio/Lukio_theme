<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Require Extras files
require_once __DIR__ . '/inc/lukio_helpers.php';
require_once __DIR__ . '/inc/lukio_theme_setup.php';
require_once __DIR__ . '/inc/lukio_theme_options.php';
require_once __DIR__ . '/inc/lukio_svg_support.php';
require_once __DIR__ . '/inc/lukio_theme_update.php';

// add woocommerce related capabilities 
if (function_exists('is_plugin_active')) {
    if (is_plugin_active('woocommerce/woocommerce.php')) {
        require_once __DIR__ . '/inc/lukio_woocommerce_setup.php';
        require_once __DIR__ . '/inc/lukio_woocommerce_helpers.php';

        // helpers
        require_once __DIR__ . '/inc/helpers/woocommerce/ajax-add-to-cart.php';
    }
}

// add the templates sub theme class when exists
// placed in the main theme to cover an overwrite problem when copying updates from the main theme
if (file_exists(__DIR__ . '/inc/lukio_templates_theme.php')) {
    require_once __DIR__ . '/inc/lukio_templates_theme.php';
}

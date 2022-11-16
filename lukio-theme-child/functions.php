<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!function_exists('sitename_enqueue')) {
    function sitename_enqueue()
    {
        // lukio_enqueue()
    }
}
add_action('wp_enqueue_scripts', 'sitename_enqueue');
add_action('flexible_content_enqueue', 'sitename_enqueue');

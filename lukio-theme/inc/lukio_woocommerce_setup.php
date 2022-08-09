<?php
if (!function_exists('lukio_woocommerce_theme_support')) {
    function lukio_woocommerce_theme_support()
    {
        add_theme_support('woocommerce');
    }

    add_action('after_setup_theme', 'lukio_woocommerce_theme_support');
}

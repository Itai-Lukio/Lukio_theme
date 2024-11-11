<?php

/**
 * Used to show page content
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

do_action('wp_enqueue_scripts');

ob_start();
the_content();
$the_content = ob_get_clean();

get_header();

echo $the_content;

get_footer();

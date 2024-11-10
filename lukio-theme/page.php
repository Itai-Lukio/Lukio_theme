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

get_header();

the_content();

get_footer();

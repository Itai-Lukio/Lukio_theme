<?php

/**
 * Template Name: Flexible content
 * 
 * The main template file for flexible content page.
 * Get template part from '/template-parts/strips/', acf_fc_layout name and the template part to get must have the same name.
 * Place all the strip acf data inside a group named 'strip_content' and it will be sent to the template part.
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

do_action('flexible_content_enqueue');

ob_start();
foreach (get_field('flexible_content') as $strip) {
    get_template_part('/template-parts/strips/' . $strip['acf_fc_layout'], '', $strip['strip_content']);
}
$page_markup = ob_get_clean();

get_header();

echo $page_markup;

get_footer();

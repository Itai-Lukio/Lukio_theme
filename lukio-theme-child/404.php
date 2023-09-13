<?php

/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 */

do_action('wp_enqueue_scripts');

lukio_enqueue('/assets/css/404.css', null, array(), array('parent' => true));

get_header();
?>

<h1 id="page_title">404</h1>
<p id="page_sub_title">
    You got us. We are yet to get everywhere and everything.
    <strong id="sub_title_strong">But we will get there!</strong>
</p>

<?php
get_footer();

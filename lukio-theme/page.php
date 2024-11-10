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

// the div#base_single is used for basic css for h1-6, p, ul and ol, it its NOT a must keep
?>
<div id="base_single">
    <?php the_content(); ?>
</div>

<?php

get_footer();

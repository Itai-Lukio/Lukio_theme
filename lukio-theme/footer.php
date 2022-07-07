<?php

/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
?>
</main><!-- #main -->
</div><!-- #primary -->
</div><!-- #content -->

<footer id="site_footer">

    <?php get_template_part('/template-parts/footer/footer_content'); ?>
</footer><!-- #site_footer -->

</div><!-- #page -->

<?php wp_footer(); ?>

</body>

</html>
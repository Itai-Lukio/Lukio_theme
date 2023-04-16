<?php

/**
 * The header.
 *
 * This is the template that displays all of the <head> section and everything up until main.
 *
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

do_action('wp_enqueue_scripts');

ob_start();
Lukio_Theme_setup::get_pre_header_part();
$pre_header_markup = ob_get_clean();

ob_start();
Lukio_Theme_setup::get_header_part();
$header_markup = ob_get_clean();

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no" />
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php wp_head(); ?>
    <?php echo get_option('lukio_pixels_head'); ?>
</head>

<body <?php body_class(); ?>>
    <?php echo get_option('lukio_pixels_body'); ?>
    <?php do_action('wp_body_open'); ?>
    <div id="page" class="site">

        <?php echo $pre_header_markup; ?>

        <header id="site_header">

            <?php echo $header_markup; ?>

        </header><!-- #site_header -->


        <div id="content" class="site-content">
            <div id="primary" class="content-area">
                <main id="main" class="site-main">
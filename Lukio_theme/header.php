<?php

/**
 * The header.
 *
 * This is the template that displays all of the <head> section and everything up until main.
 *
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no" />
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php do_action('wp_body_open'); ?>
    <div id="page" class="site">
        <header id="site_header">

            <?php get_template_part('/template-parts/header/header_main'); ?>

        </header><!-- #site_header -->


        <div id="content" class="site-content">
            <div id="primary" class="content-area">
                <main id="main" class="site-main">
<?php

if (!function_exists('lukio_woocommerce_theme_support')) {
    /**
     * add theme support for woocommere
     * 
     * @author Itai Dotan
     */
    function lukio_woocommerce_theme_support()
    {
        add_theme_support('woocommerce');
    }
}
add_action('after_setup_theme', 'lukio_woocommerce_theme_support');

if (!function_exists('lukio_woocommerce_enqueues')) {
    /**
     * enqueue the woocommerce relevant script
     * 
     * @author Itai Dotan
     */
    function lukio_woocommerce_enqueues()
    {
        lukio_enqueue('/assets/js/lukio_woocommerce.js', 'lukio_woocommerce_script', array('jquery'), array('parent' => true));

        wp_localize_script(
            'lukio_woocommerce_script',
            'lukio_wc_ajax',
            array('ajax_url' => admin_url('admin-ajax.php'))
        );
    }
}
add_action('wp_enqueue_scripts', 'lukio_woocommerce_enqueues', PHP_INT_MAX);

if (!function_exists('lukio_woocommerce_admin_bar_guides')) {
    /**
     * add woocommerce guides to the admin bar
     * 
     * @author Itai Dotan
     */
    function lukio_woocommerce_admin_bar_guides($wp_admin_bar)
    {
        global $lukio_admin_bar_guides_allowerd_roles;
        if (count(array_intersect($lukio_admin_bar_guides_allowerd_roles, wp_get_current_user()->roles)) > 0) {
            $wp_admin_bar->add_node(array(
                'id' => 'lukio_woocommerce_guides',
                'title' => "WOO " .  _n('guide', 'guides', 1, 'lukio-theme'),
                'parent' => 'lukio_guides',
            ));

            $wp_admin_bar->add_node(array(
                'id' => 'lukio_woocommerce_php',
                'title' => "PHP " .  _n('guide', 'guides', 1, 'lukio-theme'),
                'parent' => 'lukio_woocommerce_guides',
                'href' => get_template_directory_uri() . '/guides/woo_php.txt',
                'meta' => array(
                    'target' => '_blank',
                ),
            ));

            $wp_admin_bar->add_node(array(
                'id' => 'lukio_woocommerce_js',
                'title' => "JS " .  _n('guide', 'guides', 1, 'lukio-theme'),
                'parent' => 'lukio_woocommerce_guides',
                'href' => get_template_directory_uri() . '/guides/woo_js.txt',
                'meta' => array(
                    'target' => '_blank',
                ),
            ));
        }
    }
}
add_action('admin_bar_menu', 'lukio_woocommerce_admin_bar_guides', 999);

if (!function_exists('lukio_woocommerce_mini_cart')) {
    /**
     * mini cart shortcode callback
     * 
     * uses woocommerce/cart/mini-cart.php as the inside of the cart
     * 
     * @return String mini cart markup
     * 
     * @author Itai Dotan
     */
    function lukio_woocommerce_mini_cart()
    {
        ob_start();
?>
        <div class="lukio_mini_cart_wrapper">
            <?php woocommerce_mini_cart(); ?>
        </div>
<?php
        return ob_get_clean();
    }
}
add_shortcode('lukio_woocommerce_mini_cart', 'lukio_woocommerce_mini_cart');

if (!function_exists('lukio_woocommerce_refresh_mini_cart')) {
    /**
     * ajax cart refresh
     * 
     * uses woocommerce/cart/mini-cart.php as the inside of the cart.
     * add data to send back with the ajax useing the filter 'lukio_woocommerce_refresh_mini_cart_extra'.
     * 
     * @author Itai Dotan
     */
    function lukio_woocommerce_refresh_mini_cart()
    {
        ob_start();
        woocommerce_mini_cart();
        $fragment = ob_get_clean();
        $extra = array();

        echo json_encode(array(
            'fragment' => $fragment,
            'items_count' => array(
                'all' => WC()->cart->get_cart_contents_count(),
                'unique' => count(WC()->cart->get_cart()),
            ),
            'extra' => apply_filters('lukio_woocommerce_refresh_mini_cart_extra', $extra)
        ));
        wp_die();
    }
}
add_action('wp_ajax_lukio_woocommerce_refresh_mini_cart', 'lukio_woocommerce_refresh_mini_cart');
add_action('wp_ajax_nopriv_lukio_woocommerce_refresh_mini_cart', 'lukio_woocommerce_refresh_mini_cart');

if (!function_exists('lukio_woocommerce_add_to_cart_button')) {
    /**
     * echo a valid woocommerce button with custom classes and button text
     * 
     * @param WC_Product $product [requierd] product the button is for
     * @param String $class_str [optional] class string to add to the button
     * @param String $btn_add_text [optional] text when the item is purchasable and in stock
     * @param String $btn_no_stock [optional] text when the item cant be purchased or our of stock
     * @param String $html_tag [optional] html tag for the button output, default 'a'
     * 
     * @author Itai Dotan
     */
    function lukio_woocommerce_add_to_cart_button($product, $class_str = '', $btn_add_text = '', $btn_no_stock = '',  $html_tag = 'a')
    {
        // modified from includes/wc-template-functions.php -> woocommerce_template_loop_add_to_cart()
        if ($product) {
            $defaults = array(
                'quantity'   => 1,
                'class'      => implode(
                    ' ',
                    array_filter(
                        array(
                            'button',
                            'product_type_' . $product->get_type(),
                            $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
                            $product->supports('ajax_add_to_cart') && $product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
                            trim($class_str),
                        )
                    )
                ),
                'attributes' => array(
                    'data-product_id'  => $product->get_id(),
                    'data-product_sku' => $product->get_sku(),
                    'aria-label'       => $product->add_to_cart_description(),
                    'rel'              => 'nofollow',
                ),
            );

            $args = apply_filters('woocommerce_loop_add_to_cart_args', wp_parse_args($args, $defaults), $product);

            if (isset($args['attributes']['aria-label'])) {
                $args['attributes']['aria-label'] = wp_strip_all_tags($args['attributes']['aria-label']);
            }

            // modified from templates/loop/add-to-cart.php 
            echo sprintf(
                '<%1$s href="%2$s" data-quantity="%3$s" class="%4$s" %5$s>%6$s</%1$s>',
                $html_tag,
                esc_url($product->add_to_cart_url()),
                esc_attr(isset($args['quantity']) ? $args['quantity'] : 1),
                esc_attr(isset($args['class']) ? $args['class'] : 'button'),
                isset($args['attributes']) ? wc_implode_html_attributes($args['attributes']) : '',
                $product->is_purchasable() && $product->is_in_stock() ? ($btn_add_text != '' ? $btn_add_text : esc_html($product->add_to_cart_text())) : ($btn_no_stock != '' ? $btn_no_stock : esc_html($product->add_to_cart_text()))
            );
        }
    }
}

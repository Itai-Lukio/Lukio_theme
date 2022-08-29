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

        add_theme_support('wc-product-gallery-zoom');
        add_theme_support('wc-product-gallery-lightbox');
        add_theme_support('wc-product-gallery-slider');
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
        lukio_enqueue('/assets/css/lukio_woocommerce.css', 'lukio_woocommerce_stylesheet', array('lukio_main_theme_general_stylesheet'), array('parent' => true));
        lukio_enqueue('/assets/js/lukio_woocommerce.js', 'lukio_woocommerce_script', array('jquery'), array('parent' => true));

        wp_localize_script(
            'lukio_woocommerce_script',
            'lukio_wc_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'checkout_url' => wc_get_checkout_url(),
                'cart_url' => wc_get_cart_url(),
            )
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
        die;
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

if (!function_exists('lukio_woocommerce_free_shipping_threshold')) {
    /**
     * Check if the cart is eligible for free shipping
     * 
     * @param Number $decimals [optional] sets the number of decimal digits when returning a number, default 2
     * @param String $decimal_separator [optional] sets the separator for the decimal point when returning a number, default '.'
     * @param String $thousands_separator [optional] sets the thousands separator when returning a number, default ','
     * @return Bool|Number true when eligible for free shipping, missing amount otherwise
     * 
     * @author Itai Dotan
     */
    function lukio_woocommerce_free_shipping_threshold($decimals = 2, $decimal_separator = '.', $thousands_separator = ',')
    {
        $amount_to_free = -1;
        $allZones = WC_Shipping_Zones::get_zones();
        foreach ($allZones as $zone) {
            $zone_methods = $zone['shipping_methods'];
            foreach ($zone_methods as $method) {
                $m_name = $method->get_rate_id();
                if (strpos($m_name, 'free_shipping') !== false && $method->is_enabled()) {
                    $order_min_amount = (float)$method->min_amount;
                    $amount_to_free = $order_min_amount - (float)WC()->cart->total;
                }
            }
        }
        return $amount_to_free <= 0 ? true : number_format((float)$amount_to_free, $decimals, $decimal_separator, $thousands_separator);
    }
}

if (!function_exists('lukio_woocommerce_cart_product_quantity')) {
    /**
     * echo item quantity plus minus control buttons for woocommerce cart and minicart
     * 
     * @param WC_Product $product [requierd] product object
     * @param String $cart_item_key [requierd] cart item key from the cart loop
     * @param Array $cart_item [requierd] cart item from the cart loop
     * @param String $class_str [optional] class string to add to all parts
     * @param String $minus_content [optional] markup to use in the minus button, default pre set svg
     * @param String $plus_content [optional] markup to use in the plus button, default pre set svg
     * 
     * @author Itai Dotan
     */
    function lukio_woocommerce_cart_product_quantity_markup($product, $cart_item_key, $cart_item, $class_str = '', $minus_content = null, $plus_content = null)
    {
        $default_minus = '<svg class="lukio_cart_product_quantity_btn_svg" width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.1011 13.9995H19.8906" stroke="#ffffff" stroke-linecap="square" />
                            </svg>';
        $default_plus = '<svg class="lukio_cart_product_quantity_btn_svg" width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14.0004 8.10547L14.0004 19.8949" stroke="#ffffff" stroke-linecap="square" />
                            <path d="M8.1011 13.9995L19.8906 13.9995" stroke="#ffffff" stroke-linecap="square" />
                            </svg>';
        $product_max_quantity = $product->get_max_purchase_quantity();

        $position_class = $class_str != '' ? ' ' . trim($class_str) : '';
        $minus_markup = is_null($minus_content) ? $default_minus : $minus_content;
        $pluse_markup = is_null($plus_content) ? $default_plus : $plus_content;
    ?>
        <div class="lukio_cart_product_quantity<?php if ($cart_item['quantity'] == $product_max_quantity) {
                                                    echo ' plus_disabled';
                                                } ?><?php echo $position_class; ?>" data-key="<?php echo $cart_item_key; ?>">
            <div class="lukio_cart_product_quantity_btn minus<?php echo $position_class; ?>">
                <?php echo $minus_markup; ?>
            </div>

            <span class="lukio_cart_product_quantity_display<?php echo $position_class; ?>" max="<?php echo $product_max_quantity; ?>"><?php echo $cart_item['quantity']; ?></span>

            <div class="lukio_cart_product_quantity_btn plus<?php echo $position_class; ?>">
                <?php echo $pluse_markup; ?>
            </div>
        </div>
<?php
    }
}
if (!function_exists('lukio_update_cart_quantity')) {
    /**
     * update cart item quantity
     * 
     * @author Tal Shpeizer
     */
    function lukio_update_cart_quantity()
    {
        $cart_item_key = $_POST['cart_item'];
        $cart_quantity = (int) $_POST['quantity'];
        $cart = WC()->cart;
        $cart->set_quantity($cart_item_key, $cart_quantity);
        die;
    }
}
add_action('wp_ajax_lukio_update_cart_quantity', 'lukio_update_cart_quantity');
add_action('wp_ajax_nopriv_lukio_update_cart_quantity', 'lukio_update_cart_quantity');

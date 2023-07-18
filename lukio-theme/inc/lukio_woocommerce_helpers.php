<?php

if (!function_exists('lukio_woocommerce_add_to_cart_button')) {
    /**
     * echo a valid woocommerce button with custom classes and button text
     * 
     * to use ajax with wc add_to_cart form remove the form submit button and place the function in the wanted template.
     * templates/single-product/add-to-cart/filename.php
     * 
     * @param WC_Product $product [requierd] product the button is for
     * @param String $class_str [optional] class string to add to the button, default `''`
     * @param String $btn_add_text [optional] text when the item is purchasable and in stock, default `''`
     * @param String $btn_no_stock [optional] text when the item cant be purchased or our of stock, default `''`
     * @param String $html_tag [optional] html tag for the button output, default `a`
     * 
     * @author Itai Dotan
     */
    function lukio_woocommerce_add_to_cart_button($product, $class_str = '', $btn_add_text = '', $btn_no_stock = '',  $html_tag = 'a')
    {
        // modified from includes/wc-template-functions.php -> woocommerce_template_loop_add_to_cart()
        if ($product) {
            if ($product->get_type() == 'simple') {
                $in_stock = $product->is_purchasable() && $product->is_in_stock();
            } else {
                $in_stock = !empty($product->get_available_variations());
            }

            $defaults = array(
                'quantity'   => 1,
                'class'      => implode(
                    ' ',
                    array_filter(
                        array(
                            'button',
                            'product_type_' . $product->get_type(),
                            $in_stock ? 'ajax_add_to_cart add_to_cart_button single_add_to_cart_button' : 'no_stock',
                            'lukio_add_btn',
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

            $args = apply_filters('woocommerce_loop_add_to_cart_args', $defaults, $product);

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
                $in_stock ? ($btn_add_text != '' ? $btn_add_text : esc_html($product->add_to_cart_text())) : ($btn_no_stock != '' ? $btn_no_stock : esc_html($product->add_to_cart_text()))
            );
        }
    }
}

if (!function_exists('lukio_woocommerce_get_user_ip')) {
    /**
     * get client ip
     * 
     * @return String client ip
     * 
     * @author Itai Dotan
     */
    function lukio_woocommerce_get_user_ip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}

if (!function_exists('lukio_woocommerce_get_user_country_code')) {
    /**
     * get the user country code
     * 
     * @return String country code
     * 
     * @author Itai Dotan
     */
    function lukio_woocommerce_get_user_country_code()
    {
        $user_id = get_current_user_id();
        $country_code = '';
        // try to get the user saved country
        if ($user_id != 0) {
            $country_code = get_user_meta($user_id, 'shipping_country', true);
        };
        // when there is no country code get one from the ip
        if ($country_code == '') {
            $ip_data = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . lukio_woocommerce_get_user_ip()));
            $country_code = $ip_data->geoplugin_countryCode;
        }
        return $country_code;
    }
}

if (!function_exists('lukio_woocommerce_free_shipping_threshold')) {
    /**
     * Check if the cart is eligible for free shipping
     * 
     * @return Bool|Number false when free shipping not available, true when eligible for free shipping or missing amount to free shipping
     * 
     * @author Itai Dotan
     */
    function lukio_woocommerce_free_shipping_threshold()
    {
        $free_shipping_available = false;
        $amount_to_free = -1;
        $allZones = WC_Shipping_Zones::get_zones();
        $country_code = lukio_woocommerce_get_user_country_code();

        foreach ($allZones as $zone) {
            $country_in_zone = false;
            // check the zone for the user's country code
            foreach ($zone['zone_locations'] as $location) {
                if ($location->code == $country_code) {
                    $country_in_zone = true;
                    break;
                }
            }

            if (!$country_in_zone) {
                // skip any zone that is not the user's zone
                continue;
            }

            $zone_methods = $zone['shipping_methods'];
            foreach ($zone_methods as $method) {
                $m_name = $method->get_rate_id();
                if (strpos($m_name, 'free_shipping') !== false && $method->is_enabled()) {
                    $free_shipping_available = true;
                    $order_min_amount = (float)$method->min_amount;
                    $amount_to_free = $order_min_amount - (float)WC()->cart->subtotal;
                    // break out of the method loop when free shipping was found and tested
                    break;
                }
            }
            // break out of the zone loop when the user's zone was found and tested
            break;
        }
        return $free_shipping_available ? ($amount_to_free <= 0 ? true : (float)$amount_to_free) : false;
    }
}

if (!function_exists('lukio_woocommerce_cart_product_quantity')) {
    /**
     * echo item quantity plus minus control buttons for woocommerce cart and minicart
     * 
     * @param WC_Product $product [requierd] product object
     * @param String $cart_item_key [requierd] cart item key from the cart loop
     * @param Array $cart_item [requierd] cart item from the cart loop
     * @param String $class_str [optional] class string to add to all parts, default `''`
     * @param String $minus_content [optional] markup to use in the minus button, default `pre set svg`
     * @param String $plus_content [optional] markup to use in the plus button, default `pre set svg`
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

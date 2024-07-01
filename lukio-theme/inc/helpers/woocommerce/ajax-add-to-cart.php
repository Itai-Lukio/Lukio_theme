<?php

namespace Lukio_Theme\Helpers\Woocommerce;

/**
 * class to fix ajax add_to_cart when adding a 'variation' with an attribute is set to 'any'
 */
class Ajax_add_To_cart
{
    /**
     * holds product ID when the fix is running
     * @var int
     */
    private static $product_id;

    /**
     * track when need to rewrite the product meta
     * @var bool 
     */
    private static $rewrite_meta = true;

    /**
     * holds the fixed product attributes with the posted data, start as `false` before initialization
     * @var array|bool
     */
    private static $posted_attributes = false;

    /**
     * setup the fix when needed
     * 
     * @param int $product_id product ID
     * 
     * @author Itai Dotan
     */
    public static function start_fix($product_id)
    {
        self::$product_id = $product_id;
        $product = wc_get_product($product_id);
        if (
            $product && $product->get_type() === 'variation' && isset($_REQUEST['wc-ajax'])
            && $_REQUEST['wc-ajax'] === 'add_to_cart' && isset($_REQUEST['lukio_attributes'])
        ) {
            add_filter('get_post_metadata', array(__CLASS__, 'edit_product_attributes'), 10, 3);
            add_filter('woocommerce_add_to_cart_validation', array(__CLASS__, 'remove_fix'));
        }

        return $product_id;
    }

    /**
     * get fixed attributes, set them to class at first call
     * 
     * @author Itai Dotan
     */
    private static function get_fixed_attributes()
    {
        if (!self::$posted_attributes) {
            $all_meta = get_post_meta(self::$product_id);
            $posted = json_decode(wp_unslash($_REQUEST['lukio_attributes']), true);

            foreach ($all_meta as $name => $value) {
                // only look at valid attribute meta
                if (0 !== strpos($name, 'attribute_') || !isset($posted[$name])) {
                    continue;
                }

                // use sanitize_title to keep the slug format the attribute is saved as
                $all_meta[$name] = array(sanitize_title($posted[$name]));
            }
            self::$posted_attributes = $all_meta;
        }
        return self::$posted_attributes;
    }

    /**
     * edit the meta pulled for the product when needed
     * 
     * @param mixed  $value The value to return
     * @param int    $object_id ID of the object metadata is for
     * @param string $meta_key  metadata key
     * @return mixed edited $value when needed
     * 
     * @author Itai Dotan
     */
    public static function edit_product_attributes($value, $object_id, $meta_key)
    {
        if (self::$rewrite_meta && $meta_key == '' && $object_id == self::$product_id) {
            // set to false to get the base meta form the DB
            self::$rewrite_meta = false;
            $value = self::get_fixed_attributes();
        }
        self::$rewrite_meta = true;

        return $value;
    }

    /**
     * remove the fix as we need it only at the start of class-wc-ajax.php::add_to_cart().
     * 
     * need to edit only the product sent by the ajax function to be added, and not any other product pull in the add_to_cart process
     * 
     * @param bool $validation if the ajax add to cart is valid, no use for this class documented only for completeness
     * @return bool $validation
     *
     * @author Itai Dotan
     */
    public static function remove_fix($validation)
    {
        remove_filter('get_post_metadata', array(__CLASS__, 'edit_product_attributes'), 10);
        return $validation;
    }
}

add_filter('woocommerce_add_to_cart_product_id', __NAMESPACE__ . '\Ajax_add_To_cart::start_fix');

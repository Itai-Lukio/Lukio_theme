<?php

/**
 * lukio theme woocommerce setup
 */
class Lukio_Woocommerce_Setup
{
    /**
     * construct action to run when creating a new instance
     * 
     * @author Itai Dotan
     */
    public function __construct()
    {
        add_action('after_setup_theme', array($this, 'theme_support'));
        add_action('wp_enqueue_scripts', array($this, 'enqueues'), PHP_INT_MAX);
        add_action('admin_bar_menu', array($this, 'admin_bar_guides'), 50);

        add_shortcode('lukio_woocommerce_mini_cart', array($this, 'mini_cart'));

        add_action('wp_ajax_lukio_woocommerce_refresh_mini_cart', array($this, 'refresh_mini_cart'));
        add_action('wp_ajax_nopriv_lukio_woocommerce_refresh_mini_cart', array($this, 'refresh_mini_cart'));

        add_action('wp_ajax_lukio_update_cart_quantity', array($this, 'update_cart_quantity'));
        add_action('wp_ajax_nopriv_lukio_update_cart_quantity', array($this, 'update_cart_quantity'));

        // wrap the form with a targetable div
        add_action('woocommerce_before_add_to_cart_form', function () {
            echo '<div class="lukio_theme_add_to_cart_form_wrapper">';
        });

        // close the form targetable div
        add_action('woocommerce_after_add_to_cart_form', function () {
            echo '</div>';
        });

        add_action('woocommerce_product_thumbnails', array($this, 'add_gallery_arrows'));
    }

    /**
     * add theme support for woocommere
     * 
     * @author Itai Dotan
     */
    public function theme_support()
    {
        add_theme_support('woocommerce');

        add_theme_support('wc-product-gallery-zoom');
        add_theme_support('wc-product-gallery-lightbox');
        add_theme_support('wc-product-gallery-slider');
    }

    /**
     * enqueue the woocommerce relevant script
     * 
     * @author Itai Dotan
     */
    public function enqueues()
    {
        lukio_enqueue('/assets/css/lukio_woocommerce.css', 'lukio_woocommerce_stylesheet', array('lukio_main_theme_general_stylesheet'), array('parent' => true));
        lukio_enqueue('/assets/js/lukio_woocommerce.js', 'lukio_woocommerce_script', array('jquery'), array('parent' => true));

        wp_enqueue_script('wc-cart-fragments');

        wp_localize_script(
            'lukio_woocommerce_script',
            'lukio_woo',
            array(
                'checkout_url' => wc_get_checkout_url(),
                'cart_url' => wc_get_cart_url(),
            )
        );
    }

    /**
     * add woocommerce guides to the admin bar
     * 
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference
     * 
     * @author Itai Dotan
     */
    public function admin_bar_guides($wp_admin_bar)
    {
        $allowerd_roles = apply_filters('lukio_admin_guides_roles', Lukio_Theme_setup::get_guides_roles());
        if (count(array_intersect($allowerd_roles, wp_get_current_user()->roles)) > 0) {
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

    /**
     * mini cart shortcode callback
     * 
     * uses woocommerce/cart/mini-cart.php as the inside of the cart
     * 
     * @return String mini cart markup
     * 
     * @author Itai Dotan
     */
    public function mini_cart()
    {
        ob_start();
?>
        <div class="lukio_mini_cart_wrapper">
            <?php woocommerce_mini_cart(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * ajax cart refresh
     * 
     * uses woocommerce/cart/mini-cart.php as the inside of the cart.
     * add data to send back with the ajax useing the filter 'lukio_woocommerce_refresh_mini_cart_extra'.
     * 
     * @author Itai Dotan
     */
    public function refresh_mini_cart()
    {
        ob_start();
        woocommerce_mini_cart();
        $fragment = ob_get_clean();
        $extra = array();
        $cart = WC()->cart;

        echo json_encode(array(
            'fragment' => $fragment,
            'cart_hash' => $cart->get_cart_hash(),
            'items_count' => array(
                'all' => $cart->get_cart_contents_count(),
                'unique' => count($cart->get_cart()),
            ),
            'extra' => apply_filters('lukio_woocommerce_refresh_mini_cart_extra', $extra)
        ));
        die;
    }

    /**
     * update cart item quantity
     * 
     * @author Tal Shpeizer
     */
    public function update_cart_quantity()
    {
        $cart_item_key = $_POST['cart_item'];
        $cart_quantity = (int) $_POST['quantity'];
        $cart = WC()->cart;
        $cart->set_quantity($cart_item_key, $cart_quantity);
        die;
    }

    /**
     * add product gallery controls
     * 
     * @author Itai Dotan
     */
    public function add_gallery_arrows()
    {
        // allow to filter to not print the arrows
        if (apply_filters('lukio_theme_skip_product_thumbnails_arrows', false)){
            return;
        }

        global $product;
        $attachment_ids = $product->get_gallery_image_ids();

        // check if there is a gallery
        if (count($attachment_ids) == 0) {
            return;
        }
        $loop = apply_filters('lukio_product_gallery_use_loop', true) ? 'true' : 'false';
        foreach (['prev', 'next'] as $button_action) {
        ?>
            <button class="lukio_product_gallery_arrow <?php echo $button_action; ?>" data-action="<?php echo $button_action; ?>" data-loop="<?php echo $loop; ?>" type="button"><?php echo apply_filters("lukio_product_gallery_arrow_$button_action", $button_action); ?></button>
<?php
        }

        if (apply_filters('lukio_product_gallery_pagination', true)) {
            echo '<ul class="lukio_product_gallery_pagination">';

            // add one more entry for the main image
            $attachment_ids[] = 0;

            foreach ($attachment_ids as $index => $id) {
                echo '<li class="lukio_product_gallery_pagination_dot' . ($index == 0 ? ' active' : '') . '" data-index="' . $index . '" data-loop="' . $index . '"></li>';
            }
            echo '</ul>';
        }
    }
}
new Lukio_Woocommerce_Setup();

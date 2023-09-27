<?php
defined('ABSPATH') || exit;

/**
 * lukio theme woocommerce setup
 */
class Lukio_Woocommerce_Setup
{
    /**
     * meta key of the product attribute color
     * @var string meta key
     */
    const COLOR_META_KEY = 'lukio_color';

    /**
     * meta key of the product attribute second color
     * @var string meta key
     */
    const SECOND_COLOR_META_KEY = 'lukio_second_color';

    /**
     * meta key of the product attribute color
     * @var string meta key
     */
    const PRODUCT_ATTRIBUTES_META_KEY = 'lukio_product_attributes';

    /**
     * attribute picker display style for colors
     * @var string style type
     */
    const ATTRIBUTE_STYLE_COLORS = 'colors';

    /**
     * attribute picker display style for dropdown
     * @var string style type
     */
    const ATTRIBUTE_STYLE_DROPDOWN = 'dropdown';

    /**
     * attribute picker display style for select
     * @var string style type
     */
    const ATTRIBUTE_STYLE_SELECT = 'select';

    /**
     * 
     * @var null|array null before init, array of attributes and their display option 
     */
    private $product_attributes = null;

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

        add_filter('lukio_theme_options_tabs', array($this, 'add_option_tabs'));
        add_action('lukio_theme_options_saved', array($this, 'save_woocommerce_options_tabs'));
        add_action('admin_init', array($this, 'maybe_init_product_attributes'));
        add_action('woocommerce_product_thumbnails', array($this, 'add_gallery_arrows'));
        add_filter('woocommerce_dropdown_variation_attribute_options_html', array($this, 'product_ul_select'), 10, 2);

        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
        add_filter('woocommerce_product_data_tabs', array($this, 'add_bulk_variations_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'bulk_variations_tab_content'));
        add_action('wp_ajax_lukio_product_bulk_variations_image_preview', array($this, 'bulk_variations_image_preview'));
        add_action('wp_ajax_lukio_product_bulk_variations_image_set', array($this, 'bulk_variations_image_set'));
        add_action('wp_ajax_lukio_product_bulk_variations_reload', array($this, 'reload_bulk_variations_tab_content'));
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
        if (apply_filters('lukio_theme_skip_product_thumbnails_arrows', false)) {
            return;
        }

        global $product;
        $attachment_ids = $product->get_gallery_image_ids();

        // check if there is a gallery
        if (count($attachment_ids) == 0) {
            return;
        }
        $loop = apply_filters('lukio_product_gallery_use_loop', true) ? 'true' : 'false';
        $position = apply_filters('lukio_product_gallery_arrows_in_gallery_wrapper', false) ? 'gallery' : 'viewport';
        foreach (['prev', 'next'] as $button_action) {
        ?>
            <button class="lukio_product_gallery_arrow <?php echo $button_action; ?> hide_no_js no_js" data-action="<?php echo $button_action; ?>" data-loop="<?php echo $loop; ?>" data-place="<?php echo $position; ?>" type="button"><?php echo apply_filters("lukio_product_gallery_arrow_$button_action", $button_action); ?></button>
        <?php
        }

        if (apply_filters('lukio_product_gallery_pagination', true)) {
            echo '<ul class="lukio_product_gallery_pagination hide_no_js no_js">';

            // add one more entry for the main image
            $attachment_ids[] = 0;

            foreach ($attachment_ids as $index => $id) {
                echo '<li class="lukio_product_gallery_pagination_dot' . ($index == 0 ? ' active' : '') . '" data-index="' . $index . '" data-loop="' . $index . '"></li>';
            }
            echo '</ul>';
        }
    }

    /**
     * add woocommerce option tabs to the theme options
     * 
     * @param Array $array tabs array
     * @return Array updated tabs array
     * 
     * @author Itai Dotan
     */
    public function add_option_tabs($array)
    {
        $array[] = array(
            'label' => __('Product variations style', 'lukio-theme'),
            'callback' => array($this, 'print_admin_variations_style'),
        );
        return $array;
    }

    /**
     * print the product variations style tab content
     * 
     * @author Itai Dotan
     */
    public function print_admin_variations_style()
    {
        ?>
        <div class="lukio_theme_variations_style_wrapper">
            <?php
            foreach (wc_get_attribute_taxonomies() as $taxonomie) {
                $attribute_name = 'pa_' . $taxonomie->attribute_name;
                $name = $this::PRODUCT_ATTRIBUTES_META_KEY . '[' . $attribute_name . ']';
            ?>
                <div class="lukio_theme_variation_style">
                    <label for="<?php echo $name; ?>"><?php echo $taxonomie->attribute_label; ?></label>
                    <select name="<?php echo $name; ?>" id="<?php echo $name; ?>">
                        <option value=""><?php echo __('Buttons', 'lukio-theme'); ?></option>
                        <?php
                        $select_types = array(
                            $this::ATTRIBUTE_STYLE_COLORS => __('Colors', 'lukio-theme'),
                            $this::ATTRIBUTE_STYLE_DROPDOWN => __('Dropdown', 'lukio-theme'),
                            $this::ATTRIBUTE_STYLE_SELECT => __('Select', 'lukio-theme'),
                        );

                        foreach ($select_types as $select_value => $select_label) {
                            $selected = isset($this->product_attributes[$attribute_name]) && $this->product_attributes[$attribute_name] == $select_value;
                        ?>
                            <option value="<?php echo $select_value; ?>" <?php echo $selected ? 'selected' : ''; ?>><?php echo $select_label; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * save woocommerce options
     * 
     * @author Itai Dotan
     */
    public function save_woocommerce_options_tabs()
    {
        $attribute_style = array();
        foreach ($_POST[$this::PRODUCT_ATTRIBUTES_META_KEY] as $taxonomie_slug => $type) {
            if (empty($type)) {
                continue;
            }
            $attribute_style[$taxonomie_slug] = sanitize_text_field($type);
        }
        update_option($this::PRODUCT_ATTRIBUTES_META_KEY, $attribute_style);
        $this->product_attributes = $attribute_style;
    }

    /**
     * add the color picker, use for edit and add term forms
     * 
     * @param bool $enqueue true to enqueue script, used to not double print scripts
     * @param string $name meta key name for the input
     * @param string $color saved color value
     * 
     * @author Itai Dotan
     */
    private function add_attribute_color_picker($enqueue, $name, $color = '')
    {
        if ($enqueue) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        ?>
            <script>
                jQuery(function($) {
                    $('.lukio_attribute_color_picker').wpColorPicker();
                });
            </script>
        <?php
        }
        ?>
        <input class="lukio_attribute_color_picker" name="<?php echo $name ?>" type="text" value="<?php echo $color; ?>" autocomplete="off">
        <?php
    }

    /**
     * save the color after a term been saved
     * 
     * @param Int $term_id term ID
     * 
     * @author Itai Dotan
     */
    public function save_attribute_color($term_id)
    {
        foreach ([$this::COLOR_META_KEY, $this::SECOND_COLOR_META_KEY] as $meta_key) {
            if (isset($_POST[$meta_key])) {
                $color = sanitize_hex_color($_POST[$meta_key]);
                update_term_meta($term_id, $meta_key, $color);
            } else {
                delete_term_meta($term_id, $meta_key);
            }
        }
    }

    /**
     * get text for the term page color
     * 
     * @return array text to use in the page indexed by meta key
     * 
     * @author Itai Dotan
     */
    private function get_term_page_text()
    {
        return array(
            $this::COLOR_META_KEY => array(
                'label' => __('Color', 'lukio-theme'),
                'description' => __('Color to use for the product variation button', 'lukio-theme'),
            ),
            $this::SECOND_COLOR_META_KEY => array(
                'label' => __('Second color', 'lukio-theme'),
                'description' => __('Second color to use for the product variation button', 'lukio-theme'),
            )
        );
    }

    /**
     * setup to add color to edit term form
     * 
     * @param WP_Term $term current taxonomy term object
     * 
     * @author Itai Dotan
     */
    public function attribute_edit_filed($term)
    {
        $texts = $this->get_term_page_text();
        $enqueue = true;
        foreach ($texts as $meta_key => $text_array) {
        ?>
            <tr class="form-field term-<?php echo $meta_key; ?>-wrap">
                <th scope="row">
                    <label for="<?php echo $meta_key; ?>"><?php echo $text_array['label']; ?></label>
                </th>
                <td>
                    <?php
                    $this->add_attribute_color_picker($enqueue, $meta_key, get_term_meta($term->term_id, $meta_key, true));
                    ?>
                    <p class="description" id="<?php echo $meta_key; ?>-description"><?php echo $text_array['description']; ?></p>
                </td>
            </tr>
        <?php
            $enqueue = false;
        }
    }

    /**
     * setup to add color to add term form
     * 
     * @author Itai Dotan
     */
    public function attribute_add_field()
    {
        $texts = $this->get_term_page_text();
        $enqueue = true;
        foreach ($texts as $meta_key => $text_array) {
        ?>
            <div class="form-field term-<?php echo $meta_key; ?>-wrap">
                <label for="<?php echo $meta_key; ?>"><?php echo $text_array['label']; ?></label>
                <?php
                $this->add_attribute_color_picker($enqueue, $meta_key);
                ?>
                <p id="<?php echo $meta_key; ?>-description"><?php echo $text_array['description']; ?></p>
            </div>
        <?php
            $enqueue = false;
        }
    }

    /**
     * init the product attributes var and hooks one per run
     * 
     * @author Itai Dotan
     */
    public function maybe_init_product_attributes()
    {
        if (!is_null($this->product_attributes)) {
            return;
        }
        $this->product_attributes = get_option($this::PRODUCT_ATTRIBUTES_META_KEY, array());
        foreach ($this->product_attributes as $attribute_slug => $type) {
            if ($type == $this::ATTRIBUTE_STYLE_COLORS) {
                add_action("{$attribute_slug}_add_form_fields", array($this, 'attribute_add_field'));
                add_action("{$attribute_slug}_edit_form_fields", array($this, 'attribute_edit_filed'));
                add_action("saved_{$attribute_slug}", array($this, 'save_attribute_color'));
            }
        }
    }

    /**
     * change the default select to ul select
     * 
     * @param string $html default select from `wc_dropdown_variation_attribute_options`
     * @param array $args data from 'wc_dropdown_variation_attribute_options'
     * @return string edited select html
     * 
     * @author Itai Dotan 
     */
    public function product_ul_select($html, $args)
    {
        $this->maybe_init_product_attributes();

        $attribute_select = false;
        $is_dropdown = false;
        $is_color = false;
        $class = 'buttons';
        if (isset($this->product_attributes[$args['attribute']])) {
            switch ($this->product_attributes[$args['attribute']]) {
                case $this::ATTRIBUTE_STYLE_SELECT:
                    $attribute_select = true;
                    break;
                case $this::ATTRIBUTE_STYLE_DROPDOWN:
                    $is_dropdown = true;
                    $class = 'dropdown';
                    break;
                case $this::ATTRIBUTE_STYLE_COLORS:
                    $is_color = true;
                    $class = 'colors';
                    break;
            }
        }
        // hook to use the default select
        if (apply_filters('lukio_product_variation_select_' . $args['attribute'], $attribute_select)) {
            return $html;
        }

        $esc_attr_name = esc_attr(sanitize_title($args['attribute']));
        ob_start();
        ?>
        <div class="lukio_woocommerce_product_variations_wrapper">

            <?php
            if ($is_dropdown) {
                // add dropdown wrapper and a selected display
                /* TRANSLATORS: use woocommerce translation. does not need translation */
                $placeholder = esc_html(apply_filters('lukio_woocommerce_product_variation_placeholder_' . $args['attribute'], __('Choose an option', 'woocommerce'), $args['product']));
            ?>
                <div class="lukio_woocommerce_product_variations_dropdown hide_no_js no_js">
                    <span class="lukio_woocommerce_product_variations_dropdown_display" data-placeholder="<?php echo $placeholder; ?>"><?php echo $placeholder; ?></span>
                <?php
            }
                ?>

                <ul class="lukio_woocommerce_product_variations_ul <?php echo $class; ?> hide_no_js no_js" data-attr="<?php echo $esc_attr_name; ?>">
                    <?php
                    if (!empty($args['options'])) {
                        if ($args['product'] && taxonomy_exists($args['attribute'])) {
                            // Get terms if this is a taxonomy - ordered. We need the names too.
                            $terms = wc_get_product_terms(
                                $args['product']->get_id(),
                                $args['attribute'],
                                array(
                                    'fields' => 'all',
                                )
                            );
                            foreach ($terms as $term) {
                                if (in_array($term->slug, $args['options'], true)) {
                                    // add background color for a color button
                                    $style = '';
                                    if ($is_color) {
                                        $color = get_term_meta($term->term_id, $this::COLOR_META_KEY, true);
                                        $second = get_term_meta($term->term_id, $this::SECOND_COLOR_META_KEY, true);
                                        if (empty($second)) {
                                            $style = 'style="background-color:' . $color . ';"';
                                        } else {
                                            $style = 'style="background: linear-gradient(135deg, ' . $color . ' 51%, ' . $second . ' 50% 100%);"';
                                        }
                                    }
                    ?>
                                    <li class="lukio_woocommerce_product_variations_li <?php echo $class;
                                                                                        echo sanitize_title($args['selected']) == $term->slug ? ' selected' : ''; ?>" data-value="<?php echo esc_attr($term->slug); ?>" data-attr="<?php echo $esc_attr_name; ?>" <?php echo $style; ?>><?php echo apply_filters('lukio_product_variation_li_' . $args['attribute'], esc_html($term->name), $term, $args['product']); ?></li>
                                <?php
                                }
                            }
                        } else {
                            foreach ($args['options'] as $option) {
                                // This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
                                $selected = sanitize_title($args['selected']) === $args['selected'] ? ($args['selected'] == sanitize_title($option) ? ' selected' : '') : ($args['selected'] == $option ? ' selected' : '');
                                ?>
                                <li class="lukio_woocommerce_product_variations_li <?php echo $class . $selected; ?>" data-value="<?php echo esc_attr($option); ?>" data-attr="<?php echo $esc_attr_name; ?>"><?php echo apply_filters('lukio_product_variation_li_' . $args['attribute'], esc_html($option), null, $args['product']); ?></li>
                    <?php
                            }
                        }
                    }
                    ?>
                </ul>

                <?php
                if ($is_dropdown) {
                    // close the dropdown wrapper
                ?>
                </div>
            <?php
                }
            ?>

            <div class="hide_js no_js">
                <?php echo $html; ?>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * enqueue for wp admin
     * 
     * @author Itai Dotan
     */
    public function admin_enqueue()
    {
        $screen = get_current_screen();

        if ($screen->base === 'post' && $screen->post_type === 'product') {
            // enqueue needed for product page
            lukio_enqueue('/assets/js/product_admin.js', 'lukio_theme_product_admin', ['jquery'], ['parent' => true]);
            lukio_enqueue('/assets/css/product_admin.css', 'lukio_theme_product_admin', [], ['parent' => true]);
        }
    }

    /**
     * add bulk variations tab to wc product data tabs
     * 
     * @author Itai Dotan
     */
    public function add_bulk_variations_tab($tabs)
    {
        $tabs['lukio_bulk_img'] = array(
            'label'    => __('Bulk Variation Image', 'lukio-theme'),
            'target'   => 'lukio_product_bulk_variations_image',
            'class'    => array('show_if_variable'),
            'priority' => 65,
        );
        return $tabs;
    }

    /**
     * print the content of the bulk variations image tab
     * 
     * @author Itai Dotan
     */
    public function bulk_variations_tab_content()
    {
        global $product_object;
        $nonce = wp_create_nonce('lukio_bulk_variation_img');
        $attributes = array_filter($product_object->get_attributes(), function ($attribute) {
            return true === $attribute->get_variation();
        });
    ?>
        <div id="lukio_product_bulk_variations_image" class="panel woocommerce_options_panel hidden">
            <?php
            if (empty($attributes)) {
            ?>
                <p class="lukio_product_bulk_variations_image_empty"><?php echo __('Before you can add a variation image you need to add some variation attributes on the <strong>Attributes</strong> tab.', 'lukio-theme'); ?></p>
            <?php
            } else {
            ?>
                <div class="lukio_product_bulk_variations_image_img_wrapper">
                    <img class="lukio_product_bulk_variations_image_img" src="" alt="">
                    <input type="hidden" id="lukio_product_bulk_variations_image_input" name="lukio_product_bulk_variations_image_input">
                    <button class="button lukio_product_bulk_variations_image_picker" type="button"><?php echo __('Pick image', 'lukio-theme') ?></button>
                </div>

                <div class="lukio_product_bulk_variations_image_filters">
                    <?php
                    foreach ($attributes as  $attribute) {
                        $esc_attr_name = esc_attr(sanitize_title($attribute->get_name()));
                        $id = 'lukio_product_bulk_variations_image_select_' . $esc_attr_name;
                    ?>
                        <div class="lukio_product_bulk_variations_image_select_wrapper">
                            <label for="<?php echo $id ?>"><?php echo wc_attribute_label($attribute->get_name()); ?></label>
                            <select name="lukio_product_bulk_variations_image_select[<?php echo $esc_attr_name; ?>]" id="<?php echo $id; ?>">
                                <option value=""><?php echo __('Don\'t filter', 'lukio-theme'); ?></option>
                                <?php
                                if ($attribute->is_taxonomy()) {
                                    foreach ($attribute->get_terms() as $option) {
                                ?>
                                        <option value="<?php echo esc_attr($option->slug); ?>"><?php echo esc_html(apply_filters('woocommerce_variation_option_name', $option->name, $option, $attribute->get_name(), $product_object)); ?></option>
                                    <?php
                                    }
                                    ?>
                                    <?php } else {
                                    foreach ($attribute->get_options() as $option) {
                                    ?>
                                        <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html(apply_filters('woocommerce_variation_option_name', $option, null, $attribute->get_name(), $product_object)); ?></option>
                                <?php }
                                }
                                ?>
                            </select>
                        </div>
                    <?php
                    }

                    ?>
                </div>
                <div class="lukio_product_bulk_variations_image_btns_wrapper">
                    <button class="button button-primary lukio_product_bulk_variations_image_btn" type="button" data-action="set"><?php echo __('Set image', 'lukio-theme') ?></button>
                    <button class="button lukio_product_bulk_variations_image_btn" type="button" data-action="remove"><?php echo __('Remove image', 'lukio-theme') ?></button>
                </div>
            <?php
            }
            ?>
            <input type="hidden" id="lukio_product_bulk_variations_image_nonce" name="lukio_product_bulk_variations_image_nonce" value="<?php echo $nonce; ?>">
        </div>
<?php
    }

    /**
     * ajax reload image for the bulk variations preview
     * 
     * @author Itai Dotan
     */
    public function bulk_variations_image_preview()
    {
        $image_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($image_id) {
            $image_src = wp_get_attachment_image_src($image_id, 'thumbnail');
            $data = array(
                'success' => true,
                'image_src' => $image_src ? $image_src[0] : '',
            );
        } else {
            $data = array(
                'success' => false,
            );
        }
        echo json_encode($data);
        die;
    }

    /**
     * bulk set variations image by the selected filters
     * 
     * @author Itai Dotan
     */
    public function bulk_variations_image_set()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'lukio_bulk_variation_img')) {
            die;
        }

        $args = array(
            'post_type' => 'product_variation',
            'posts_per_page' => -1,
            'post_parent' => (int)$_POST['post_id'],
            'fields' => 'ids',
        );
        parse_str($_POST['filters_str'], $filters);
        foreach ($filters['lukio_product_bulk_variations_image_select'] as $attr => $value) {
            // $value = sanitize_text_field($value);
            if ($value == '') {
                continue;
            }
            if (isset($args['meta_query']) && !isset($args['meta_query']['relation'])) {
                $args['meta_query']['relation'] = 'AND';
            }

            $args['meta_query'][] = array(
                'key' => 'attribute_' . $attr,
                'value' => $value
            );
        }

        $query = new WP_Query($args);

        $img_id = (int)$_POST['image_id'];
        foreach ($query->posts as $post_id) {
            $action = sanitize_text_field($_POST['type']);
            if ($img_id === 0 || $action === 'remove') {
                delete_post_meta($post_id, '_thumbnail_id');
            } else {
                update_post_meta($post_id, '_thumbnail_id', $img_id);
            }
        }

        echo json_encode(array('success' => true));
        die;
    }

    /**
     * ajax reload of bulk variations image tab
     * 
     * @author Itai Dotan
     */
    public function reload_bulk_variations_tab_content()
    {
        if (!wp_verify_nonce($_GET['nonce'], 'lukio_bulk_variation_img')) {
            die;
        }

        global $product_object;
        $product_object = wc_get_product((int)$_GET['post_id']);
        ob_start();
        $this->bulk_variations_tab_content();
        $fragment = ob_get_clean();

        echo json_encode(array(
            'fragment' => $fragment
        ));
        die;
    }
}
new Lukio_Woocommerce_Setup();

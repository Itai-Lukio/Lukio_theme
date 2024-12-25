<?php

namespace Lukio_Theme\Helpers\Woocommerce;

use WC_Coupon, WP_query;

/**
 * tool allow duplication of woocommerce coupons
 */
class Coupon_Duplication
{
    protected $duplicate_priority = 100;
    protected $checkbox_id = 'lu_duplicate_checkbox';
    protected $amount_id = 'lu_duplicate_amount';
    protected $new_indicator_id = 'lu_duplicate_new';

    private $generated_codes = array();

    /**
     * add the needed hooks
     */
    public function __construct()
    {
        add_action('woocommerce_coupon_options_save', array($this, 'duplicate'), $this->duplicate_priority, 2);

        add_filter('woocommerce_coupon_data_tabs', array($this, 'add_duplicate_tab'));
        add_action('woocommerce_coupon_data_panels', array($this, 'duplicate_tab_content'));
    }

    /**
     * check if this is the add new coupon page
     * 
     * @return bool `true` when new coupon page
     * 
     * @author Itai Dotan
     */
    public static function is_new_coupon()
    {
        return strpos($_SERVER['SCRIPT_NAME'], 'post-new.php') !== false;
    }

    /**
     * add duplicate tab to wc coupon panel
     * 
     * @param  array $tabs wc tabs index
     * @return array updated $tabs
     * 
     * @author Itai Dotan
     */
    public function add_duplicate_tab($tabs)
    {
        $tabs['duplicate_tab'] = array(
            'label'  => __('Duplicate', 'lukio-theme'),
            'target' => 'lu_duplicate_tab_coupon_data',
            'class'  => '',
        );

        return $tabs;
    }

    /**
     * print the duplicate tab content
     * 
     * @author Itai Dotan
     */
    public function duplicate_tab_content()
    {
?>
        <div id="lu_duplicate_tab_coupon_data" class="panel woocommerce_options_panel">
            <?php
            woocommerce_wp_checkbox(
                array(
                    'id'          => $this->checkbox_id,
                    'label'       => __('Duplicate coupon', 'lukio-theme'),
                    'description' => __('When checked the coupon will be duplicated by the given amount.<br>Coupon code will be used as a prefix, if given, added by a random string.', 'lukio-theme'),
                    'checked_value' => 'yes'
                )
            );

            woocommerce_wp_text_input(
                array(
                    'id'                => $this->amount_id,
                    'label'             => __('Amount of times to duplicate the coupon', 'lukio-theme'),
                    'desc_tip'          => true,
                    'type'              => 'number',
                    'custom_attributes' => array(
                        'step' => 1,
                        'min'  => 0,
                    ),
                    'value'             => 0,
                )
            );

            if ($this->is_new_coupon()) {
            ?>
                <input type="hidden" name="<?php echo $this->new_indicator_id; ?>" value="1">
            <?php
            }
            ?>

            <script>
                jQuery(function($) {
                    function handle_amonut_display(checkbox = null) {
                        if (checkbox == null) {
                            checkbox = $('#lu_duplicate_checkbox');
                        }

                        $('.lu_duplicate_amount_field')[checkbox.prop('checked') ? 'show' : 'hide']();
                    }

                    $(document)
                        .on('change', '#lu_duplicate_checkbox', function() {
                            handle_amonut_display($(this));
                        });
                });
            </script>
            <style>
                .lu_duplicate_amount_field {
                    display: none;
                }
            </style>
        </div>
<?php
    }

    /**
     * create random coupon code
     * 
     * @param  string $prefix prefix for the code, will add `_` between prefix and code when a prefix is given, default `''`
     * @param  int    $length length of the code to generate, default `6`
     * @return string generated coupon code
     * 
     * @author Itai Dotan
     */
    private function generate_coupon_code($prefix = '', $length = 6)
    {
        $characters = apply_filters('lukio_theme_coupons_characters', '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        $code = $prefix . $randomString;

        if (!$this->is_code_avilable($code)) {
            // try to create a new code
            return $this->generate_coupon_code($prefix, $length + 1);
        }

        $this->generated_codes[] = $code;
        return $code;
    }

    /**
     * check if the code is avilable to use for a new coupon
     * 
     * @param  string $code code to check its availability
     * @return bool `true` when the code is availability to use
     * 
     * @author Itai Dotan
     */
    private function is_code_avilable($code)
    {
        if (in_array($code, $this->generated_codes)) {
            return false;
        }

        $coupon = new WP_query(array(
            'post_type' => 'shop_coupon',
            'title' => $code,
            'posts_per_page' => 1
        ));

        if ($coupon->found_posts == 1) {
            return false;
        }

        return true;
    }

    /**
     * change the coupon name for the original copy only when creating a new coupon
     * 
     * @param WC_Coupon $coupon coupon object
     * 
     * @author Itai Dotan
     */
    private function maybe_change_new_coupon_name($coupon)
    {
        if (!isset($_POST[$this->new_indicator_id])) {
            return;
        }

        $coupon->set_code($this->generate_coupon_code($coupon->get_code()));
        $coupon->save();
    }

    /**
     * create duplicate coupons when needed
     * 
     * @param int       $post_id coupon post id
     * @param WC_Coupon $coupon  coupon object
     * 
     * @author Itai Dotan
     */
    public function duplicate($post_id, $coupon)
    {
        if (!is_a($coupon, 'WC_Coupon') || !isset($_POST[$this->checkbox_id], $_POST[$this->amount_id]) || intval($_POST[$this->amount_id]) < 1) {
            return;
        }

        remove_action('woocommerce_coupon_options_save', array($this, 'duplicate'), $this->duplicate_priority);

        $amount = intval($_POST[$this->amount_id]);
        $prefix = $coupon->get_code();

        $this->maybe_change_new_coupon_name($coupon);

        $original_data = $coupon->get_data();
        unset($original_data['id']);
        for ($i = 0; $i < $amount; $i++) {
            $dup_coupon = new WC_Coupon();

            $original_data['code'] = $this->generate_coupon_code($prefix);
            $dup_coupon->set_props($original_data);

            if (isset($original_data['meta_data'])) {
                // save coupon meta
                foreach ($original_data['meta_data'] as $wc_meta_data) {
                    $meta_data = $wc_meta_data->get_data();
                    $dup_coupon->update_meta_data($meta_data['key'], $meta_data['value']);
                }
            }

            $dup_coupon->save();
        }

        do_action('lukio_theme_coupons_duplicated', $this->generated_codes);
    }
}

new Coupon_Duplication();

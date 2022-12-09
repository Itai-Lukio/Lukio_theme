<?php
if (!function_exists('lukio_add_option_menu')) {
    /**
     * add the lukio options menu
     */
    function lukio_add_option_menu()
    {
        add_menu_page(
            __('Lukio options', 'lukio-theme'),
            __('Lukio options', 'lukio-theme'),
            'manage_options',
            'lukio_theme_options',
            'lukio_theme_option_page_callable',
            'dashicons-admin-generic',
            2
        );
    }
}
add_action('admin_menu', 'lukio_add_option_menu');

if (!function_exists('lukio_theme_option_page_callable')) {
    /**
     * lukio options menu callable
     */
    function lukio_theme_option_page_callable()
    {
        // 
        // min toggle when the user is admin
        //
?>
        <textarea name="lukio_pixels_head" id="" cols="30" rows="10"><?php echo get_option('lukio_pixels_head') ?></textarea>
        <textarea name="lukio_pixels_body" id="" cols="30" rows="10"><?php echo get_option('lukio_pixels_body') ?></textarea>
        <?php
        $colors_options = get_option('lukio_site_colors');
        $colors_options =  $colors_options != '' ? json_decode($colors_options) : array();
        foreach ($colors_options as $index => $per) {
            $edit_name = (in_array('administrator', wp_get_current_user()->roles)) ? true : false;
        ?>
            <div>
                <input type="text" class="lukio_theme_option_css_name" name="css_name[]" value="<?php echo $per->css_name; ?>" <?php echo !$edit_name ? ' disabled' : ''; ?>>
                <input type="text" class="lukio_theme_option_color_picker" name="color[]" id="button_color" value="<?php echo $per->color; ?>" autocomplete="off">
            </div>
<?php
        }
    }
}

//temp trigger update
add_action('init', function () {
    do_action('lukio_theme_updated');
});

if (!function_exists('lukio_option_page_enqueue')) {
    function lukio_option_page_enqueue()
    {
        if (get_current_screen()->base == 'toplevel_page_lukio_theme_options') {
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
            lukio_enqueue('/assets/js/lukio_option_page.js', 'lukio_theme_option_page', ['jquery'], ['parent' => true]);
        };
    }
}
add_action('admin_enqueue_scripts', 'lukio_option_page_enqueue');

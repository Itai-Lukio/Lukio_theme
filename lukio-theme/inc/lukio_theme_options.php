<?php
if (!function_exists('lukio_add_option_menu')) {
    /**
     * add the lukio options menu
     * 
     * @author Itai Dotan
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
     * 
     * @author Itai Dotan
     */
    function lukio_theme_option_page_callable()
    {
        $allowed_roles =  apply_filters('lukio_theme_options_full_access_roles', array('administrator'));
        $full_access_bool = count(array_intersect($allowed_roles, wp_get_current_user()->roles)) > 0;

        // run the save function to save when posted
        lukio_theme_option_save($full_access_bool);
?>
        <form class="lukio_theme_option_form" method="POST">
            <?php
            if ($full_access_bool) {
                // output the enqueue switch
            ?>
                <div>
                    <span><?php echo __('Disable enqueue min files', 'lukio-theme'); ?></span>
                    <label class="lukio_theme_option_enqueue_switch">
                        <input class="lukio_theme_option_enqueue_switch_input" type="checkbox" name="lukio_disable_enqueue_min" autocomplete="off" <?php echo (bool)get_option('lukio_disable_enqueue_min') ? ' checked' : ''; ?>>
                        <span class="lukio_theme_option_enqueue_switch_slider"></span>
                    </label>
                </div>
            <?php
            }
            ?>

            <div class="lukio_theme_option_pixels_wrappair">
                <textarea name="lukio_pixels_head" cols="30" rows="10"><?php echo get_option('lukio_pixels_head'); ?></textarea>
                <textarea name="lukio_pixels_body" cols="30" rows="10"><?php echo get_option('lukio_pixels_body'); ?></textarea>
            </div>
            <div class="lukio_theme_option_colors_wrappair">
                <?php
                if ($full_access_bool) {
                    // output color row templete to be used when adding a new row
                ?>
                    <div class="lukio_theme_option_color_pair template">
                        <input type="text" class="lukio_theme_option_css_name" name="css_name_%d" data-name-format="css_name_%d" disabled>
                        <input type="text" class="lukio_theme_option_color_picker" name="color_%d" data-name-format="color_%d" autocomplete="off" disabled>
                        <button class="lukio_theme_option_remove_row button" type="button"><?php echo __('Remove row', 'lukio-theme'); ?></button>
                    </div>
                <?php
                }
                $colors_options = get_option('lukio_site_colors');
                $colors_options =  $colors_options != '' ? json_decode($colors_options, true) : array();
                foreach ($colors_options as $index => $pair) {
                ?>
                    <div class="lukio_theme_option_color_pair">
                        <?php
                        if ($full_access_bool) {
                        ?>
                            <input type="text" class="lukio_theme_option_css_name" name="css_name_<?php echo $index; ?>" data-name-format="css_name_%d" value="<?php echo $pair['css_name']; ?>">
                        <?php
                        } else {
                        ?>
                            <span><?php echo $pair['css_name']; ?></span>
                        <?php
                        }
                        ?>
                        <input type="text" class="lukio_theme_option_color_picker" name="color_<?php echo $index; ?>" value="<?php echo $pair['color']; ?>" data-name-format="color_%d" autocomplete="off">
                        <?php
                        if ($full_access_bool) {
                        ?>
                            <button class="lukio_theme_option_remove_row button" type="button"><?php echo __('Remove row', 'lukio-theme'); ?></button>
                        <?php
                        }
                        ?>
                    </div>
                <?php
                }

                if ($full_access_bool) {
                ?>
                    <button class="lukio_theme_option_add_row button" type="button"><?php echo __('Add row', 'lukio-theme'); ?></button>
                <?php
                }
                ?>
                <input type="hidden" id="lukio_theme_option_loop_count" name="loop_count" value="<?php echo count($colors_options); ?>" autocomplete="off">
            </div>

            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('lukio_theme_option'); ?>">
            <button class="lukio_theme_option_submit button-primary" type="submit"><?php echo __('Save options', 'lukio-theme'); ?></button>
        </form>
<?php
    }
}

if (!function_exists('lukio_theme_option_save')) {
    /**
     * update lukio options
     * 
     * @author Itai Dotan
     */
    function lukio_theme_option_save($full_access_bool)
    {
        if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'lukio_theme_option')) {
            // update the enqueue option when the user has full access to the options
            if ($full_access_bool) {
                update_option('lukio_disable_enqueue_min', isset($_POST['lukio_disable_enqueue_min']));
            }

            $colors_options = get_option('lukio_site_colors');
            $colors_options =  $colors_options != '' ? json_decode($colors_options, true) : array();
            $row_count = (int)$_POST['loop_count'];

            // when the new colors pairs are less then the saved pairs trim the saved pairs to match
            if (($row_count - 1) < count($colors_options)) {
                $colors_options = array_slice($colors_options, 0, $row_count);
            }

            // loop over the rows and update the row when applicable
            for ($i = 0; $i < $row_count; $i++) {
                $update_pair = isset($colors_options[$i]) ? $colors_options[$i] : array();
                $track_update = 0;
                if ($full_access_bool && isset($_POST['css_name_' . $i]) && $_POST['css_name_' . $i] != '') {
                    $update_pair['css_name'] = $_POST['css_name_' . $i];
                    $track_update++;
                }
                if (isset($_POST['color_' . $i]) && $_POST['color_' . $i] != '') {
                    $update_pair['color'] = $_POST['color_' . $i];
                    $track_update++;
                }
                if (($full_access_bool && $track_update == 2) || (!$full_access_bool && $track_update == 1)) {
                    $colors_options[$i] = $update_pair;
                }
            }
            update_option('lukio_site_colors', json_encode($colors_options));
            lukio_theme_options_update_css($colors_options);

            // update pixels
            update_option('lukio_pixels_head', stripslashes($_POST['lukio_pixels_head']));
            update_option('lukio_pixels_body', stripslashes($_POST['lukio_pixels_body']));
        }
    }
}

if (!function_exists('lukio_theme_options_update_css')) {
    /**
     * create update or delete site_colors.css 
     * 
     * @author Itai Dotan
     */
    function lukio_theme_options_update_css($colors_options)
    {
        $stylesheet_directory = get_stylesheet_directory();
        $site_color_path = $stylesheet_directory . '/assets/css/site_colors.css';
        if (empty($colors_options)) {
            // delete the site_colors.css if exists
            if (file_exists($site_color_path)) {
                unlink($site_color_path);
            }
        } else {
            if (!file_exists($stylesheet_directory . '/assets/css')) {
                // create the dir path when needed
                mkdir(get_stylesheet_directory() . '/assets/css', 0777, true);
            }
            $file = fopen($site_color_path, "w");
            fwrite($file, ":root{");
            foreach ($colors_options as $row) {
                fwrite($file, $row['css_name'] . ': ' . $row['color'] . ";");
            }
            fwrite($file, "}");
            fclose($file);
        }
    }
}

if (!function_exists('lukio_option_page_enqueue')) {
    /**
     * enqueue the needed scripts and styles for lukio option page
     * 
     * @author Itai Dotan
     */
    function lukio_option_page_enqueue()
    {
        if (get_current_screen()->base == 'toplevel_page_lukio_theme_options') {
            wp_enqueue_style('wp-color-picker');
            lukio_enqueue('/assets/js/lukio_option_page.js', 'lukio_theme_option_page', ['jquery', 'wp-color-picker'], ['parent' => true]);
            lukio_enqueue('/assets/css/lukio_option_page.css', 'lukio_theme_option_page', [], ['parent' => true]);
        };
    }
}
add_action('admin_enqueue_scripts', 'lukio_option_page_enqueue');

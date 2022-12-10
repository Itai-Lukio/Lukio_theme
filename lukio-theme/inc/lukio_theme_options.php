<?php

/**
 * lukio theme options class to add display and update the admin options
 */
class Lukio_Theme_Options
{
    /**
     * add the actions of this class
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'lukio_add_option_menu'));
        add_action('admin_enqueue_scripts', array($this, 'lukio_option_page_enqueue'));
    }

    /**
     * add the lukio options menu
     * 
     * @author Itai Dotan
     */
    public function lukio_add_option_menu()
    {
        add_menu_page(
            __('Lukio options', 'lukio-theme'),
            __('Lukio options', 'lukio-theme'),
            'manage_options',
            'lukio_theme_options',
            array($this, 'lukio_theme_option_page_callable'),
            'dashicons-admin-generic',
            2
        );
    }

    /**
     * enqueue the needed scripts and styles for lukio option page
     * 
     * @author Itai Dotan
     */
    public function lukio_option_page_enqueue()
    {
        if (get_current_screen()->base == 'toplevel_page_lukio_theme_options') {
            wp_enqueue_style('wp-color-picker');
            lukio_enqueue('/assets/js/lukio_option_page.js', 'lukio_theme_option_page', ['jquery', 'wp-color-picker'], ['parent' => true]);
            lukio_enqueue('/assets/css/lukio_option_page.css', 'lukio_theme_option_page', [], ['parent' => true]);
        };
    }

    /**
     * lukio options menu callable
     * 
     * @author Itai Dotan
     */
    public function lukio_theme_option_page_callable()
    {
        $colors_options = get_option('lukio_site_colors');
        $colors_options =  $colors_options != '' ? json_decode($colors_options, true) : array();

        $allowed_roles =  apply_filters('lukio_theme_options_full_access_roles', array('administrator'));
        $full_access_bool = count(array_intersect($allowed_roles, wp_get_current_user()->roles)) > 0;

        // run the save function to save when posted
        $this->lukio_theme_option_save($colors_options, $full_access_bool);
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

                foreach ($colors_options as $index => $pair) {
                ?>
                    <div class="lukio_theme_option_color_pair">
                        <?php
                        if ($full_access_bool) {
                            // editable input when have full access 
                        ?>
                            <input type="text" class="lukio_theme_option_css_name" name="css_name_<?php echo $index; ?>" data-name-format="css_name_%d" value="<?php echo $pair['css_name']; ?>">
                        <?php
                        } else {
                            // display name when not editable
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

            <input type="hidden" name="action" value="lukio_theme_option_save">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('lukio_theme_option'); ?>">
            <button class="lukio_theme_option_submit button-primary" type="submit"><?php echo __('Save options', 'lukio-theme'); ?></button>
        </form>
<?php
    }

    /**
     * update lukio options
     * 
     * modify $colors_options according to the posted data
     * 
     * @param Array $colors_options lukio_site_colors option
     * @param Bool $full_access_bool true when the form is with full access, false otherwise
     * 
     * @author Itai Dotan
     */
    private function lukio_theme_option_save(&$colors_options, $full_access_bool)
    {
        if (
            isset($_POST['action']) && $_POST['action'] == 'lukio_theme_option_save' &&
            isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'lukio_theme_option')
        ) {
            // update the enqueue option when the user has full access to the options
            if ($full_access_bool) {
                update_option('lukio_disable_enqueue_min', isset($_POST['lukio_disable_enqueue_min']));
            }

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

                // make sure valid input was updated in the row before updateing the colors array
                if (($full_access_bool && $track_update == 2) || (!$full_access_bool && $track_update == 1)) {
                    $colors_options[$i] = $update_pair;
                }
            }
            update_option('lukio_site_colors', json_encode($colors_options));
            $this->lukio_theme_options_update_css($colors_options);

            // update pixels
            update_option('lukio_pixels_head', stripslashes($_POST['lukio_pixels_head']));
            update_option('lukio_pixels_body', stripslashes($_POST['lukio_pixels_body']));
        }
    }

    /**
     * create update or delete site_colors.css 
     * 
     * @param Array $colors_options lukio_site_colors option
     * 
     * @author Itai Dotan
     */
    private function lukio_theme_options_update_css($colors_options)
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

new Lukio_Theme_Options();

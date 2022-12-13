<?php

if (!function_exists('lukio_theme_update')) {
    /**
     * based on https://rudrastyh.com/wordpress/theme-updates-from-custom-server.html
     * 
     * @author Itai Dotan
     */
    function lukio_theme_update($transient)
    {
        // prevent run when in mid update
        if (!$transient) {
            return $transient;
        }

        $stylesheet = get_template();

        $theme = wp_get_theme($stylesheet);
        $version = $theme->get('Version');

        if (false == $remote = get_transient('lukio_theme_update' . $version)) {

            // connect to a remote server where the update information is stored
            $remote = wp_remote_get(
                $theme->get('ThemeURI') . '/raw/main/info.json',
                array(
                    'timeout' => 10,
                    'headers' => array(
                        'Accept' => 'application/json'
                    )
                )
            );

            // do nothing if errors
            if (
                is_wp_error($remote)
                || 200 !== wp_remote_retrieve_response_code($remote)
                || empty(wp_remote_retrieve_body($remote))
            ) {
                return $transient;
            }

            // encode the response body
            $remote = json_decode(wp_remote_retrieve_body($remote));

            if (!$remote) {
                return $transient; // who knows, maybe JSON is not valid
            }
            set_transient('lukio_theme_update' . $version, $remote, HOUR_IN_SECONDS);
        }

        $data = array(
            'theme' => $stylesheet,
            'url' => $remote->details_url,
            'requires' => $remote->requires,
            'requires_php' => $remote->requires_php,
            'new_version' => $remote->version,
            'package' => $remote->download_url,
        );

        // check all the versions now
        if (
            $remote
            && version_compare($version, $remote->version, '<')
            && version_compare($remote->requires, get_bloginfo('version'), '<')
            && version_compare($remote->requires_php, PHP_VERSION, '<')
        ) {

            $transient->response[$stylesheet] = $data;
        } else {

            $transient->no_update[$stylesheet] = $data;
        }

        return $transient;
    }
}
add_filter('site_transient_update_themes', 'lukio_theme_update');

if (!function_exists('lukio_check_upgrade_completed')) {
    /**
     * run when an update process is complete and when its for the update of the theme, do all action of the hook
     * 
     * @param $upgrader_object Array
     * @param $options Array
     * 
     * @author Itai Dotan
     */
    function lukio_check_upgrade_completed($upgrader_object, $options)
    {
        if ($options['action'] == 'update' && $options['type'] == 'theme' && in_array(get_template(), $options['themes'])) {
            wp_schedule_single_event(time(), 'lukio_theme_trigger_updated');
        }
    }
}
add_action('upgrader_process_complete', 'lukio_check_upgrade_completed', 10, 2);

if (!function_exists('lukio_theme_trigger_upgrade_completed_hooks')) {
    /**
     * scheduled task to run after theme update. trigger all actions hooked to 'lukio_theme_updated'
     * 
     * @author Itai Dotan
     */
    function lukio_theme_trigger_upgrade_completed_hooks()
    {
        do_action('lukio_theme_updated');
    }
}
add_action('lukio_theme_trigger_updated', 'lukio_theme_trigger_upgrade_completed_hooks');

// trigger the custom user role to run after an update
add_action('lukio_theme_updated', 'lukio_custom_user_role');

// trigger the option create to make sure they are created
add_action('lukio_theme_updated', 'lukio_create_options');

if (!function_exists('lukio_upgrade_from_acf_to_menu')) {
    /**
     * upgrade from the acf option to lukio option when needed
     * 
     * @author Itai Dotan
     */
    function lukio_upgrade_from_acf_to_menu()
    {
        $old_theme_option = get_template_directory() . '/acf-json/group_62c6f6db79755.json';

        // check if the old option isnt already been processed 
        if (!file_exists($old_theme_option)) {
            return;
        }

        // make sure acf is active
        if (function_exists('get_field')) {

            $acf_pixels_data = get_field('pixels', 'options');
            $pixels = array(
                'head_scripts' => 'lukio_pixels_head',
                'body_opening_scripts' => 'lukio_pixels_body',
            );
            // upgrade the pixels if needed
            foreach ($pixels as $acf_key => $option) {
                // get the acf option and updated to lukio option when havent done it before
                if ($acf_pixels_data && $acf_pixels_data[$acf_key] != 'lukio_updated') {
                    update_option($option, $acf_pixels_data[$acf_key]);
                    $acf_pixels_data[$acf_key] = 'lukio_updated';
                    // set the acf option as been upgraded
                    update_field('pixels', $acf_pixels_data, 'options');
                }
            }

            // upgrade the site_colors if needed
            $acf_site_colors = get_field('lukio_site_colors', 'options');
            if ($acf_site_colors && is_array($acf_site_colors) && $acf_site_colors[0]['css_name'] != 'lukio_updated') {
                $site_colors = [];
                foreach ($acf_site_colors as $row) {
                    $site_colors[] = array(
                        'css_name' => $row['css_name'],
                        'color' => $row['color'],
                    );
                }
                update_option('lukio_site_colors', json_encode($site_colors));
                update_field('lukio_site_colors', array(
                    array(
                        'css_name' => 'lukio_updated',
                        'color' => 'lukio_updated',
                    )
                ), 'options');
            }
        }

        // mark the old option json as deprecated
        rename($old_theme_option, $old_theme_option . '.deprecated');
    }
}
add_action('lukio_theme_updated', 'lukio_upgrade_from_acf_to_menu', 20);
add_action('after_switch_theme', 'lukio_upgrade_from_acf_to_menu');

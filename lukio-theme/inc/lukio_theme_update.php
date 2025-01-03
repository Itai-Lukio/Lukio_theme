<?php
defined('ABSPATH') || exit;

/**
 * lukio theme update class
 */
class Lukio_Theme_Update
{
    /**
     * construct action to run when creating a new instance
     * 
     * @author Itai Dotan
     */
    public function __construct()
    {
        add_filter('site_transient_update_themes', array($this, 'theme_update'));
        add_action('upgrader_process_complete', array($this, 'check_upgrade_completed'), 10, 2);
        add_action('lukio_theme_updated', array($this, 'upgrade_from_acf_to_menu'), 20);
    }

    /**
     * based on https://rudrastyh.com/wordpress/theme-updates-from-custom-server.html
     * 
     * @author Itai Dotan
     */
    public function theme_update($transient)
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

    /**
     * run when an update process is complete and when its for the update of the theme, do all action of the hook
     * 
     * @param array $upgrader_object WP_Upgrader instance
     * @param array $options array of bulk item update data
     * 
     * @author Itai Dotan
     */
    public function check_upgrade_completed($upgrader_object, $options)
    {
        if ($options['action'] == 'update' && $options['type'] == 'theme' && in_array(get_template(), $options['themes'])) {
            wp_schedule_single_event(time(), 'lukio_theme_updated');
        }
    }

    /**
     * upgrade from the acf option to lukio option when needed
     * 
     * @author Itai Dotan
     */
    public function upgrade_from_acf_to_menu()
    {
        $pixels = array(
            'options_pixels_head_scripts' => 'lukio_pixels_head',
            'options_pixels_body_opening_scripts' => 'lukio_pixels_body',
        );
        // upgrade the pixels if needed and delete the old option
        foreach ($pixels as $acf_key => $option) {
            $pixel_option = get_option($acf_key);
            if ($pixel_option && $pixel_option != 'lukio_updated') {
                update_option($option, $pixel_option);
                delete_option($acf_key);
            }
        }

        // upgrade the site_colors if needed and delete the old option
        $acf_site_colors_count = (int)get_option('options_lukio_site_colors');
        if ($acf_site_colors_count && $acf_site_colors_count != 'lukio_updated') {
            $site_colors = [];
            for ($i = 0; $i < $acf_site_colors_count; $i++) {
                $site_colors[] = array(
                    'css_name' => get_option('options_lukio_site_colors_' . $i . '_css_name'),
                    'color' => get_option('options_lukio_site_colors_' . $i . '_color'),
                );
                delete_option('options_lukio_site_colors_' . $i . '_css_name');
                delete_option('options_lukio_site_colors_' . $i . '_color');
            }
            update_option('lukio_site_colors', json_encode($site_colors));
            delete_option('options_lukio_site_colors');
        }
    }
}
new Lukio_Theme_Update();

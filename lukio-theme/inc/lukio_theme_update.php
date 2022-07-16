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
                'https://raw.githubusercontent.com/Itai-Lukio/Lukio_theme/main/info.json',
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

    add_filter('site_transient_update_themes', 'lukio_theme_update');
}

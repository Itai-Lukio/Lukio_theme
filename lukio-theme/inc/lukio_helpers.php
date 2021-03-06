<?php
if (!function_exists('lukio_enqueue')) {
    /**
     * enqueue the given loacl script and stylesheet files or url. normal file for admin and min file for any one eles if exists.
     *
     * @param String $path full file path inside the theme folder.
     * @param String $name name to use in the enqueue, default null to use $path.
     * @param Array $deps an array of registered dependents.
     * @param Array $user_extras 'parent' => for parent theme enqueue, default false. 'in_footer' => for script enqueue in the footer, default false. 'media' => for style enqueue media type, default 'all'. 'version' version to set in the enqueue when its a url, default '1.0'.
     * 
     * @author Itai Dotan
     */
    function lukio_enqueue($path, $name = null, $deps = array(), $user_extras = array())
    {
        $file_enqueue = true;
        if (strpos($path, 'http') === 0) {
            $file_enqueue = false;
        }

        // get the file extension offset and file type
        if (substr($path, -3) == '.js') {
            $path_sub_offset = -3;
            $file_type = '.js';
        } else {
            $path_sub_offset = -4;
            $file_type = '.css';
        }

        $default_extras = array(
            'parent' => false,
            'in_footer' => false,
            'media' => 'all',
            'version' => 1.0,
        );
        $active_extras = array_merge($default_extras, $user_extras);

        if ($file_enqueue) {
            // setup for local file enqueue

            global $lukio_enqueue_admin_status;
            // set $lukio_enqueue_admin_status if not set yet
            if (!isset($lukio_enqueue_admin_status)) {
                $allowed_roles = array('administrator');
                $lukio_enqueue_admin_status = count(array_intersect($allowed_roles, wp_get_current_user()->roles)) > 0;
            }

            // setup the file path and uri path
            if ($active_extras['parent']) {
                $directory = get_template_directory();
                $directory_uri = get_template_directory_uri();
            } else {
                $directory = get_stylesheet_directory();
                $directory_uri = get_stylesheet_directory_uri();
            }

            $name = is_null($name) ? $path : $name;

            // remove file extension for min testing
            $path = substr($path, 0, $path_sub_offset);

            // set the enqueue path
            $enqueue = (!$lukio_enqueue_admin_status && file_exists($directory . $path . '.min' . $file_type)) ? $path . '.min' . $file_type : $path . $file_type;
            $path = $directory_uri . $enqueue;

            $active_extras['version'] = filemtime($directory . $enqueue);
        } else {
            $name = array_slice(explode('/', $path), -1)[0];
        }

        if ($file_type == '.css') {
            wp_enqueue_style(
                $name,
                $path,
                $deps,
                $active_extras['version'],
                $active_extras['media']
            );
        } else {
            wp_enqueue_script(
                $name,
                $path,
                $deps,
                $active_extras['version'],
                $active_extras['in_footer']
            );
        }
    }
}

if (!function_exists('lukio_footer_credit')) {
    /**
     * echo the lukio credit.
     * 
     * @param Bool $dark_mode to use dark mode. default false
     * @param Bool $eng to use english text. default false
     * 
     * @author Itai Dotan
     */
    function lukio_footer_credit($dark_mode = false, $eng = false)
    {
        $link = 'https://lukio.pro';
        if ($eng) {
            $text = 'powerd by';
            $direction = 'ltr';
        } else {
            $text = '???????? ?????????????? ??????';
            $direction = 'rtl';
        }
        $mode = $dark_mode ? 'dark_mode' : 'light_mode';
        $svg = '<svg id="lukio_credit_svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 107.87 40.12"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="lukio_footer_svg_u" d="M24.14,29c-7.8,0-13-5-13-12.47V1h9.68V16.57c0,2.39,1.1,3.56,3.36,3.56s3.52-1.22,3.52-3.61V1h9.68V16.57C37.34,24,32,29,24.14,29Z"/><path class="lukio_footer_svg_u_border" d="M36.34,2V16.57C36.34,23.29,31.73,28,24.15,28s-12-4.75-12-11.47V2h7.68V16.57c0,3,1.58,4.56,4.37,4.56s4.51-1.59,4.51-4.61V2h7.68m2-2H26.66V16.52c0,1.85-.73,2.61-2.51,2.61-1.43,0-2.37-.44-2.37-2.56V0H10.1V16.57C10.1,24.63,15.74,30,24.15,30s14.19-5.41,14.19-13.47V0Z"/><path class="lukio_footer_svg_text" d="M93.93,12.18a13.55,13.55,0,0,0-14,14,13.95,13.95,0,1,0,27.89,0C107.87,17.86,101.84,12.2,93.93,12.18Zm0,19.9a5.93,5.93,0,0,1,0-11.86,5.93,5.93,0,1,1,0,11.86Z"/><polygon class="lukio_footer_svg_text" points="76.7 12.81 69.02 12.81 69.02 39.4 76.7 39.4 76.7 39.4 76.7 39.4 76.7 12.81 76.7 12.81 76.7 12.81"/><polygon class="lukio_footer_svg_text" points="7.68 2.08 0 2.08 0 32.2 0 39.4 7.68 39.4 35.95 39.4 35.95 32.2 7.68 32.2 7.68 2.08"/><polygon class="lukio_footer_svg_text" points="67.11 12.81 58.03 12.81 48.38 25 48.38 2.08 40.7 2.08 40.7 39.4 48.38 39.4 48.38 26.54 58.03 39.4 67.44 39.4 56.74 25.48 67.11 12.81"/><path class="lukio_footer_svg_text" d="M72.89,10.3a4.15,4.15,0,1,0-4.23-4.17A4.14,4.14,0,0,0,72.89,10.3Z"/></g></g></svg>';

        echo '<a id="lukio_credit" class="' . $direction . ' ' . $mode . '" href="' . $link . '" target="_blank">' . $text . $svg . '</a>';
    }
}

if (!function_exists('lukio_remove_p_tags')) {
    /**
     * Remove <p> tags from string
     * 
     * @param String $string string to remove tags from
     * @return String cleared string
     * 
     * @author Itai Dotan
     */
    function lukio_remove_p_tags($string)
    {
        return str_replace(array('<p>', '</p>'), '', $string);
    }
}

if (!function_exists('lukio_remove_br_tags')) {
    /**
     * Remove <br> tags from string
     * 
     * @param String $string string to remove tags from
     * @return String cleared string
     * 
     * @author Itai Dotan
     */
    function lukio_remove_br_tags($string)
    {
        return str_replace(array('<br>', '</br>', '<br />', '<br/>'), '', $string);
    }
}

if (!function_exists('lukio_get_cf7_form')) {
    /**
     * Get and clean <p> and <br> tags from a CF7 contact form
     * 
     * @param Number $form_id form id to get
     * @return String clean cf7 form string
     * 
     * @author Itai Dotan
     */
    function lukio_get_cf7_form($form_id)
    {
        return lukio_remove_p_tags(lukio_remove_br_tags(do_shortcode('[contact-form-7 id="' . $form_id . '"]')));
    }
}

if (!function_exists('lukio_create_whatsapp_link_url')) {
    /**
     * Create a url for a whatsapp link
     * 
     * @param Number|String $number international phone number format. Omit any brackets, dashes, plus signs, and leading zeros
     * @param String $text default text for the whatsapp message. default '' for no message
     * @return String whatsapp link uri
     * 
     * @author Itai Dotan
     */
    function lukio_create_whatsapp_link_url($number, $text = '')
    {
        $lukio_whatsapp_url = 'https://api.whatsapp.com/send?phone=' . trim($number);
        if ($text != '') {
            $lukio_whatsapp_url = $lukio_whatsapp_url . '&text=' . urlencode(trim($text));
        }
        return $lukio_whatsapp_url;
    }
}

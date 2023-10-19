<?php
defined('ABSPATH') || exit;

if (!function_exists('lukio_enqueue')) {
    /**
     * enqueue the given loacl script and stylesheet files or url. normal file for admin and min file for any one eles if exists.
     *
     * @param String $path [requierd] full file path inside the theme folder.
     * @param String $name [optional] name to use in the enqueue, default `null` to use $path.
     * @param Array $deps [optional] an array of registered dependents, default `array()`
     * @param Array $user_extras [optional] 'parent' => for parent theme enqueue, default `false`.
     *                                      'in_footer' => for script enqueue in the footer, default `true`. 
     *                                      'media' => for style enqueue media type, default `all`.
     * 
     * @author Itai Dotan
     */
    function lukio_enqueue($path, $name = null, $deps = array(), $user_extras = array())
    {
        $file_enqueue = true;
        if (strpos($path, 'http') === 0) {
            $file_enqueue = false;
            $version = false;
        }

        // get the file extension offset and file type
        if (substr($path, -3) == '.js') {
            $path_sub_offset = -3;
            $file_type = '.js';
        } else {
            $path_sub_offset = -4;
            $file_type = '.css';
        }

        // setup $name when null
        if (is_null($name)) {
            if ($file_enqueue) {
                $name =  $path;
            } else {
                $name =  array_slice(explode('/', $path), -1)[0];
            }
        }

        // return if the file is already enqueue
        if ($file_type == '.css') {
            if (wp_style_is($name)) {
                return;
            }
        } else {
            if (wp_script_is($name)) {
                return;
            }
        }

        $default_extras = array(
            'parent' => false,
            'in_footer' => true,
            'media' => 'all',
        );
        $active_extras = array_merge($default_extras, $user_extras);

        if ($file_enqueue) {
            // setup for local file enqueue

            global $lukio_enqueue_disable_min;
            // set $lukio_enqueue_disable_min if not set yet
            if (!isset($lukio_enqueue_disable_min)) {
                // check the option if set to be disabled
                if (get_option('lukio_disable_enqueue_min')) {
                    $lukio_enqueue_disable_min = true;
                } else {
                    $lukio_enqueue_disable_min = in_array('administrator', wp_get_current_user()->roles);
                }
            }

            // setup the file path and uri path
            if ($active_extras['parent']) {
                $directory = get_template_directory();
                $directory_uri = get_template_directory_uri();
            } else {
                $directory = get_stylesheet_directory();
                $directory_uri = get_stylesheet_directory_uri();
            }

            // remove file extension for min testing
            $path = substr($path, 0, $path_sub_offset);

            // set the enqueue path
            $enqueue = (!$lukio_enqueue_disable_min && file_exists($directory . $path . '.min' . $file_type)) ? $path . '.min' . $file_type : $path . $file_type;
            $path = $directory_uri . $enqueue;

            $version = filemtime($directory . $enqueue);
        }

        if ($file_type == '.css') {
            wp_enqueue_style(
                $name,
                $path,
                $deps,
                $version,
                $active_extras['media']
            );
        } else {
            wp_enqueue_script(
                $name,
                $path,
                $deps,
                $version,
                $active_extras['in_footer']
            );
        }
    }
}

if (!function_exists('lukio_footer_credit')) {
    /**
     * echo the lukio credit.
     * 
     * @param Bool $dark_mode [optional] to use dark mode. default `false`
     * @param Bool $eng [optional] to use english text. default `false`
     * 
     * @author Itai Dotan
     */
    function lukio_footer_credit($dark_mode = false, $eng = false)
    {
        $link = 'https://lukio.pro';
        if ($eng) {
            $text = 'Developed & Powered by';
            $direction = 'ltr';
        } else {
            $text = 'פותח ומתוחזק ע״י';
            $direction = 'rtl';
        }
        $mode = $dark_mode ? 'dark_mode' : 'light_mode';
        $svg = '<svg id="lukio_credit_svg" aria-label="Lukio logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 107.87 40.12"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="lukio_footer_svg_u" d="M24.14,29c-7.8,0-13-5-13-12.47V1h9.68V16.57c0,2.39,1.1,3.56,3.36,3.56s3.52-1.22,3.52-3.61V1h9.68V16.57C37.34,24,32,29,24.14,29Z"/><path class="lukio_footer_svg_u_border" d="M36.34,2V16.57C36.34,23.29,31.73,28,24.15,28s-12-4.75-12-11.47V2h7.68V16.57c0,3,1.58,4.56,4.37,4.56s4.51-1.59,4.51-4.61V2h7.68m2-2H26.66V16.52c0,1.85-.73,2.61-2.51,2.61-1.43,0-2.37-.44-2.37-2.56V0H10.1V16.57C10.1,24.63,15.74,30,24.15,30s14.19-5.41,14.19-13.47V0Z"/><path class="lukio_footer_svg_text" d="M93.93,12.18a13.55,13.55,0,0,0-14,14,13.95,13.95,0,1,0,27.89,0C107.87,17.86,101.84,12.2,93.93,12.18Zm0,19.9a5.93,5.93,0,0,1,0-11.86,5.93,5.93,0,1,1,0,11.86Z"/><polygon class="lukio_footer_svg_text" points="76.7 12.81 69.02 12.81 69.02 39.4 76.7 39.4 76.7 39.4 76.7 39.4 76.7 12.81 76.7 12.81 76.7 12.81"/><polygon class="lukio_footer_svg_text" points="7.68 2.08 0 2.08 0 32.2 0 39.4 7.68 39.4 35.95 39.4 35.95 32.2 7.68 32.2 7.68 2.08"/><polygon class="lukio_footer_svg_text" points="67.11 12.81 58.03 12.81 48.38 25 48.38 2.08 40.7 2.08 40.7 39.4 48.38 39.4 48.38 26.54 58.03 39.4 67.44 39.4 56.74 25.48 67.11 12.81"/><path class="lukio_footer_svg_text" d="M72.89,10.3a4.15,4.15,0,1,0-4.23-4.17A4.14,4.14,0,0,0,72.89,10.3Z"/></g></g></svg>';

        echo '<a id="lukio_credit" class="' . $direction . ' ' . $mode . '" href="' . $link . '" target="_blank">' . $text . $svg . '</a>';
    }
}

if (!function_exists('lukio_remove_p_tags')) {
    /**
     * Remove <p> tags from string
     * 
     * @param String $string [requierd] string to remove tags from
     * @return String cleared string
     * 
     * @author Itai Dotan
     */
    function lukio_remove_p_tags($string)
    {
        return str_replace(array('<p>', '<p style="display: none !important">', '</p>'), '', $string);
    }
}

if (!function_exists('lukio_remove_br_tags')) {
    /**
     * Remove <br> tags from string
     * 
     * @param String $string [requierd] string to remove tags from
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
     * @param Number $form_id [requierd] form id to get
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
     * @param Number|String $number [optional] international phone number format. Omit any brackets, dashes, plus signs, and leading zeros
     * @param String $text [optional] default text for the whatsapp message. default `''` for no message
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

if (!function_exists('lukio_create_socialmedia_link_url')) {
    /**
     * Create a social media share url
     * 
     * @param String $url [requierd] to share
     * @param String $site [requierd] social media to create link to. 'facebook', 'linkedin', 'twitter'
     * @return String generated share link
     */
    function lukio_create_socialmedia_link_url($url, $site)
    {
        $url = urlencode($url);
        switch ($site) {
            case 'facebook':
                return 'https://www.facebook.com/sharer/sharer.php?u=' . $url;
                break;
            case 'linkedin':
                return 'https://www.linkedin.com/shareArticle?mini=true&url=' . $url;
                break;
            case 'twitter':
                return 'https://twitter.com/intent/tweet?url=' . $url . '/&text=';
                break;
        }
    }
}

if (!function_exists('lukio_create_pinterest_share_link')) {
    /**
     * Create a pinterest share link
     * 
     * @param String $link [requierd] url to link to
     * @param String $image_url [requierd] url to the image to add to the pin
     * @return String generated pinterest link
     */
    function lukio_create_pinterest_share_link($link, $image_url)
    {
        return 'https://www.pinterest.com/pin/create/link/?url=' . urlencode($link) . '&media=' . urldecode($image_url) . '&method=link';
    }
}

if (!function_exists('lukio_range_min_max')) {
    /**
     * Lukio number range input min and max.
     * 
     * @param String $target_class [requierd] class to add the all items
     * @param String $inner_color [requierd] color to use in side the range
     * @param String $outer_color [requierd] color to use put of the range
     * @param String $name [requierd] name for the inputs to be grouped under
     * @param Number $min [requierd] min value for the inputs
     * @param Number $max [requierd] max value fot the inputs
     * @param String $thumb_color [requierd] color of the range thumb
     * @param String $format [optional] format for the display string, default `%d`
     * @param Bool $use_input [optional] true to use input as the display, default `false` to use span
     * 
     * @author Itai Dotan
     */
    function lukio_range_min_max($target_class, $inner_color, $outer_color, $name, $min, $max, $thumb_color, $format = '%d', $use_input = false)
    {
        get_template_part('/template-parts/partials/lukio_range_min_max', '', array(
            'target_class' => $target_class,
            'inner_color' => $inner_color,
            'outer_color' => $outer_color,
            'name' => $name,
            'min' => $min,
            'max' => $max,
            'thumb_color' => $thumb_color,
            'format' => $format,
            'use_input' => $use_input,
        ));
    }
}

if (!function_exists('lukio_bold_text')) {
    /**
     * Remove curly braces = {{}}, from given string, and replace with <b> tag
     * 
     * @param String $text [required] text to replace braces for
     * @return String new string after braces change
     */
    function lukio_bold_text($text)
    {
        return str_replace(['{{', '}}'], ['<b>', '</b>'], $text);
    }
}

if (!function_exists('lukio_floatval')) {
    /**
     * unformat numbers and return a float
     * 
     * use the last comma or dot (if any) as the decimal separator to create a clean float
     * 
     * @param String $text text to turn in to float
     * @return Float currect number
     * 
     * @author Itai Dotan 
     */
    function lukio_floatval($text)
    {
        $parts = preg_split('/[\,\.](?!.*?[\,\.].*?)/', $text, 2);
        if (count($parts) == 1) {
            return floatval(preg_replace('/[^0-9\-]/', '', $parts[0]));
        } else {
            return floatval(preg_replace('/[^0-9\-]/', '', $parts[0]) . '.' . preg_replace('/[^0-9\-]/', '', $parts[1]));
        }
    }
}

if (!function_exists('lukio_dropdown')) {
    /**
     * print dropdown to use instead of the normal select element
     * 
     * @param array $options option array of arrays. 
     * array(
     *      array('name'=>'option_display_name1', 'value' => 'option_value1'),
     *      array('name'=>'option_display_name2', 'value' => 'option_value2')                                                 
     *     )
     * @param string $name name of the input
     * @param string $id id for of the input, default `` and will be the same as $name
     * @param string $class class to add to all the css editable elements, default ``
     * @param string $placeholder placeholder for the empty value, default ``
     * @param string $selected_value value to start as selected, default `false` for no selected option
     * 
     * @author Itai Dotan
     */
    function lukio_dropdown($options, $name, $id = '', $class = '', $placeholder = '', $selected_value = false)
    {
        $name = esc_attr($name);
        $id = $id ? esc_attr($id) : $name;
        $class = $class ? ' ' . trim($class) : '';
        $placeholder = esc_html($placeholder);
        $display = $placeholder;
?>
        <div class="lukio_dropdown<?php echo $class; ?>">
            <select class="lukio_dropdown_select<?php echo $class; ?> hide_js no_js" name="<?php echo $name; ?>" id="<?php echo $id; ?>">
                <option value=""><?php echo $placeholder; ?></option>
                <?php
                $options_html = '';
                foreach ($options as $option_data) {
                    $option_name = $option_data['name'];
                    $option_value = esc_attr(sanitize_title($option_data['value']));
                    $selected = '';
                    if ($selected_value && $selected_value == $option_data['value']) {
                        $selected = ' selected';
                        $display = $option_name;
                    }
                    echo '<option value="' . $option_value . '"' . $selected . '>' . $option_name . '</option>';
                    $options_html .= '<li class="lukio_dropdown_display_option' . $class . $selected . '" data-value="' . $option_value . '">' . $option_name . '</li>';
                }
                ?>
            </select>
            <div class="lukio_dropdown_display<?php echo $class; ?> hide_no_js no_js">
                <span class="lukio_dropdown_display_text<?php echo $class; ?>"><?php echo $display; ?></span>
                <ul class="lukio_dropdown_display_options_wrapper<?php echo $class; ?>">
                    <?php
                    echo $options_html;
                    ?>
                </ul>
            </div>
        </div>
<?php
    }
}

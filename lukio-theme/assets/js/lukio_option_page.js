jQuery(document).ready(function ($) {
    /**
     * activate the color picker on the inputs
     */
    function activate_color_picker() {
        $('.lukio_theme_option_color_picker:not(.wp-color-picker)').each(function () {
            let input = $(this);
            input.wpColorPicker({
                defaultColor: input.val(),
            });
        });
    }
    // trigger on ready
    activate_color_picker();

    // body trigger to activate colorPicker on new inputs
    $('body').on('lukio_theme_option_activate_color_picker', activate_color_picker);
});
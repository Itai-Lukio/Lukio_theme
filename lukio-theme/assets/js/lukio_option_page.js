jQuery(document).ready(function ($) {
    /**
     * activate the color picker on the inputs
     * 
     * @author Itai Dotan
     */
    function activate_color_picker() {
        $('.lukio_theme_option_color_picker:not(.wp-color-picker):not([disabled])').each(function () {
            let input = $(this);
            input.wpColorPicker();
        });
    }
    // trigger on ready
    activate_color_picker();

    // body trigger to activate colorPicker on new inputs
    $('body').on('lukio_theme_option_activate_color_picker', activate_color_picker);

    /**
     * update the input loop count
     * 
     * @author Itai Dotan
     */
    function update_color_loop_count() {
        $('#lukio_theme_option_loop_count').val($('.lukio_theme_option_color_pair:not(.template)').length);
    };

    // remove color pair row
    $(document).on('click', '.lukio_theme_option_remove_row', function () {
        $(this).closest('.lukio_theme_option_color_pair').remove();
        update_color_loop_count();
        $('.lukio_theme_option_color_pair:not(.template)').each(function (index, row) {
            $(row).find('.lukio_theme_option_css_name, .lukio_theme_option_color_picker').each(function () {
                let input = $(this);
                input.attr('name', input.data('name-format').replace('%d', index));
            });
        });
    });

    // add new color pair row
    $('.lukio_theme_option_add_row').on('click', function () {
        let new_row = $('.lukio_theme_option_color_pair.template').clone();
        new_row.removeClass('template').find('input').removeAttr('disabled').each(function () {
            let input = $(this);
            input.attr('name', input.attr('name').replace('%d', $('#lukio_theme_option_loop_count').val()));
        });
        $(this).before(new_row);
        update_color_loop_count();
        activate_color_picker();
    });

    $('.lukio_theme_option_tab').on('click', function () {
        let tab = $(this);
        if (tab.hasClass('active')) {
            return;
        }
        let new_tab_index = tab.data('tab');
        $('.lukio_theme_option_tab.active, .lukio_theme_option_tab_content.active').removeClass('active');
        $(`.lukio_theme_option_tab[data-tab="${new_tab_index}"], .lukio_theme_option_tab_content[data-tab="${new_tab_index}"]`).addClass('active');
    });
});
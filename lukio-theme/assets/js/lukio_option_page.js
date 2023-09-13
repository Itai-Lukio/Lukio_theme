jQuery(document).ready(function ($) {
    /**
     * add or update the url query with the new param and value
     * 
     * @param {string} param url param to update
     * @param {string} newval param new value
     * @param {string} search url query
     * @returns {string} updated url query
     * 
     * @author Itai Dotan
     */
    function replace_query_param(param, newval, search) {
        let regex = new RegExp("([?;&])(" + param + "[^&;]*[;&]?)"),
            query = search.replace(regex, "$1").replace(/[?&]$/, '');

        return query + (newval ? (query.length > 0 ? "&" : "?") + param + "=" + newval : '');
    }

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
    $(document)
        .on('click', '.lukio_theme_option_remove_row', function () {
            $(this).closest('.lukio_theme_option_color_pair').remove();
            update_color_loop_count();
            $('.lukio_theme_option_color_pair:not(.template)').each(function (index, row) {
                $(row).find('.lukio_theme_option_css_name, .lukio_theme_option_color_picker').each(function () {
                    let input = $(this);
                    input.attr('name', input.data('name-format').replace('%d', index));
                });
            });
        })
        // add new color pair row
        .on('click', '.lukio_theme_option_add_row', function () {
            let new_row = $('.lukio_theme_option_color_pair.template').clone();
            new_row.removeClass('template').find('input').removeAttr('disabled').each(function () {
                let input = $(this);
                input.attr('name', input.attr('name').replace('%d', $('#lukio_theme_option_loop_count').val()));
            });
            $(this).before(new_row);
            update_color_loop_count();
            activate_color_picker();
        })
        // switch option tab
        .on('click', '.lukio_theme_option_tab', function () {
            let tab = $(this);
            if (tab.hasClass('active')) {
                return;
            }
            let new_tab_index = tab.data('tab');
            $('.lukio_theme_option_tab.active, .lukio_theme_option_tab_content.active').removeClass('active');
            $(`.lukio_theme_option_tab[data-tab="${new_tab_index}"], .lukio_theme_option_tab_content[data-tab="${new_tab_index}"]`).addClass('active');

            window.history.replaceState({}, "", window.location.pathname + replace_query_param('tab', new_tab_index, window.location.search));
        });
});
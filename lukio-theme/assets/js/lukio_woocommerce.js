(function ($) {
    $(document).ready(function () {

        // refresh the mini cart in to lukio_mini_cart_wrapper from the shortcode 
        $('body').on('wc_fragments_loaded wc_fragments_refreshed', function () {
            $.ajax({
                method: 'POST',
                url: lukio_wc_ajax.ajax_url,
                data: { action: 'lukio_woocommerce_refresh_mini_cart' },
                success: function (data) {
                    if (data) {
                        data = JSON.parse(data);
                        $('.lukio_mini_cart_wrapper').html(data.fragment);
                        // trigger event with the updated items count to allow updating count out side of the mini cart 
                        $('body').trigger('lukio_cart_num_refresh', [data.items_count]);
                        // trigger event with the extra data added to the ajax 
                        $('body').trigger('lukio_refresh_mini_cart_extra', [data.extra]);
                    }
                }
            });
        });

        // functionality to lukio quantity, update the product quantity and refresh wc and minicart
        $(document).on('click', '.lukio_cart_product_quantity_btn', function () {
            let clicked = $(this);
            let group = clicked.parent();
            if (group.hasClass('working') || (clicked.hasClass('plus') && group.hasClass('plus_disabled'))) {
                return;
            }
            let product_quantity = parseInt(group.find('.lukio_cart_product_quantity_display').text());
            if (clicked.hasClass('minus')) {
                product_quantity--;
            } else if (clicked.hasClass('plus')) {
                product_quantity++;
            }
            group.addClass('working');
            $.ajax({
                method: 'POST',
                url: lukio_wc_ajax.ajax_url,
                data: { action: 'lukio_update_cart_quantity', cart_item: group.data('key'), quantity: product_quantity },
                success: function () {
                    $('body').trigger('wc_fragment_refresh');
                }
            });

        })
    })
})(jQuery)
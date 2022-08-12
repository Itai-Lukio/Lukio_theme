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

    })
})(jQuery)
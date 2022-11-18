(function ($) {
    $(document).ready(function () {

        // refresh the mini cart in to lukio_mini_cart_wrapper from the shortcode 
        $('body').on('wc_fragments_loaded wc_fragments_refreshed updated_checkout updated_cart_totals', function () {
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
                    $('body').trigger('lukio_update_wc_parts');
                }
            });
        });

        // event to trigger when needing to refresh woocommerce parts
        $('body').on('lukio_update_wc_parts', function () {
            switch (window.location.origin + window.location.pathname) {
                case lukio_wc_ajax.checkout_url:
                    $('body').trigger('update_checkout');
                    break;
                case lukio_wc_ajax.cart_url:
                    $('body').trigger('wc_update_cart');
                    break;
                default:
                    $('body').trigger('wc_fragment_refresh');
                    break;
            };
        });

        // refresh wc parts when removing item from the mini cart when in cart or checkout
        let page = window.location.origin + window.location.pathname;
        if (page == lukio_wc_ajax.cart_url || page == lukio_wc_ajax.checkout_url) {
            $(document).on('click', '.lukio_mini_cart_wrapper .remove', function () {
                $('body').one('wc_fragments_loaded', function () {
                    $('body').trigger('lukio_update_wc_parts');
                });
            });
        };

        // update lukio add to cart button quantity inside of wc add to cart form
        $(document).on('change', '.lukio_add_to_cart_form_wrapper input[name="quantity"]', function () {
            let input = $(this);
            input.closest('.lukio_add_to_cart_form_wrapper').find('.lukio_add_btn').attr('data-quantity', input.val());
        });

        // update lukio add to cart button product_id inside of wc add to cart form
        $(document).on('change', '.lukio_add_to_cart_form_wrapper input[name="variation_id"]', function () {
            let input = $(this);
            input.closest('.lukio_add_to_cart_form_wrapper').find('.lukio_add_btn').attr('data-product_id', input.val());
        });

        // hook in to wc add_to_cart trigger and prevent the ajax when the button has a 'disabled' class
        $('body').on('should_send_ajax_request.adding_to_cart', function (e, btn) {
            if (btn.hasClass('disabled')) {
                return false;
            }
        });

    })
})(jQuery)
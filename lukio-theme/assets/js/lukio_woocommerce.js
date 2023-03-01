jQuery(document).ready(function ($) {

    let minicart = $('.lukio_mini_cart_wrapper'),
        body = $('body'),
        page = window.location.origin + window.location.pathname,
        cart_hash_key = wc_cart_fragments_params.cart_hash_key,
        refresh_trigger = 'wc_fragments_refreshed',
        is_checkout = page == lukio_woo.checkout_url,
        cart_or_checkout = page == lukio_woo.cart_url || is_checkout,
        supports_html5_storage = true,
        checkout_storage_refresh = false;

    // update the refresh events to the type of page
    switch (page) {
        case lukio_woo.checkout_url:
            refresh_trigger += ' updated_checkout';
            break;
        case lukio_woo.cart_url:
            refresh_trigger = 'updated_cart_totals wc_cart_emptied';
            break;
        default:
            refresh_trigger += ' wc_fragments_loaded';
            break;
    };

    // make sure storage is available
    try {
        window.sessionStorage.setItem('lukio_storage_check', 'test');
        window.sessionStorage.removeItem('lukio_storage_check');
        window.localStorage.setItem('lukio_storage_check', 'test');
        window.localStorage.removeItem('lukio_storage_check');
    } catch (error) {
        supports_html5_storage = false;
    }

    /**
     * turn element raw className to a jQuery selector
     * 
     * @param {string} class_name 
     * @returns {string} valid jquery class selector
     */
    function class_name_to_selector(class_name) {
        return class_name.replaceAll(' ', '.');
    };

    /**
     * shrink fades and remove minicart li element
     * 
     * @param {jQuery} el element to remove
     */
    function remove_minicart_li(el) {
        lukio_helpers.animate_size(el, 'height', 0, 400, 0, { opacity: '0' }, () => { el.remove() });
    };

    /**
     * check if the given element is not the last one in the minicart
     * 
     * @param {jQuery} item_li minicart item to check its siblings
     * @returns {bool} true when not the last item
     */
    function not_last_item_in_minicart(item_li) {
        return item_li.siblings().length != 0;
    }

    /**
     * update the storage to trigger other tabs refresh when in checkout or cart
     * 
     * @param {string} cart_hash new cart hash
     */
    function update_storage(cart_hash) {
        if (!supports_html5_storage || !cart_or_checkout) {
            return;
        }
        localStorage.setItem(cart_hash_key, cart_hash);
        sessionStorage.setItem(cart_hash_key, cart_hash);
    }

    if (supports_html5_storage && cart_or_checkout) {
        // refresh the cart or checkout when storage changes in another tab
        $(window).on('storage onstorage', function (e) {
            if (cart_hash_key === e.originalEvent.key &&
                localStorage.getItem(cart_hash_key) !== sessionStorage.getItem(cart_hash_key)) {
                if (is_checkout) {
                    checkout_storage_refresh = true;
                }
                body.trigger('lukio_update_wc_parts');
            };
        });
    };

    body
        // refresh the mini cart in to lukio_mini_cart_wrapper from the shortcode
        .on(refresh_trigger, function () {
            if (checkout_storage_refresh) {
                // prevent double refresh from storage on checkout
                checkout_storage_refresh = false;
                return;
            }

            $.ajax({
                method: 'POST',
                url: lukio_localize.ajax_url,
                data: { action: 'lukio_woocommerce_refresh_mini_cart' },
                success: function (data) {
                    if (data) {
                        data = JSON.parse(data);

                        if (false === body.triggerHandler('lukio_should_fully_refresh_minicart')) {
                            let fragment_el = document.createElement('div');
                            fragment_el.innerHTML = data.fragment;

                            let jq_fragment_el = $(fragment_el);
                            fragment_el.remove();

                            // find any element with the 'always_refresh' class and refresh it
                            jq_fragment_el.find('.always_refresh').each(function () {
                                minicart.find('.' + class_name_to_selector(this.className)).html(this.innerHTML);
                            });

                            body.trigger('lukio_minicart_not_fully_refreshed', [jq_fragment_el])
                        } else {
                            minicart.html(data.fragment);
                        }

                        update_storage(data.cart_hash);

                        body
                            // trigger event with the updated items count to allow updating count out side of the mini cart 
                            .trigger('lukio_cart_num_refresh', [data.items_count])
                            // trigger event with the extra data added to the ajax 
                            .trigger('lukio_refresh_mini_cart_extra', [data.extra])
                            // trigger event hooked to the end of the refresh
                            .trigger('lukio_minicart_refreshed');
                    }
                }
            });
        })
        // hook in to wc add_to_cart trigger
        .on('should_send_ajax_request.adding_to_cart', function (e, btn) {
            if (btn.hasClass('disabled')) {
                // prevent the ajax when the button has a 'disabled' class
                return false;
            }
            // update the button attributes when in the lukio wrapper
            let wrapper = btn.closest('.lukio_theme_add_to_cart_form_wrapper');
            if (wrapper.length == 1) {
                let quantity = wrapper.find('input[name="quantity"]');
                if (quantity.length == 1) {
                    btn.attr('data-quantity', quantity.val());
                }

                let product_id = wrapper.find('input[name="variation_id"]');
                if (product_id.length == 1) {
                    btn.attr('data-product_id', product_id.val());
                }
            }
        })
        // event to trigger when needing to refresh woocommerce parts
        .on('lukio_update_wc_parts', function () {
            switch (window.location.origin + window.location.pathname) {
                case lukio_woo.checkout_url:
                    body.trigger('update_checkout');
                    break;
                case lukio_woo.cart_url:
                    body.trigger('wc_update_cart');
                    break;
                default:
                    body.trigger('wc_fragment_refresh');
                    break;
            };
        });

    $(document)
        // functionality to lukio quantity, update the product quantity and refresh wc and minicart
        .on('click', '.lukio_cart_product_quantity_btn', function () {
            let clicked = $(this),
                group = clicked.parent(),
                item_li = clicked.closest('li');

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
                url: lukio_localize.ajax_url,
                data: { action: 'lukio_update_cart_quantity', cart_item: group.data('key'), quantity: product_quantity },
                success: function () {
                    if (item_li.closest('.lukio_mini_cart_wrapper').length == 1) {
                        // when triggered in the minicart
                        let not_last = not_last_item_in_minicart(item_li)
                        if (not_last || !not_last && product_quantity != 0) {
                            // when not the last item or wehn is the last item but not removing
                            body
                                .one('lukio_should_fully_refresh_minicart', function () {
                                    return false;
                                })
                                .one('lukio_minicart_not_fully_refreshed', function (e, fragment) {
                                    if (product_quantity != 0) {
                                        item_li.html(fragment.find('.' + class_name_to_selector(item_li.attr('class'))).eq(item_li.index()).html());
                                    } else {
                                        remove_minicart_li(item_li);
                                    }
                                })
                        }
                    }
                    body.trigger('lukio_update_wc_parts');

                }
            });
        })
        // clicking the remove link in the minicart
        .on('click', '.lukio_mini_cart_wrapper .remove', function () {
            let item_li = $(this).closest('li');
            if (not_last_item_in_minicart(item_li)) {
                // when not removing the last item
                body
                    .one('wc_fragments_loaded', function () {
                        body.one('lukio_should_fully_refresh_minicart', function () {
                            return false;
                        });

                        if (cart_or_checkout) {
                            // refresh wc parts when removing item from the mini cart when in cart or checkout
                            body.trigger('lukio_update_wc_parts');
                        };
                    })
                    .one('lukio_minicart_refreshed', function () {
                        remove_minicart_li(item_li);
                    });
            } else {
                body
                    .one('wc_fragments_loaded', function () {
                        if (cart_or_checkout) {
                            // refresh wc parts when removing item from the mini cart when in cart or checkout
                            body.trigger('lukio_update_wc_parts');
                        };
                    });
            }
        });
});
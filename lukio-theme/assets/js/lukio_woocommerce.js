jQuery(document).ready(function ($) {

    let minicart = $('.lukio_mini_cart_wrapper'),
        body = $('body'),
        page = window.location.origin + window.location.pathname,
        cart_hash_key = wc_cart_fragments_params.cart_hash_key,
        refresh_trigger = 'wc_fragments_refreshed',
        is_checkout = page == lukio_woo.checkout_url,
        cart_or_checkout = page == lukio_woo.cart_url || is_checkout,
        supports_html5_storage = true,
        checkout_storage_refresh = false,
        thumb_active_class = 'lukio_active_thumb';

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
     * 
     * @author Itai Dotan
     */
    function class_name_to_selector(class_name) {
        return class_name.replaceAll(' ', '.');
    };

    /**
     * shrink fades and remove minicart li element
     * 
     * @param {jQuery} el element to remove
     * 
     * @author Itai Dotan
     */
    function remove_minicart_li(el) {
        lukio_helpers.animate_size(el, 'height', 0, 400, 0, { opacity: '0' }, () => { el.remove() });
    };

    /**
     * check if the given element is not the last one in the minicart
     * 
     * @param {jQuery} item_li minicart item to check its siblings
     * @returns {bool} true when not the last item
     * 
     * @author Itai Dotan
     */
    function not_last_item_in_minicart(item_li) {
        return item_li.siblings().length != 0;
    }

    /**
     * update the storage to trigger other tabs refresh when in checkout or cart
     * 
     * @param {string} cart_hash new cart hash
     * 
     * @author Itai Dotan
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

    /**
     * move the arrows to their proper position
     * 
     * due to the way wc gallery works can not change the html architecture
     * like wrapping thumbs in a wrapper to place the arrows with them directly
     * 
     * @param {jQuery} arrows elements to be moved
     * @param {jQuery} continer element to append in to
     * 
     * @author Itai Dotan
     */
    function reposition_gallery_arrows(arrows, continer) {
        arrows.each(function () {
            let outer = this.outerHTML,
                arrow = $(this),
                position = arrow.data('place');
            $(this).remove();
            if (position == 'viewport') {
                continer.append(outer);
            } else {
                continer.closest('.woocommerce-product-gallery').append(outer);
            }
        });
    }

    /**
     * move the pagination to its proper position
     * 
     * @param {jQuery} pagination element to be moved
     * @param {jQuery} continer element to append in to
     * 
     * @author Itai Dotan
     */
    function reposition_gallery_pagination(pagination, continer) {
        let outer = pagination[0].outerHTML;
        pagination.remove();
        continer.append(outer);
    }

    /**
     * toggle the arrows disable status of the gallery
     * 
     * @param {jQuery} btn button/pagination targeted by the event 
     * @param {jQuery} thumbs_ol thumbnails ol element
     * @param {int} active_index active slide index
     * 
     * @author Itai Dotan
     */
    function toggle_gallery_arrows(btn, thumbs_ol, active_index) {
        // no need to disable when looping
        if (btn.data('loop')) {
            return;
        }
        let continer = btn.closest('.woocommerce-product-gallery'),
            arrows = continer.find('.lukio_product_gallery_arrow'),
            li_list = thumbs_ol.children('li'),
            prev_action = active_index == 0 ? 'addClass' : 'removeClass',
            next_action = active_index == li_list.length - 1 ? 'addClass' : 'removeClass';

        arrows.filter('.prev')[prev_action]('disable');
        arrows.filter('.next')[next_action]('disable');
    }

    /**
     * update all the parts of product gallery. slide, thumbs, arrows and pagination
     * 
     * @param {jQuery} gallery '.woocommerce-product-gallery' div
     * @param {int} index index of the active slide
     * 
     * @author Itai Dotan
     */
    function update_gallery_display_by_index(gallery, index) {
        let prev_arrow = gallery.find('.lukio_product_gallery_arrow.prev'),
            thumbs_ol = gallery.find('ol.flex-control-nav'),
            nth_index = index + 1;

        // update active thumb class
        thumbs_ol.find(`.${thumb_active_class}`).removeClass(thumb_active_class);
        thumbs_ol.find(`li:nth-of-type(${nth_index})`).addClass(thumb_active_class);

        // update the arrows
        toggle_gallery_arrows(prev_arrow, thumbs_ol, index);
        // scroll the thumb in to view
        let active_thumb = thumbs_ol.find(`.${thumb_active_class}`);
        lukio_helpers.scroll_horizontally(active_thumb, thumbs_ol);

        // change the pagination dot
        gallery.find('.lukio_product_gallery_pagination_dot.active').removeClass('active');
        gallery.find(`.lukio_product_gallery_pagination_dot:nth-of-type(${nth_index})`).addClass('active');

        // allow to add extra actions at the end of the update 
        body.trigger('lukio_gallery_display_updated', [gallery, thumbs_ol, active_thumb, index]);
    }

    /**
     * setup lukio product gallery
     * 
     * @author Itai Dotan
     */
    function setup_product_gallery() {
        let arrows = $('.lukio_product_gallery_arrow'),
            pagination = $('.lukio_product_gallery_pagination'),
            continer = arrows.closest('.flex-viewport'),
            gallery = arrows.closest('.woocommerce-product-gallery'),
            thumbs_ol = gallery.find('ol.flex-control-nav.flex-control-thumbs');

        if (arrows.length == 0) {
            return;
        }

        // setup thumbs css and grab and drag
        thumbs_ol.addClass('lukio_product_gallery_thumbs');
        thumbs_ol.lukioDragScroll();

        reposition_gallery_arrows(arrows, continer);

        if (pagination.length != 0) {
            reposition_gallery_pagination(pagination, continer);
        }

        update_gallery_display_by_index(gallery, 0);
    }
    setup_product_gallery();

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
        })
        // update gallery pagination and arrows when clicking a thumbnail
        .on('click', '.woocommerce-product-gallery ol.flex-control-nav.flex-control-thumbs li', function (e) {
            let clicked_li = $(this);

            if (e.target.tagName == 'LI') {
                // when the target is the li the after play icon was clicked, so needed to trigger a click on the img
                clicked_li.find('img').trigger('click');
            } else {
                let ol = clicked_li.closest('.flex-control-nav.flex-control-thumbs'),
                    gallery = ol.closest('.woocommerce-product-gallery'),
                    new_index = clicked_li.index();
                update_gallery_display_by_index(gallery, new_index);
            }
        })
        // change the product gallery image when clicking the arrows 
        .on('click', '.lukio_product_gallery_arrow', function (e) {
            e.stopPropagation();
            let btn = $(this);

            if (btn.hasClass('disbled')) {
                return;
            }

            let action = btn.data('action'),
                gallery = btn.closest('.woocommerce-product-gallery'),
                loop = btn.data('loop'),
                offset = action == 'next' ? 1 : -1,
                ol = gallery.find('ol.flex-control-nav'),
                li_thumbs = ol.find('li'),
                li_index_length = li_thumbs.length - 1,
                active_li = ol.find('.flex-active').closest('li'),
                new_index = active_li.index() + offset;

            // fix the index when looping
            if (new_index < 0) {
                new_index = loop ? li_index_length : 0;
            } else if (new_index > li_index_length) {
                new_index = loop ? 0 : li_index_length;
            }

            let new_li = li_thumbs.eq(new_index);
            new_li.find('img').trigger('click');
        })
        // change the product gallery image when clicking a pagination dot
        .on('click', '.lukio_product_gallery_pagination_dot', function () {
            let btn = $(this),
                new_index = btn.data('index'),
                gallery = btn.closest('.woocommerce-product-gallery');

            if (btn.hasClass('active')) {
                return;
            }

            // click the new image
            gallery.find('ol.flex-control-nav li').eq(new_index).find('img').trigger('click');
        })
        // update gallery display when the slide was changed
        .on('zoom.destroy', '.woocommerce-product-gallery__image.flex-active-slide', function () {
            let gallery = $(this).closest('.woocommerce-product-gallery'),
                new_index = gallery.find('.flex-control-nav.flex-control-thumbs .flex-active').closest('li').index();
            update_gallery_display_by_index(gallery, new_index);
        })
        // update wc variation
        .on('click', '.lukio_woocommerce_product_variations_li', function (e) {
            e.stopPropagation();
            let li = $(this);
            if (li.hasClass('selected')) {
                return;
            }

            let value = li.data('value'),
                wrapper = li.closest('.lukio_woocommerce_product_variations_wrapper'),
                display = wrapper.find('.lukio_woocommerce_product_variations_dropdown_display');

            wrapper.find('.lukio_woocommerce_product_variations_li.selected').removeClass('selected');
            li.addClass('selected');
            wrapper.find(`select`).val(value).trigger('change');

            // when we got a display its a dropdown select
            if (display.length != 0) {
                display.text(li.text());
                $('body').trigger('click.lukio_woocommerce_product_variation_clicked');
            }
        })
        // add accessabilty action to the variation li
        .on('keydown', '.lukio_woocommerce_product_variations_li', function (e) {
            if (!e.key || !(e.key.toLocaleLowerCase() == 'enter' || e.key == ' ')) {
                return;
            }
            e.preventDefault();
            $(this).trigger('click');
        })
        // reset product variation ul selected
        .on('click', '.variations_form a.reset_variations', function () {
            let form = $(this).closest('form');
            form.find('.lukio_woocommerce_product_variations_li.selected').removeClass('selected');
            form.find('.lukio_woocommerce_product_variations_dropdown_display').each(function () {
                let display = $(this);
                display.text(display.data('placeholder'));
            });
        })
        // open variations dropdown and setup body click close 
        .on('click', '.lukio_woocommerce_product_variations_dropdown', function () {
            let selector = $(this),
                ul = selector.find('.lukio_woocommerce_product_variations_ul');
            selector.addClass('open');
            $('body').one('click.lukio_woocommerce_product_variation_clicked', function () {
                ul.addClass('closing');
                selector.removeClass('open');
                setTimeout(() => {
                    ul.removeClass('closing');
                }, 400);
            });
        })
        // open the dropdown on focus
        .on('focus', '.lukio_woocommerce_product_variations_li.dropdown', function () {
            $(this).closest('.lukio_woocommerce_product_variations_dropdown').trigger('click');
        })
        // close the dropdown on blur
        .on('blur', '.lukio_woocommerce_product_variations_li.dropdown', function () {
            $('body').trigger('click.lukio_woocommerce_product_variation_clicked');
        });

    /**
    * handle needed tweaks to have videos in photoswipe
    * 
    * @author Itai Dotan
    */
    function photoswipe_gallery_videos() {
        /**
         * set video on init, mute videos slide on change, make sure the video is used insted of the replaced img on slide change
         * 
         * due to photoswipe useing only 3 slides in the DOM, there is a need to track and reset the video slide
         * 
         * @author Itai Dotan
         */
        function photoswipe_slides_change() {
            $('.lukio_wc_gallery_video').each(function () {
                this.muted = true;
            });
            let pswp = $('div.pswp'),
                active_images = pswp.find('.pswp__item img'),
                videos = [],
                need_resize = false;

            $('.lukio_wc_gallery_video_wrapper .lukio_wc_gallery_video').each(function () {
                videos.push({
                    img_src: $(this).data('img_src'),
                    video_html: this.outerHTML
                });
            });
            active_images.each(function () {
                let img = $(this),
                    img_src = img.attr('src');
                videos.forEach(vid_el => {
                    if (vid_el.img_src !== img_src) {
                        return;
                    }
                    img.replaceWith(vid_el.video_html);
                    need_resize = true;
                });
            });

            if (need_resize) {
                // trigger window resize for pswp to resize the slides
                window.dispatchEvent(new Event('resize'));
            }
        }

        // add video indicator class to the thumbnail li
        $('.lukio_wc_gallery_video_wrapper').each(function () {
            $(`.lukio_product_gallery_thumbs li:nth-of-type(${$(this).index() + 1})`).addClass('lukio_wc_gallery_video_thumb');
        });

        let pswp_observer = new MutationObserver(function (mutations) {
            let pswp = $(mutations[0].target);
            if (pswp.hasClass('pswp--open')) {
                photoswipe_slides_change();
            } else {
                $('div.pswp .lukio_wc_gallery_video').each(function () {
                    this.pause();
                });
            }
        });

        pswp_observer.observe($('div.pswp')[0], {
            attributes: true,
            attributeFilter: ['class']
        });

        // if there are no more then 3 images no need to fix photoswipe 3 DOM elements limit
        if ($('.woocommerce-product-gallery__image').length <= 3) {
            return;
        }

        // as there are stopPropagation in the way, need to target directly
        $('.pswp__button--arrow--right, .pswp__button--arrow--left').on('click', photoswipe_slides_change);
        $(window).on('keydown', function (e) {
            // update only when photoswipe is open and one of the arrow key are used
            if ($('div.pswp.pswp--open').length && (e.keyCode === 37 || e.keyCode === 39)) {
                photoswipe_slides_change();
            }
        });
    };
    photoswipe_gallery_videos();
});
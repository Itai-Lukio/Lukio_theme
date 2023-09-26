jQuery(function ($) {
    /**
    * get the new preview image src and update the previews
    * 
    * @param {string} image_id id of the image to get
    * @param {jQuery} preview_image jQuery object of the preview image
    * 
    * @author Itai Dotan
    */
    function refresh_preview_images(image_id, preview_image) {
        $.ajax({
            method: 'GET',
            url: woocommerce_admin_meta_boxes_variations.ajax_url,
            data: {
                action: 'lukio_product_bulk_variations_image_preview',
                id: image_id
            },
            success: function (response) {
                if (response) {
                    response = JSON.parse(response);
                    if (response.success === true) {
                        preview_image.attr('src', response.image_src);
                    }
                }
            }
        });
    }

    /**
     * copy wc block settings for the 'block' library
     * 
     * @param {jQuery} el element to bloack
     * 
     * @authoe Itai Dotan
     */
    function wc_block(el) {
        el.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6,
            },
        });
    }

    $(document)
        // open the image picker
        .on('click', '.lukio_product_bulk_variations_image_picker', function (e) {
            e.preventDefault();
            let btn = $(this),
                input = btn.siblings('#lukio_product_bulk_variations_image_input'),
                image_preview = btn.siblings('.lukio_product_bulk_variations_image_img'),
                // Define image_frame as wp.media object
                image_frame = wp.media({
                    title: btn.data('popup-title'),
                    multiple: false,
                    library: {
                        type: 'image',
                    }
                });

            image_frame
                // get the id from the input and select the appropiate image in the media manager
                .on('open', function () {
                    let selection = image_frame.state().get('selection'),
                        id = input.val(),
                        attachment = wp.media.attachment(id);
                    attachment.fetch();
                    selection.add(attachment ? [attachment] : []);
                })
                // get the id from the media manager update the input and the preview image
                .on('close', function () {
                    let selection = image_frame.state().get('selection');
                    if (selection.models.length == 0) {
                        // when no image is selected
                        return;
                    }

                    // get the selected image id from the media manager
                    let id = selection.models[0]['id'];

                    if (input.val() == id) {
                        // return when the selected image wasn't changed
                        return;
                    }

                    input.val(id);
                    refresh_preview_images(id, image_preview);
                });

            image_frame.open();
        })
        // send bulk image set request
        .on('click', '.lukio_product_bulk_variations_image_btn', function () {
            let btn = $(this),
                btn_action = btn.data('action'),
                wrapper = btn.closest('#lukio_product_bulk_variations_image'),
                nonce = wrapper.find('#lukio_product_bulk_variations_image_nonce').val(),
                filters_str = wrapper.find('[name^=lukio_product_bulk_variations_image_select]').serialize(),
                image_id = wrapper.find('#lukio_product_bulk_variations_image_input').val();

            wc_block(wrapper);

            $.ajax({
                method: 'POST',
                url: woocommerce_admin_meta_boxes_variations.ajax_url,
                data: {
                    action: 'lukio_product_bulk_variations_image_set',
                    image_id: image_id,
                    type: btn_action,
                    post_id: woocommerce_admin_meta_boxes_variations.post_id,
                    filters_str: filters_str,
                    nonce: nonce
                },
                success: function (response) {
                    wrapper.unblock();

                    // update variations tab content
                    $('.variations-pagenav .page-selector').trigger('change');
                }
            });
        })
        // reload the bulk options on variations change
        .on('reload', '#variable_product_options', function () {
            let wrapper = $('#lukio_product_bulk_variations_image'),
                nonce = wrapper.find('#lukio_product_bulk_variations_image_nonce').val();

            wc_block(wrapper);

            $.ajax({
                method: 'GET',
                url: woocommerce_admin_meta_boxes_variations.ajax_url,
                data: {
                    action: 'lukio_product_bulk_variations_reload',
                    post_id: woocommerce_admin_meta_boxes_variations.post_id,
                    nonce: nonce
                },
                success: function (response) {
                    if (response) {
                        response = JSON.parse(response);
                        wrapper.html(response.fragment.trim().replace(/^<div[^>]*>||<\/div>$/g, ''));
                        wrapper.unblock();
                    }
                }
            });
        });
});
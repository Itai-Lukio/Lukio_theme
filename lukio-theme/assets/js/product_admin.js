jQuery(function ($) {
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

    /**
     * open wp.media with the given options and update the input and preview on close
     * 
     * @param {Object} options options to use for wp.media init
     * @param {jQuery} input input to update with the selected media id
     * @param {jQuery} preview preview to update its src with the selected media url
     * 
     * @author Itai Dotan
     */
    function open_media_selector(options, input, preview) {
        // Define frame as wp.media object
        let frame = wp.media(options);

        frame
            // get the id from the input and select the appropiate media in the media manager
            .on('open', function () {
                let selection = frame.state().get('selection'),
                    id = input.val(),
                    attachment = wp.media.attachment(id);
                attachment.fetch();
                selection.add(attachment ? [attachment] : []);
            })
            // get the id from the media manager update the input and the preview
            .on('close', function () {
                let selection = frame.state().get('selection');
                if (selection.models.length == 0) {
                    // when no image is selected
                    return;
                }

                // get the selected id from the media manager
                let id = selection.models[0]['id'];

                if (input.val() == id) {
                    // return when the selected image wasn't changed
                    return;
                }

                input.val(id);
                if (selection.models[0]['changed']['url']) {
                    preview.attr('src', selection.models[0]['changed']['url']);
                }
            });

        frame.open();
    }

    /**
     * update the video rows
     * 
     * @author Itai Dotan
     */
    function update_gallery_video_rows() {
        let images_data = {},
            row_template = $('.lukio_gallery_video_row_wrapper.template'),
            new_rows = '',
            thumbnail_id = parseInt($('#_thumbnail_id').val()),
            gallery_ids = $('#product_image_gallery').val().split(','),
            wc_gallery_wrapper = $('#product_images_container .product_images'),
            video_gallery = $('#lukio_product_gallery_video');

        // add main image id and src
        if (thumbnail_id > 0) {
            images_data[thumbnail_id] = $('#set-post-thumbnail img').attr('src');
        }

        // get and add ids and src from wc gallery
        gallery_ids.forEach(id => {
            if (parseInt(id)) {
                images_data[id] = wc_gallery_wrapper.find(`li[data-attachment_id="${id}"] img`).attr('src');
            }
        });

        for (let img_id in images_data) {
            let found_row = $(`.lukio_gallery_video_row_wrapper[data-id="${img_id}"]`);

            if (found_row.length > 0) {
                // when the image row exist mark as valid
                found_row.addClass('valid');
            } else {
                // create a new row for the image with the needed data
                let row_str = row_template[0].outerHTML;
                new_rows += row_str.replace('template', 'valid').replaceAll('0', img_id).replace(/(<img.*?src=")/, '$1' + images_data[img_id]);
            }
        }
        video_gallery[Object.keys(images_data).length ? 'removeClass' : 'addClass']('empty').append(new_rows);
        $('.lukio_gallery_video_row_wrapper:not(.template):not(.valid)').remove();
        $('.lukio_gallery_video_row_wrapper.valid').removeClass('valid');
    }

    $(document)
        // open the image picker
        .on('click', '.lukio_product_bulk_variations_image_picker', function (e) {
            e.preventDefault();
            let btn = $(this),
                input = btn.siblings('#lukio_product_bulk_variations_image_input'),
                image_preview = btn.siblings('.lukio_product_bulk_variations_image_img'),
                options = {
                    multiple: false,
                    library: {
                        type: 'image',
                    }
                };
            open_media_selector(options, input, image_preview);
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
        })
        // open the video picker
        .on('click', '.lukio_gallery_video_row_button', function (e) {
            e.preventDefault();
            let btn = $(this),
                wrapper = btn.closest('.lukio_gallery_video_row_wrapper'),
                input = wrapper.find('.lukio_gallery_video_row_input'),
                preview = wrapper.find('.lukio_gallery_video_row_video'),
                options = {
                    multiple: false,
                    library: {
                        type: 'video',
                    }
                };
            open_media_selector(options, input, preview);
        })
        // remove video from the gallery
        .on('click', '.lukio_gallery_video_row_video_remove', function (e) {
            e.preventDefault();
            let btn = $(this),
                wrapper = btn.closest('.lukio_gallery_video_row_wrapper'),
                input = wrapper.find('.lukio_gallery_video_row_input'),
                preview = wrapper.find('.lukio_gallery_video_row_video');
            input.val('');
            preview.attr('src', '');
        })
        // due to the input been added with innerHTML track the ajaxComplete that load it
        .on('ajaxComplete', function (event, request, options) {
            if (options.data && !options.data.includes('get-post-thumbnail-html')) {
                return;
            }
            update_gallery_video_rows();
        });

    // use an observer on the gallery input due to val() not triggering 'change' event
    let wc_gallery_input_observer = new MutationObserver(function (mutations) {
        update_gallery_video_rows();
    });
    wc_gallery_input_observer.observe($('#product_image_gallery')[0], {
        attributes: true,
        attributeFilter: ['value']
    });
});
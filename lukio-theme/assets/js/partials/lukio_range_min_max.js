jQuery(function ($) {
    let display_timeout;
    $(document)
        .on('lukio_range_min_max_track_update', '.lukio_range_min_max', function () {
            let wrapper = $(this),
                outer_color = wrapper.attr('outer_color'),
                inner_color = wrapper.attr('inner_color'),
                track = wrapper.find('.lukio_range_min_max_track'),
                inputs = {
                    min: wrapper.find('.lukio_range_min_max_input.min'),
                    max: wrapper.find('.lukio_range_min_max_input.max')
                },
                min_val = parseInt(inputs.min.attr('min')),
                max_val = parseInt(inputs.min.attr('max')),
                track_divider = max_val - min_val;

            let percent1 = ((parseInt(inputs.min.val()) - min_val) * 100) / track_divider,
                percent2 = ((parseInt(inputs.max.val()) - min_val) * 100) / track_divider,
                direction = track.css('direction') == 'rtl' ? 'left' : 'right';
            track.css('background', `linear-gradient(to ${direction}, ${outer_color} ${percent1}% , ${inner_color} ${percent1}% , ${inner_color} ${percent2}%, ${outer_color} ${percent2}%)`);
        })
        .on('input', '.lukio_range_min_max_input', function () {
            let input = $(this),
                indicator_class = input.hasClass('min') ? 'min' : 'max',
                wrapper = input.closest('.lukio_range_min_max'),
                inputs = {
                    min: indicator_class == 'min' ? input : wrapper.find('.lukio_range_min_max_input.min'),
                    max: indicator_class == 'max' ? input : wrapper.find('.lukio_range_min_max_input.max')
                },
                min = parseInt(input.attr('min')),
                max = parseInt(input.attr('max')),
                format = wrapper.attr('format'),
                gap = (max - min) * 0.1,
                current_min = parseInt(inputs.min.val()),
                current_max = parseInt(inputs.max.val()),
                display = wrapper.find(`.lukio_range_min_max_display.${indicator_class}`);

            // make sure the gap is minimum 1
            gap = gap > 1 ? gap : 1;
            if (current_max - current_min <= gap) {
                input.val(indicator_class == 'min' ? parseInt(inputs.max.val()) - gap : parseInt(inputs.min.val()) + gap);
            }

            display[display.prop('tagName') == 'INPUT' ? 'val' : 'text'](format.replace('%d', input.val()));
            wrapper.trigger('lukio_range_min_max_track_update')
        })
        .on('input', '.lukio_range_min_max_display', function () {
            clearTimeout(display_timeout);
            display_timeout = setTimeout(() => {
                let input = $(this),
                    format = input.closest('.lukio_range_min_max').attr('format'),
                    value = input.val().replace(/[^0-9]/g, ''),
                    min = input.attr('min'),
                    max = input.attr('max')
                value = value == '' ? 0 : parseInt(value);
                value = value < min ? min : (value > max ? max : value);
                input.val(format.replace('%d', value));
            }, 1000);
        })
        .on('change', '.lukio_range_min_max_display', function () {
            let input = $(this),
                wrapper = input.closest('.lukio_range_min_max'),
                type = input.hasClass('min') ? '.min' : '.max';
            wrapper.find(`.lukio_range_min_max_input${type}`).val(input.val().replace(/[^0-9]/g, '')).trigger('input').trigger('pointerup');
        });

    $('.lukio_range_min_max').trigger('lukio_range_min_max_track_update');
});
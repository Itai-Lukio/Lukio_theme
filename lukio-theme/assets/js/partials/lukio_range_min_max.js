(function ($) {
    $(document).ready(function () {
        class Price_range {
            constructor(wrapper) {
                this.min_slider = wrapper.find('.lukio_range_min_max_input.min');
                this.max_slider = wrapper.find('.lukio_range_min_max_input.max');
                this.min_display = wrapper.find('.lukio_range_min_max_display.min');
                this.max_display = wrapper.find('.lukio_range_min_max_display.max');
                this.track = wrapper.find('.lukio_range_min_max_track');
                this.min_val = this.min_slider[0].min;
                this.max_val = this.min_slider[0].max;
                this.track_divider = this.max_val - this.min_val;
                this.price_gap = this.track_divider * 0.1;
                this.inner_color = wrapper.attr('inner_color');
                this.outer_color = wrapper.attr('outer_color');

                let update = (input) => { this.input_update(input) };
                wrapper.find('.lukio_range_min_max_input').each(function () {
                    let input = $(this);
                    input.val(input.hasClass('min') ? input.attr('min') : input.attr('max'));
                    update(input);
                });
                wrapper.find('.lukio_range_min_max_input').on('input', function () {
                    update(this);
                });
                this.track_update();
            };

            input_update(input) {
                input = $(input);
                let current_min = parseInt(this.min_slider.val());
                let current_max = parseInt(this.max_slider.val());
                if (input.hasClass('min')) {
                    if (current_max - current_min <= this.price_gap) {
                        input.val(current_max - this.price_gap);
                    }
                    this.min_display.text(input.val());
                } else {
                    if (current_max - current_min <= this.price_gap) {
                        input.val(current_min + this.price_gap);
                    }
                    this.max_display.text(input.val());
                };
                this.track_update();
            };

            track_update() {
                let percent1 = ((this.min_slider.val() - this.min_val) * 100) / this.track_divider;
                let percent2 = ((this.max_slider.val() - this.min_val) * 100) / this.track_divider;
                this.track.css('background', `linear-gradient(to right, ${this.outer_color} ${percent1}% , ${this.inner_color} ${percent1}% , ${this.inner_color} ${percent2}%, ${this.outer_color} ${percent2}%)`);
            }
        };

        $('.lukio_range_min_max').each(function () {
            new Price_range($(this));
        })
    })
})(jQuery)
// variable to use the helper class easily
const lukio_helpers = (function ($) {
    /**
     * Lukio base helpers inclosed in a class for easy indexing and prevent conflicts
     */
    class class_lukio_helpers {
        /**
         * Scroll horizontally to the element with in its continer
         * the continer need to be positioned.
         * @param {jQuery} el element to scroll to
         * @param {jQuery} continer continer to scroll
         * @param {String} scroll_behavior scrollTo behavior to use. default 'smooth'
         * 
         * @author Itai Dotan
         */
        scroll_horizontally(el, continer, scroll_behavior = 'smooth') {
            let el_scroll;
            // check what is the continer direction
            if (continer.css('direction') == 'rtl') {
                let mark = new Date().getTime();
                el.addClass('scroll_horizontally_el_' + mark);
                // clone the continer to get the offset with out visual effect on the original
                continer.before(continer.clone().css('height', 0).css('direction', 'ltr').addClass('scroll_horizontally_continer_' + mark));
                el_scroll = ((-1) * ($(`.scroll_horizontally_continer_${mark}`).find(`.scroll_horizontally_el_${mark}`).position()['left']) + parseFloat(continer.css('padding-left').replace('px', '') + continer[0].scrollLeft));
                // clean up
                $(`.scroll_horizontally_continer_${mark}`).remove();
                $(`.scroll_horizontally_el_${mark}`).removeClass(`scroll_horizontally_el_${mark}`);
            } else {
                el_scroll = continer[0].scrollLeft + el.position()['left'];
            }

            continer[0].scrollTo({
                top: 0,
                left: el_scroll,
                behavior: scroll_behavior
            });
        }

        /**
         * animate a given element for a nice open and close effect
         * @param {jQuery} el jq element to trigger 'animate' on
         * @param {String} direction size direction to animate (width || height)
         * @param {Number} size final size the direction will get to
         * @param {Number} time duration of the animation
         * @param {String | number} end end css to set the element on
         * @param {Object} extra_options extra option in an object structure to use in the animaition. see jQuery.animate for more info
         * @param {Function} complete function to run at the end of the animation
         * 
         * @author Itai Dotan
         */
        animate_size(el, direction, size, time, end, extra_options = {}, complete = null) {
            let options = {};
            Object.keys(extra_options).forEach(key => {
                options[key] = extra_options[key];
            });
            options[direction] = size;
            el.animate(options, time, function () {
                el.css(direction, end);
                if (complete) {
                    complete();
                }
            })
        }

        /**
         * get the size of an element
         * @param {jQuery} el jq element to get the size of
         * @param {String} direction size value to get width or height. default 'width'
         * @param {String} open_size open css size to measure. default 'auto'
         * @returns {Number} element value
         * 
         * @author Itai Dotan
         */
        get_size(el, direction = 'width', open_size = 'auto') {
            el.css(direction, open_size);
            let size = this.get_css_numeric_value(el, direction);
            el.css(direction, '');
            return size;
        }

        /**
         * get the css attribute numeric value of an element
         * 
         * column-gap: 10px; => 10
         * 
         * font-size: 28px; => 28px
         * @param {jQuery} el jq element to get the css numeric value of
         * @param {String} css css attribut attribute
         * @returns {Number} css numeric value
         * 
         * @author Itai Dotan
         */
        get_css_numeric_value(el, css) {
            return parseFloat(el.css(css).replace('px', ''));
        }

        /**
         * extension for 'get_css_numeric_value', to get css numeric value of an element with a class to be added later
         * 
         * @param {jQuery} el jq element to get the css numeric value of
         * @param {String} css css attribut attribute
         * @param {String} class_str class name to trigger the different element status needed to get the value of
         * @returns {Number} css numeric value
         * 
         * @author Itai Dotan
         */
        get_css_numeric_value_with_different_class(el, css, class_str) {
            el.addClass(class_str);
            let size = this.get_css_numeric_value(el, css);
            el.removeClass(class_str);
            return size;
        }

        /**
         * set the flex continer width to the highest width avilable for items in a single row
         * 
         * @param {jQuery} flex_element the flex element
         * @param {Number} continer_width the flex element continer width or max width
         * @param {Number} item_width single item width
         * @param {Number} column_gap gap between items
         * @returns {Bool} true when resized, false when the continer_width is equal or lower then item_width
         * 
         * @author Itai Dotan
         */
        flex_width_max_items(flex_element, continer_width, item_width, column_gap) {
            if (continer_width <= item_width) {
                return false;
            };
            flex_element.css('width', (item_width + (Math.floor((continer_width - item_width) / (item_width + column_gap)) * (item_width + column_gap))) + 'px');
            return true;
        }

        /**
         * fix the skewed position of a fixed top 0 element when the wp admin bar is visible below 601px such as mobile menu
         * 
         * @param {jQuery} element the fixed element
         * @param {String} top top position top use. default '0px'
         * @param {String} height height of the element. default '100vh'
         * 
         * @autor Itai Dotan
         */
        mobile_admin_bar_top_fix(element, top = '0px', height = '100vh') {
            if (window.innerWidth < 601 && window.pageYOffset == 0 && $('body').hasClass('admin-bar')) {
                element.css('top', 'calc(' + top + ' + var(--wp-admin--admin-bar--height))').css('height', 'calc(' + height + ' - var(--wp-admin--admin-bar--height))');
            } else {
                element.css('top', '').css('height', '');
            }
        }

        /**
         * track if header sticky state been triggered
         * 
         * @param {String} indication_class class to add when the header is sticky, default `sticky_active`
         * @param {String} header_selector selector of the header, default the main header `header#site_header`
         * 
         * @author Itai Dotan
         */
        header_tracking(indication_class = 'sticky_active', header_selector = 'header#site_header') {
            const header = $(header_selector),
                observer = new IntersectionObserver((entries, observer) => {
                    header[entries[0].isIntersecting ? 'removeClass' : 'addClass'](indication_class);
                });

            // create and add the tracking element
            let elm = document.createElement('div');
            elm.setAttribute('sticky-tracker', 1);
            header.before(elm);

            observer.observe(elm);
        }
    }

    // return the class object to be stored in the const
    return new class_lukio_helpers;
})(jQuery);

// functions to run on ready
(function ($) {
    // remove the no js class indicator
    $('.no_js').removeClass('no_js');

    /**
    * update the css var with the real 100vw of the page
    */
    function lukio_body_width_var() {
        document.documentElement.style.setProperty('--lukio-100vw', $('body').css('width'));
    }
    lukio_body_width_var();

    // rerun on resize of the body to update even when using 'overflow: hidden;' on the body
    const lukio_resize_observer = new ResizeObserver(lukio_body_width_var);
    lukio_resize_observer.observe($('body')[0]);

    $(document)
        // open the dropdown and setup it closing
        .on('click', '.lukio_dropdown_display', function () {
            let selector = $(this),
                ul = selector.find('.lukio_dropdown_display_options_wrapper');
            selector.addClass('open');
            $('body').one('click.lukio_dropdown_clicked', function () {
                ul.addClass('closing');
                selector.removeClass('open');
                setTimeout(() => {
                    ul.removeClass('closing');
                }, 400);
            });
        })
        // update the dropdown select and close the dropdown
        .on('click', '.lukio_dropdown_display_option', function (e) {
            e.stopPropagation();
            let li = $(this);
            if (li.hasClass('selected')) {
                return;
            }

            let value = li.data('value'),
                wrapper = li.closest('.lukio_dropdown'),
                display = wrapper.find('.lukio_dropdown_display_text');

            wrapper.find('.lukio_dropdown_display_option.selected').removeClass('selected');
            li.addClass('selected');
            wrapper.find(`select`).val(value).trigger('change');

            display.text(li.text());
            $('body').trigger('click.lukio_dropdown_clicked');
        });

    // use to add drag scroll to elements
    $.fn.lukioDragScroll = function () {
        this.each(function () {
            let el = $(this),
                top = 0,
                left = 0,
                x = 0,
                y = 0;

            el.css({
                cursor: 'grab',
                userSelect: '',
            });

            el.on('pointerdown', (e) => {
                pointerDownHandler(e);
            });

            function pointerDownHandler(e) {
                e.preventDefault();
                el.css({
                    cursor: 'grabbing',
                    userSelect: 'none',
                });

                left = el[0].scrollLeft;
                top = el[0].scrollTop;
                x = e.clientX;
                y = e.clientY;

                $(window).on('pointermove.lukio_drag', (e) => {
                    pointerMoveHandler(e);
                });
                $(window).on('pointerup.lukio_drag', () => {
                    pointerUpHandler();
                });
            }

            function pointerMoveHandler(e) {
                let dx = e.clientX - x;
                let dy = e.clientY - y;

                // Scroll the element
                el[0].scrollTop = top - dy;
                el[0].scrollLeft = left - dx;
            };

            function pointerUpHandler() {
                el.css({
                    cursor: 'grab',
                    userSelect: '',
                });
                jQuery(window).off('pointermove.lukio_drag');
                jQuery(window).off('pointerup.lukio_drag');
            }
        });
        return this;
    }
})(jQuery)
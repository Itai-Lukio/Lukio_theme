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
         * 
         * @author Itai Dotan
         */
        scroll_horizontally(el, continer) {
            let el_scroll;
            // check what is the continer direction
            if (continer.css('direction') == 'rtl') {
                let mark = new Date().getTime();
                el.addClass('scroll_horizontally_el_' + mark);
                // clone the continer to get the offset with out visual effect on the original
                continer.before(continer.clone().css('height', 0).css('direction', 'ltr').addClass('scroll_horizontally_continer_' + mark));
                el_scroll = ((-1) * $(`.scroll_horizontally_continer_${mark}`).find(`.scroll_horizontally_el_${mark}`).position()['left']) + parseFloat(continer.css('padding-left').replace('px', ''));
                // clean up
                $(`.scroll_horizontally_continer_${mark}`).remove();
                $(`.scroll_horizontally_el_${mark}`).removeClass(`scroll_horizontally_el_${mark}`);
            } else {
                el_scroll = el.position()['left'];
            }

            continer[0].scrollTo({
                top: 0,
                left: el_scroll,
                behavior: 'smooth'
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
                element.css('top', 'calc(' + top + '- var(--wp-admin--admin-bar--height))').css('height', 'calc(' + height + '- var(--wp-admin--admin-bar--height))');
            }
        }
    }

    // return the class object to be stored in the const
    return new class_lukio_helpers;
})(jQuery);

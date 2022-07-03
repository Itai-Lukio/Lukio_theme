/**
 * Lukio base helpers inclosed in a class for easy indexing and prevent conflicts
 */
class Class_lukio_helpers {
    /**
     * Scroll horizontally to the element with in its continer
     * the continer need to be positioned.
     * @param {jQuery} el element to scroll to
     * @param {jQuery} continer continer to scroll
     * 
     * @author Itai Dotan
     */
    scroll_horizontally(el, continer) {
        // check what is the continer direction
        if (continer.css('direction') == 'rtl') {
            this.mark = new Date().getTime();
            el.addClass('scroll_horizontally_el_' + this.mark);
            // clone the continer to get the offset with out visual effect on the original
            continer.before(continer.clone().css('height', 0).css('direction', 'ltr').addClass('scroll_horizontally_continer_' + this.mark));
            this.el_scroll = ((-1) * jQuery(`.scroll_horizontally_continer_${this.mark}`).find(`.scroll_horizontally_el_${this.mark}`).position()['left']) + parseFloat(continer.css('padding-left').replace('px', ''));
            // clean up
            jQuery(`.scroll_horizontally_continer_${this.mark}`).remove();
            jQuery(`.scroll_horizontally_el_${this.mark}`).removeClass(`scroll_horizontally_el_${this.mark}`);
        } else {
            this.el_scroll = el.position()['left'];
        }

        continer[0].scrollTo({
            top: 0,
            left: this.el_scroll,
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
        this.options = {};
        Object.keys(extra_options).forEach(key => {
            this.options[key] = extra_options[key];
        });
        this.options[direction] = size;
        el.animate(this.options, time, function () {
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
        this.size = this.get_css_numeric_value(el, direction);
        el.css(direction, '');
        return this.size;
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
        this.size = this.get_css_numeric_value(el, css);
        el.removeClass(class_str);
        return this.size;
    }

    /**
     * set the flex continer width to the highest width avilable for items in a single row
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
}

// variable to use the helper class easily
const lukio_helpers = new Class_lukio_helpers;

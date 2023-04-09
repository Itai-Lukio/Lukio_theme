<?php

/**
 * lukio theme support and handler SVG uploads
 * 
 */
class Lukio_SVG_Support
{

    /**
     * defines the whitelist of elements and attributes allowed
     * 
     * @var array $whitelist array of the allowed attributes
     */
    private static $whitelist = array(
        "a" => array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "id", "mask", "opacity", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform", "href", "xlink:href", "xlink:title"),
        "circle" => array("class", "clip-path", "clip-rule", "cx", "cy", "fill", "fill-opacity", "fill-rule", "filter", "id", "mask", "opacity", "r", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
        "clipPath" => array("class", "clipPathUnits", "id"),
        "defs" => array(),
        "style" => array("type"),
        "desc" => array(),
        "ellipse" => array("class", "clip-path", "clip-rule", "cx", "cy", "fill", "fill-opacity", "fill-rule", "filter", "id", "mask", "opacity", "requiredFeatures", "rx", "ry", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
        "feGaussianBlur" => array("class", "color-interpolation-filters", "id", "requiredFeatures", "stdDeviation"),
        "filter" => array("class", "color-interpolation-filters", "filterRes", "filterUnits", "height", "id", "primitiveUnits", "requiredFeatures", "width", "x", "xlink:href", "y"),
        "foreignObject" => array("class", "font-size", "height", "id", "opacity", "requiredFeatures", "style", "transform", "width", "x", "y"),
        "g" => array("class", "clip-path", "clip-rule", "id", "display", "fill", "fill-opacity", "fill-rule", "filter", "mask", "opacity", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform", "font-family", "font-size", "font-style", "font-weight", "text-anchor"),
        "image" => array("class", "clip-path", "clip-rule", "filter", "height", "id", "mask", "opacity", "requiredFeatures", "style", "systemLanguage", "transform", "width", "x", "xlink:href", "xlink:title", "y"),
        "line" => array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "id", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform", "x1", "x2", "y1", "y2"),
        "linearGradient" => array("class", "id", "gradientTransform", "gradientUnits", "requiredFeatures", "spreadMethod", "systemLanguage", "x1", "x2", "xlink:href", "y1", "y2"),
        "marker" => array("id", "class", "markerHeight", "markerUnits", "markerWidth", "orient", "preserveAspectRatio", "refX", "refY", "systemLanguage", "viewBox"),
        "mask" => array("class", "height", "id", "maskContentUnits", "maskUnits", "width", "x", "y", "style", "fill"),
        "metadata" => array("class", "id"),
        "path" => array("class", "clip-path", "clip-rule", "d", "fill", "fill-opacity", "fill-rule", "filter", "id", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
        "pattern" => array("class", "height", "id", "patternContentUnits", "patternTransform", "patternUnits", "requiredFeatures", "style", "systemLanguage", "viewBox", "width", "x", "xlink:href", "y"),
        "polygon" => array("class", "clip-path", "clip-rule", "id", "fill", "fill-opacity", "fill-rule", "filter", "id", "class", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "points", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
        "polyline" => array("class", "clip-path", "clip-rule", "id", "fill", "fill-opacity", "fill-rule", "filter", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "points", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
        "radialGradient" => array("class", "cx", "cy", "fx", "fy", "gradientTransform", "gradientUnits", "id", "r", "requiredFeatures", "spreadMethod", "systemLanguage", "xlink:href"),
        "rect" => array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "height", "id", "mask", "opacity", "requiredFeatures", "rx", "ry", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform", "width", "x", "y"),
        "stop" => array("class", "id", "offset", "requiredFeatures", "stop-color", "stop-opacity", "style", "systemLanguage"),
        "svg" => array("class", "clip-path", "clip-rule", "filter", "id", "height", "mask", "preserveAspectRatio", "requiredFeatures", "style", "systemLanguage", "viewBox", "width", "x", "xmlns", "xmlns:se", "xmlns:xlink", "y", 'fill'),
        "switch" => array("class", "id", "requiredFeatures", "systemLanguage"),
        "symbol" => array("class", "fill", "fill-opacity", "fill-rule", "filter", "font-family", "font-size", "font-style", "font-weight", "id", "opacity", "preserveAspectRatio", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform", "viewBox"),
        "text" => array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "font-family", "font-size", "font-style", "font-weight", "id", "mask", "opacity", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "text-anchor", "transform", "x", "xml:space", "y"),
        "textPath" => array("class", "id", "method", "requiredFeatures", "spacing", "startOffset", "style", "systemLanguage", "transform", "xlink:href"),
        "title" => array(),
        "tspan" => array("class", "clip-path", "clip-rule", "dx", "dy", "fill", "fill-opacity", "fill-rule", "filter", "font-family", "font-size", "font-style", "font-weight", "id", "mask", "opacity", "requiredFeatures", "rotate", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "text-anchor", "textLength", "transform", "x", "xml:space", "y"),
        "use" => array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "height", "id", "mask", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "transform", "width", "x", "xlink:href", "y"),
        "feFlood" => array("id", "lang", "tabindex", "xml:base", "xml:lang", "xml:space", "alignment-baseline", "baseline-shift", "clip", "clip-path", "clip-rule", "color", "color-interpolation", "color-interpolation-filters", "color-profile", "color-rendering", "cursor", "direction", "display", "dominant-baseline", "enable-background", "fill", "fill-opacity", "fill-rule", "filter", "flood-color", "flood-opacity", "font-family", "font-size", "font-size-adjust", "font-stretch", "font-style", "font-variant", "font-weight", "glyph-orientation-horizontal", "glyph-orientation-vertical", "image-rendering", "kerning", "letter-spacing", "lighting-color", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "overflow", "pointer-events", "shape-rendering", "stop-color", "stop-opacity", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "text-anchor", "text-decoration", "text-rendering", "transform", "transform-origin", "unicode-bidi", "vector-effect", "visibility", "word-spacing", "writing-mode", "type", "tableValues", "slope", "intercept", "amplitude", "exponent", "offset", "class", "style", "flood-color", "flood-opacity", "result"),
        "feColorMatrix" => array("id", "lang", "tabindex", "xml:base", "xml:lang", "xml:space", "alignment-baseline", "baseline-shift", "clip", "clip-path", "clip-rule", "color", "color-interpolation", "color-interpolation-filters", "color-profile", "color-rendering", "cursor", "direction", "display", "dominant-baseline", "enable-background", "fill", "fill-opacity", "fill-rule", "filter", "flood-color", "flood-opacity", "font-family", "font-size", "font-size-adjust", "font-stretch", "font-style", "font-variant", "font-weight", "glyph-orientation-horizontal", "glyph-orientation-vertical", "image-rendering", "kerning", "letter-spacing", "lighting-color", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "overflow", "pointer-events", "shape-rendering", "stop-color", "stop-opacity", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "text-anchor", "text-decoration", "text-rendering", "transform", "transform-origin", "unicode-bidi", "vector-effect", "visibility", "word-spacing", "writing-mode", "type", "tableValues", "slope", "intercept", "amplitude", "exponent", "offset", "class", "style", "in", "type", "values", "result"),
        "feOffset" => array("id", "lang", "tabindex", "xml:base", "xml:lang", "xml:space", "alignment-baseline", "baseline-shift", "clip", "clip-path", "clip-rule", "color", "color-interpolation", "color-interpolation-filters", "color-profile", "color-rendering", "cursor", "direction", "display", "dominant-baseline", "enable-background", "fill", "fill-opacity", "fill-rule", "filter", "flood-color", "flood-opacity", "font-family", "font-size", "font-size-adjust", "font-stretch", "font-style", "font-variant", "font-weight", "glyph-orientation-horizontal", "glyph-orientation-vertical", "image-rendering", "kerning", "letter-spacing", "lighting-color", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "overflow", "pointer-events", "shape-rendering", "stop-color", "stop-opacity", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "text-anchor", "text-decoration", "text-rendering", "transform", "transform-origin", "unicode-bidi", "vector-effect", "visibility", "word-spacing", "writing-mode", "type", "tableValues", "slope", "intercept", "amplitude", "exponent", "offset", "class", "style", "in", "dx", "dy"),
        "feComposite" => array("id", "lang", "tabindex", "xml:base", "xml:lang", "xml:space", "alignment-baseline", "baseline-shift", "clip", "clip-path", "clip-rule", "color", "color-interpolation", "color-interpolation-filters", "color-profile", "color-rendering", "cursor", "direction", "display", "dominant-baseline", "enable-background", "fill", "fill-opacity", "fill-rule", "filter", "flood-color", "flood-opacity", "font-family", "font-size", "font-size-adjust", "font-stretch", "font-style", "font-variant", "font-weight", "glyph-orientation-horizontal", "glyph-orientation-vertical", "image-rendering", "kerning", "letter-spacing", "lighting-color", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "overflow", "pointer-events", "shape-rendering", "stop-color", "stop-opacity", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "text-anchor", "text-decoration", "text-rendering", "transform", "transform-origin", "unicode-bidi", "vector-effect", "visibility", "word-spacing", "writing-mode", "type", "tableValues", "slope", "intercept", "amplitude", "exponent", "offset", "class", "style", "in", "in2", "operator", "k1", "k2", "k3", "k4"),
        "feBlend" => array("id", "lang", "tabindex", "xml:base", "xml:lang", "xml:space", "alignment-baseline", "baseline-shift", "clip", "clip-path", "clip-rule", "color", "color-interpolation", "color-interpolation-filters", "color-profile", "color-rendering", "cursor", "direction", "display", "dominant-baseline", "enable-background", "fill", "fill-opacity", "fill-rule", "filter", "flood-color", "flood-opacity", "font-family", "font-size", "font-size-adjust", "font-stretch", "font-style", "font-variant", "font-weight", "glyph-orientation-horizontal", "glyph-orientation-vertical", "image-rendering", "kerning", "letter-spacing", "lighting-color", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "overflow", "pointer-events", "shape-rendering", "stop-color", "stop-opacity", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "text-anchor", "text-decoration", "text-rendering", "transform", "transform-origin", "unicode-bidi", "vector-effect", "visibility", "word-spacing", "writing-mode", "type", "tableValues", "slope", "intercept", "amplitude", "exponent", "offset", "class", "style", "in", "in2", "mode", "result"),
    );

    /**
     * construct action to run when creating a new instance
     * 
     * @author Itai Dotan
     */
    public function __construct()
    {
        add_action('upload_mimes', array($this, 'add_mimes'));
        add_filter('wp_check_filetype_and_ext', array($this, 'fix_mime_type_svg'), 75, 3);
        add_filter('wp_handle_upload_prefilter', array($this, 'sanitize_uploaded_svg'));
    }

    /**
     * add .svg to the allowed upload file extension
     * 
     * @param array $file_types mime types keyed by the file extension regex corresponding to those types
     * @return array updated $file_types with the new types to allow
     * 
     * @author Itai Dotan
     */
    public function add_mimes($file_types)
    {

        $new_filetypes = array();
        $new_filetypes['svg'] = 'image/svg';
        $new_filetypes['svgz'] = 'image/svg';
        $file_types = array_merge($file_types, $new_filetypes);

        return $file_types;
    }

    /**
     * fix mime type when missing
     * 
     * @param Array $data values for the extension, mime type, and corrected filename
     * @param String $file full path to the file
     * @param String $filename name of the file
     * @return Array fixed file data
     * 
     * @author Itai Dotan
     */
    public function fix_mime_type_svg($data = null, $file = null, $filename = null)
    {
        $ext = isset($data['ext']) ? $data['ext'] : '';
        if (strlen($ext) < 1) {
            $exploded = explode('.', $filename);
            $ext      = strtolower(end($exploded));
        }
        if ($ext === 'svg') {
            $data['type'] = 'image/svg';
            $data['ext']  = 'svg';
        } elseif ($ext === 'svgz') {
            $data['type'] = 'image/svg';
            $data['ext']  = 'svgz';
        }

        return $data;
    }

    /**
     * remove any thing before the svg tag
     * 
     * @author Itai Dotan
     */
    private function lukio_remove_parts_before_svg($svg)
    {
        return trim(substr($svg, strpos($svg, '<svg')));
    }

    /**
     * sanitize and upload svg files
     * 
     * @param Array $file An array of data for a single file
     * @return Array sanitized file data
     * 
     * @author Itai Dotan
     */
    public function sanitize_uploaded_svg($file)
    {
        // Ensure we have a proper file path before processing
        if (!isset($file['tmp_name'])) {
            return $file;
        }

        $file_name = isset($file['name']) ? $file['name'] : '';
        $wp_filetype = wp_check_filetype_and_ext($file['tmp_name'], $file_name);
        $type = !empty($wp_filetype['type']) ? $wp_filetype['type'] : '';

        if ($type === 'image/svg') {
            file_put_contents($file['tmp_name'], $this->lukio_remove_parts_before_svg(file_get_contents($file['tmp_name'])));

            $clean = $this->sanitize_svg_file($file['tmp_name']);

            // clean the xml tag
            $clean = $this->lukio_remove_parts_before_svg($clean);

            // clean php from the svg
            $got_php = strpos($clean, '<?php');
            while ($got_php !== false) {
                $php_cleaning = trim(substr($clean, 0, $got_php));
                $php_cleaning .= trim(substr($clean, strpos($clean, '?>') + 2));
                $clean = $php_cleaning;
                $got_php = strpos($clean, '<?php');
            }
            // update the file
            file_put_contents($file['tmp_name'], $clean);
        }
        return $file;
    }

    /**
     * sanitize svg XML document
     * 
     * base on https://github.com/alnorris/SVG-Sanitizer 
     * 
     * @param string file path to XML document
     * @return string sanitized svg string
     * 
     * @author Itai Dotan
     */
    public function sanitize_svg_file($file)
    {
        $xmlDoc = new DOMDocument();
        $xmlDoc->preserveWhiteSpace = false;

        $xmlDoc->load($file);

        // all elements in xml doc
        $allElements = $xmlDoc->getElementsByTagName("*");

        // loop through all elements
        for ($i = 0; $i < $allElements->length; $i++) {
            $currentNode = $allElements->item($i);


            // array of allowed attributes in specific element
            $whitelist_attr_arr = self::$whitelist[$currentNode->tagName];

            // does element exist in whitelist?
            if (isset($whitelist_attr_arr)) {

                for ($x = 0; $x < $currentNode->attributes->length; $x++) {

                    // get attributes name
                    $attrName = $currentNode->attributes->item($x)->name;

                    // check if attribute isn't in whiltelist
                    if (!in_array($attrName, $whitelist_attr_arr)) {
                        $currentNode->removeAttribute($attrName);
                        // $x--;
                    }
                }
            }

            // else remove element
            else {
                $currentNode->parentNode->removeChild($currentNode);
                // $i--;
            }
        }

        $xmlDoc->formatOutput = true;
        return ($xmlDoc->saveXML());
    }
}
new Lukio_SVG_Support();

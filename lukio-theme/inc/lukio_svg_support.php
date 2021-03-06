<?php

if (!function_exists('lukio_add_svg_file_types_to_uploads')) {
    /**
     * add .svg to the allowed upload file extension
     * 
     * @author Itai Dotan
     */
    function lukio_add_svg_file_types_to_uploads($file_types)
    {

        $new_filetypes = array();
        $new_filetypes['svg'] = 'image/svg';
        $new_filetypes['svgz'] = 'image/svg';
        $file_types = array_merge($file_types, $new_filetypes);

        return $file_types;
    }
    add_action('upload_mimes', 'lukio_add_svg_file_types_to_uploads');
}

if (!function_exists('lukio_fix_mime_type_svg')) {
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
    function lukio_fix_mime_type_svg($data = null, $file = null, $filename = null)
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

    add_filter('wp_check_filetype_and_ext', 'lukio_fix_mime_type_svg', 75, 3);
}

if (!function_exists('lukio_remove_parts_before_svg')) {
    /**
     * remove any thing before the svg tag
     * 
     * @author Itai Dotan
     */
    function lukio_remove_parts_before_svg($svg)
    {
        return trim(substr($svg, strpos($svg, '<svg')));
    }
}

if (!function_exists('lukio_sanitize_svg_upload')) {
    /**
     * sanitize and upload svg files
     * 
     * @param Array $file An array of data for a single file
     * 
     * @author Itai Dotan
     */
    function lukio_sanitize_svg_upload($file)
    {
        // Ensure we have a proper file path before processing
        if (!isset($file['tmp_name'])) {
            return $file;
        }

        $file_name = isset($file['name']) ? $file['name'] : '';
        $wp_filetype = wp_check_filetype_and_ext($file['tmp_name'], $file_name);
        $type = !empty($wp_filetype['type']) ? $wp_filetype['type'] : '';

        if ($type === 'image/svg') {
            file_put_contents($file['tmp_name'], lukio_remove_parts_before_svg(file_get_contents($file['tmp_name'])));
            $svg = new SvgSanitizer();
            $svg->load(($file['tmp_name']));
            $svg->sanitize();

            $clean = $svg->saveSVG();

            // clean the xml tag
            $clean = lukio_remove_parts_before_svg($clean);

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

    add_filter('wp_handle_upload_prefilter', 'lukio_sanitize_svg_upload');
}

/**
 *  SVGSantiizer
 * 
 *  Whitelist-based PHP SVG sanitizer.
 * 
 *  @link https://github.com/alister-/SVG-Sanitizer}
 *  @author Alister Norris
 *  @copyright Copyright (c) 2013 Alister Norris
 *  @license http://opensource.org/licenses/mit-license.php The MIT License
 *  @package svgsanitizer
 */

class SvgSanitizer
{

    //private $remoteHref = false;		// Check if hrefs in XML can goto remote locations
    private $xmlDoc;                // PHP XML DOMDocument

    // defines the whitelist of elements and attributes allowed.
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
        "mask" => array("class", "height", "id", "maskContentUnits", "maskUnits", "width", "x", "y"),
        "metadata" => array("class", "id"),
        "path" => array("class", "clip-path", "clip-rule", "d", "fill", "fill-opacity", "fill-rule", "filter", "id", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
        "pattern" => array("class", "height", "id", "patternContentUnits", "patternTransform", "patternUnits", "requiredFeatures", "style", "systemLanguage", "viewBox", "width", "x", "xlink:href", "y"),
        "polygon" => array("class", "clip-path", "clip-rule", "id", "fill", "fill-opacity", "fill-rule", "filter", "id", "class", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "points", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
        "polyline" => array("class", "clip-path", "clip-rule", "id", "fill", "fill-opacity", "fill-rule", "filter", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "points", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
        "radialGradient" => array("class", "cx", "cy", "fx", "fy", "gradientTransform", "gradientUnits", "id", "r", "requiredFeatures", "spreadMethod", "systemLanguage", "xlink:href"),
        "rect" => array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "height", "id", "mask", "opacity", "requiredFeatures", "rx", "ry", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform", "width", "x", "y"),
        "stop" => array("class", "id", "offset", "requiredFeatures", "stop-color", "stop-opacity", "style", "systemLanguage"),
        "svg" => array("class", "clip-path", "clip-rule", "filter", "id", "height", "mask", "preserveAspectRatio", "requiredFeatures", "style", "systemLanguage", "viewBox", "width", "x", "xmlns", "xmlns:se", "xmlns:xlink", "y"),
        "switch" => array("class", "id", "requiredFeatures", "systemLanguage"),
        "symbol" => array("class", "fill", "fill-opacity", "fill-rule", "filter", "font-family", "font-size", "font-style", "font-weight", "id", "opacity", "preserveAspectRatio", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform", "viewBox"),
        "text" => array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "font-family", "font-size", "font-style", "font-weight", "id", "mask", "opacity", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "text-anchor", "transform", "x", "xml:space", "y"),
        "textPath" => array("class", "id", "method", "requiredFeatures", "spacing", "startOffset", "style", "systemLanguage", "transform", "xlink:href"),
        "title" => array(),
        "tspan" => array("class", "clip-path", "clip-rule", "dx", "dy", "fill", "fill-opacity", "fill-rule", "filter", "font-family", "font-size", "font-style", "font-weight", "id", "mask", "opacity", "requiredFeatures", "rotate", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "text-anchor", "textLength", "transform", "x", "xml:space", "y"),
        "use" => array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "height", "id", "mask", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "transform", "width", "x", "xlink:href", "y"),
    );

    function __construct()
    {
        $this->xmlDoc = new DOMDocument();
        $this->xmlDoc->preserveWhiteSpace = false;
    }

    // load XML SVG
    function load($file)
    {
        $this->xmlDoc->load($file);
    }

    function sanitize()
    {
        // all elements in xml doc
        $allElements = $this->xmlDoc->getElementsByTagName("*");

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
                        // comment out from original
                        // $x--;
                    }
                }
            }

            // else remove element
            else {

                $currentNode->parentNode->removeChild($currentNode);
                // comment out from original
                // $i--;
            }
        }
    }

    function saveSVG()
    {
        $this->xmlDoc->formatOutput = true;
        return ($this->xmlDoc->saveXML());
    }
}

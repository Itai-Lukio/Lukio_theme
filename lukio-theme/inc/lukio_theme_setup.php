<?php
defined('ABSPATH') || exit;

/**
 * lukio theme main setup
 */
class Lukio_Theme_setup
{
    /**
     * array of the roles with the admin bar guides
     * 
     * @var array array of user roles name
     */
    private static $guides_allowerd_roles = array('administrator');

    /**
     * construct action to run when creating a new instance
     * 
     * @author Itai Dotan
     */
    public function __construct()
    {
        add_action('after_setup_theme', array($this, 'theme_setup'));
        add_action('after_switch_theme', array($this, 'custom_user_role'));
        add_action('after_switch_theme', array($this, 'create_options'));
        // remove from wp_head as we trigger it manual before wp_head() and dont want to trigger it both times
        remove_action('wp_head', 'wp_enqueue_scripts', 1);
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));
        add_action('wp_before_admin_bar_render', array($this, 'theme_admin_setup_enqueues'));
        add_action('init', array($this, 'classic_editor'));
        add_action('admin_bar_menu', array($this, 'wp_admin_bar_branding'), 40);
        add_action('lukio_theme_updated', array($this, 'updated'));
        add_filter('acf/settings/capability', array($this, 'acf_custom_fields_tab_restriction'));
        add_action('upload_mimes', array($this, 'add_mimes'));

        /**
         * add 'lukio' class to the body
         * 
         * @author Itai Dotan
         */
        add_filter('body_class', function ($classes) {
            return array_merge($classes, array('lukio'));
        });

        /**
         * add 'lukio' class to the body in admin pages
         * 
         * @author Itai Dotan
         */
        add_filter('admin_body_class', function ($classes) {
            return $classes . ' lukio ';
        });

        $this->create_acf_option();
    }

    /**
     * setup the theme and theme support
     * 
     * @author Itai Dotan
     */
    public function theme_setup()
    {
        // Load text domain
        load_theme_textdomain('lukio-theme', get_template_directory() . '/languages');

        // Add default posts and comments RSS feed links to head.
        add_theme_support('automatic-feed-links');

        /*
        * Let WordPress manage the document title.
        * This theme does not use a hard-coded <title> tag in the document head,
        * WordPress will provide it for us.
        */
        add_theme_support('title-tag');

        /**
         * Add post-formats support.
         */
        add_theme_support(
            'post-formats',
            array(
                'link',
                'aside',
                'gallery',
                'image',
                'quote',
                'status',
                'video',
                'audio',
                'chat',
            )
        );

        /*
        * Enable support for Post Thumbnails on posts and pages.
        *
        * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
        */
        add_theme_support('post-thumbnails');
        set_post_thumbnail_size(1568, 9999);

        /**
         * Register header and footer nav
         */
        register_nav_menus(
            array(
                'header' => __('Header menu', 'lukio-theme'),
                'footer'  => __('Footer menu', 'lukio-theme'),
            )
        );

        /*
        * Switch default core markup for search form, comment form, and comments
        * to output valid HTML5.
        */
        add_theme_support(
            'html5',
            array(
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
                'navigation-widgets',
            )
        );

        /*
        * Add support for core custom logo.
        *
        * @link https://codex.wordpress.org/Theme_Logo
        */
        $logo_width  = 300;
        $logo_height = 100;

        add_theme_support(
            'custom-logo',
            array(
                'height'               => $logo_height,
                'width'                => $logo_width,
                'flex-width'           => true,
                'flex-height'          => true,
                'unlink-homepage-logo' => false,
            )
        );

        /*
         * Add a new image size to wordpress
         * 
         * @link https://developer.wordpress.org/reference/functions/add_image_size/
         */
        add_image_size('custom-size', 220, 180);
    }

    /**
     * create custom user role and update its capabilities when the theme is activated or updated
     * 
     * @author Itai Dotan
     */
    public function custom_user_role()
    {
        $site_manager = add_role('site_manager', __('Site manager', 'lukio-theme'), []);
        global $wp_roles;
        if (is_null($site_manager)) {
            $site_manager = $wp_roles->get_role('site_manager');
        }

        $admin_capabilities = $wp_roles->get_role('administrator')->capabilities;
        // $admin_capabilities['promote_users'] = false;
        $admin_capabilities['switch_themes'] = false;
        $admin_capabilities['edit_themes'] = false;
        $admin_capabilities['install_themes'] = false;
        // $admin_capabilities['activate_plugins'] = false;
        // $admin_capabilities['edit_plugins'] = false;
        $admin_capabilities['install_plugins'] = false;
        $admin_capabilities['update_plugins'] = false;
        $admin_capabilities['delete_plugins'] = false;
        $admin_capabilities['update_core'] = false;

        foreach (apply_filters('lukio_theme_site_manager_capabilities', $admin_capabilities) as $cap => $grant) {
            $site_manager->add_cap($cap, $grant);
        }
    }

    /**
     * create lukio options when there are not been created yet
     * 
     * @author Itai Dotan
     */
    public function create_options()
    {
        $options = ['pixels_head', 'pixels_body', 'site_colors', 'disable_enqueue_min'];
        $prefix = 'lukio_';
        foreach ($options as $option) {
            if (!get_option($prefix . $option)) {
                add_option($prefix . $option);
            }
        }
    }

    /**
     * enqueue the theme base styles and scripts
     * 
     * @author Itai Dotan
     */
    public function enqueue()
    {
        lukio_enqueue('/style.css', 'lukio_main_theme_stylesheet', array(), array('parent' => true));
        lukio_enqueue('/assets/css/general.css', 'lukio_main_theme_general_stylesheet', array(), array('parent' => true));
        lukio_enqueue('/assets/js/lukio_theme.js', 'lukio_main_theme_script', array('jquery'), array('parent' => true));
        wp_localize_script(
            'lukio_main_theme_script',
            'lukio_localize',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
            )
        );
        if (file_exists(get_stylesheet_directory() . '/assets/css/site_colors.css')) {
            lukio_enqueue('/assets/css/site_colors.css', 'lukio_site_colors');
        };
    }

    /**
     * enqueue relevant files for the admin bar 
     * 
     * @author Itai Dotan
     */
    public function theme_admin_setup_enqueues()
    {
        lukio_enqueue('/assets/css/lukio_admin.css', 'lukio_theme_admin_stylesheet', array(), array('parent' => true));
    }

    /**
     * add filter to use classsic editor
     * 
     * use add_filter('lukio_use_block_editor', '__return_true') to use the block editor
     * 
     * @author Itai Dotan
     */
    public function classic_editor()
    {
        if (apply_filters('lukio_use_block_editor', false)) {
            return;
        }
        // enable classic editor for wordpress
        add_filter('use_block_editor_for_post', '__return_false');
    }

    /**
     * add lukio guides to wp-admin bar
     * 
     * use add_filter('lukio_no_branding_to_none_admin', '__return_true') to disable the branding to none admins
     * 
     * @param WP_Admin_Bar the WP_Admin_Bar instance, passed by reference
     * 
     * @author Itai Dotan
     */
    public function wp_admin_bar_branding($wp_admin_bar)
    {
        $user_roles = wp_get_current_user()->roles;
        if (apply_filters('lukio_no_branding_to_none_admin', false)) {
            if (!in_array('administrator', $user_roles)) {
                return;
            }
        }

        $svg = '<svg id="lukio_guides_svg" xmlns="http://www.w3.org/2000/svg" width="35" viewBox="0 0 107.87 40.12"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="lukio_guides_svg_u" d="M24.14,29c-7.8,0-13-5-13-12.47V1h9.68V16.57c0,2.39,1.1,3.56,3.36,3.56s3.52-1.22,3.52-3.61V1h9.68V16.57C37.34,24,32,29,24.14,29Z"></path><path class="lukio_guides_svg_u_border" d="M36.34,2V16.57C36.34,23.29,31.73,28,24.15,28s-12-4.75-12-11.47V2h7.68V16.57c0,3,1.58,4.56,4.37,4.56s4.51-1.59,4.51-4.61V2h7.68m2-2H26.66V16.52c0,1.85-.73,2.61-2.51,2.61-1.43,0-2.37-.44-2.37-2.56V0H10.1V16.57C10.1,24.63,15.74,30,24.15,30s14.19-5.41,14.19-13.47V0Z"></path><path class="lukio_guides_svg_text" d="M93.93,12.18a13.55,13.55,0,0,0-14,14,13.95,13.95,0,1,0,27.89,0C107.87,17.86,101.84,12.2,93.93,12.18Zm0,19.9a5.93,5.93,0,0,1,0-11.86,5.93,5.93,0,1,1,0,11.86Z"></path><polygon class="lukio_guides_svg_text" points="76.7 12.81 69.02 12.81 69.02 39.4 76.7 39.4 76.7 39.4 76.7 39.4 76.7 12.81 76.7 12.81 76.7 12.81"></polygon><polygon class="lukio_guides_svg_text l" points="7.68 2.08 0 2.08 0 32.2 0 39.4 7.68 39.4 35.95 39.4 35.95 32.2 7.68 32.2 7.68 2.08"></polygon><polygon class="lukio_guides_svg_text" points="67.11 12.81 58.03 12.81 48.38 25 48.38 2.08 40.7 2.08 40.7 39.4 48.38 39.4 48.38 26.54 58.03 39.4 67.44 39.4 56.74 25.48 67.11 12.81"></polygon><path class="lukio_guides_svg_text" d="M72.89,10.3a4.15,4.15,0,1,0-4.23-4.17A4.14,4.14,0,0,0,72.89,10.3Z"></path></g></g></svg>';

        $allowerd_roles = apply_filters('lukio_admin_guides_roles', self::$guides_allowerd_roles);

        if (count(array_intersect($allowerd_roles, $user_roles)) > 0) {
            $wp_admin_bar->add_node(array(
                'id'    => 'lukio_guides',
                'title' => "$svg " . _n('guide', 'guides', 2, 'lukio-theme'),
            ));

            $wp_admin_bar->add_node(array(
                'id' => 'lukio_guide_php',
                'title' => "PHP " .  _n('guide', 'guides', 1, 'lukio-theme'),
                'parent' => 'lukio_guides',
                'href' => get_template_directory_uri() . '/guides/php.txt',
                'meta' => array(
                    'target' => '_blank',
                ),
            ));

            $wp_admin_bar->add_node(array(
                'id' => 'lukio_guide_js',
                'title' => "JS " .  _n('guide', 'guides', 1, 'lukio-theme'),
                'parent' => 'lukio_guides',
                'href' => get_template_directory_uri() . '/guides/js.txt',
                'meta' => array(
                    'target' => '_blank',
                ),
            ));
        } else {
            $wp_admin_bar->add_node(array(
                'id'    => 'lukio_guides',
                'title' => '<a id="lukio_guides_site_link" href="https://lukio.pro/" target="_blank">' . $svg . '</a>',
            ));
        }
    }

    /**
     * create acf option page when acf plugin is present
     */
    private function create_acf_option()
    {
        // Add the Systems options pgae
        if (function_exists('acf_add_options_page')) {
            add_action('after_setup_theme', function () {
                acf_add_options_page(array(
                    'page_title'  => __('Theme Setup', 'lukio-theme'),
                    'menu_title'  => __('Theme Setup', 'lukio-theme'),
                    'menu_slug'   => 'theme-setup',
                    'capability'  => 'edit_posts',
                    'redirect'    => false,
                    'position'    => 2,
                    'icon_url'    => 'dashicons-shortcode'
                ));
            });
        }
    }

    /**
     * make it so only 'administrator' user can see the custom fields tab and edit its content
     * 
     * @author Itai Dotan
     */
    public function acf_custom_fields_tab_restriction($capability)
    {
        return 'administrator';
    }

    /**
     * actions to take when the theme was updated
     * 
     * @author Itai Dotan
     */
    public function updated()
    {
        $this->custom_user_role();
        $this->create_options();
    }

    /**
     * add file types to the allowed upload file extension.
     * for file type that dont need sanitizing
     * 
     * @param array $file_types mime types keyed by the file extension regex corresponding to those types
     * @return array updated $file_types with the new types to allow
     * 
     * @author Itai Dotan
     */
    public function add_mimes($file_types)
    {
        $file_types['json'] = 'application/json';
        return $file_types;
    }

    /**
     * get the pre_header template part with a fallback for the old path.
     * 
     * /template-parts/globals/pre_header_content
     * 
     * @author Itai Dotan
     */
    static public function get_pre_header_part()
    {
        if (get_template_part('/template-parts/globals/pre_header_content') !== false) {
            return;
        }
        get_template_part('/template-parts/header/pre_header_content');
    }

    /**
     * get the header template part with a fallback for the old path
     * 
     * /template-parts/globals/header_content
     * 
     * @author Itai Dotan
     */
    static public function get_header_part()
    {
        if (get_template_part('/template-parts/globals/header_content') !== false) {
            return;
        }
        get_template_part('/template-parts/header/header_content');
    }

    /**
     * get the footer template part with a fallback for the old path
     * 
     * /template-parts/globals/footer_contents
     * 
     * @author Itai Dotan
     */
    static public function get_footer_part()
    {
        if (get_template_part('/template-parts/globals/footer_content') !== false) {
            return;
        }
        get_template_part('/template-parts/footer/footer_content');
    }

    /**
     * get the roles allowerd to see the guides
     * 
     * @author Itai Dotan
     */
    static public function get_guides_roles()
    {
        return self::$guides_allowerd_roles;
    }
}

new Lukio_Theme_setup();

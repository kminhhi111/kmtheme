<?php
/**
 * Plugin Name: KMTheme Hero Slider
 * Plugin URI: https://keymmedia.vn
 * Description: Hero Slider verwalten
 * Version: 1.0.0
 * Author: kminhhi__
 * Author URI: https://keymmedia.vn
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kmtheme-hero-slider
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KMTHEME_HERO_SLIDER_VERSION', '1.0.0');
define('KMTHEME_HERO_SLIDER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KMTHEME_HERO_SLIDER_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
class KMTheme_Hero_Slider {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register Custom Post Type
        add_action('init', array($this, 'register_hero_slider_cpt'));
        
        // Register shortcode
        add_shortcode('hero_slider', array($this, 'hero_slider_shortcode'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add shortcode meta box
        add_action('add_meta_boxes', array($this, 'add_shortcode_meta_box'));
    }
    
    /**
     * Register Hero Slider Custom Post Type
     */
    public function register_hero_slider_cpt() {
        $labels = array(
            'name'               => 'Hero Sliders',
            'singular_name'      => 'Hero Slider',
            'add_new'            => 'Thêm Slider',
            'add_new_item'       => 'Thêm Hero Slider mới',
            'edit_item'          => 'Sửa Hero Slider',
            'new_item'           => 'Hero Slider mới',
            'view_item'          => 'Xem Hero Slider',
            'search_items'       => 'Tìm Hero Slider',
            'not_found'          => 'Không tìm thấy',
            'not_found_in_trash' => 'Không có trong thùng rác',
            'menu_name'          => 'Hero Sliders',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-images-alt2',
            'supports'           => array('title'),
        );

        register_post_type('hero_slider', $args);
    }
    
    /**
     * Hero Slider Shortcode
     */
    public function hero_slider_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
        ), $atts);

        $post_id = (int) $atts['id'];
        if (!$post_id) {
            return '';
        }

        if (!function_exists('get_field')) {
            return '<!-- Cần cài ACF để dùng hero_slider -->';
        }

        $slides = get_field('slides', $post_id);
        if (empty($slides) || !is_array($slides)) {
            return '<!-- Chưa có slide nào trong hero_slider ID ' . $post_id . ' -->';
        }

        ob_start();
        ?>

        <div class="hero-slider">
            <div class="hero-slides">
                <?php
                $i = 0;
                foreach ($slides as $slide) :
                    $title       = isset($slide['slide_title']) ? $slide['slide_title'] : '';
                    $content     = isset($slide['slide_content']) ? $slide['slide_content'] : '';
                    $link_text   = isset($slide['slide_link_text']) ? $slide['slide_link_text'] : '';
                    $link_url    = isset($slide['slide_link_url']) ? $slide['slide_link_url'] : '';
                    $image_field = isset($slide['slide_image']) ? $slide['slide_image'] : '';
                    
                    // Nếu image field trả về array
                    if (is_array($image_field) && isset($image_field['url'])) {
                        $image_url = $image_field['url'];
                    } else {
                        $image_url = $image_field;
                    }

                    $active_class = $i === 0 ? ' is-active' : '';
                ?>
                    <div class="hero-slide<?php echo $active_class; ?>">
                        <div class="hero-slide-inner">
                            <div class="hero-left">
                                <?php if ($title) : ?>
                                    <h1><?php echo esc_html($title); ?></h1>
                                <?php endif; ?>

                                <?php if ($content) : ?>
                                    <p><?php echo wp_kses_post($content); ?></p>
                                <?php endif; ?>

                                <?php if ($link_text && $link_url) : ?>
                                    <a href="<?php echo esc_url($link_url); ?>" class="hero-link">
                                        <?php echo esc_html($link_text); ?>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="hero-right">
                                <?php if ($image_url) : 
                                    $image_alt = '';
                                    if (is_array($image_field) && isset($image_field['alt'])) {
                                        $image_alt = $image_field['alt'];
                                    } elseif (is_array($image_field) && isset($image_field['title'])) {
                                        $image_alt = $image_field['title'];
                                    }
                                ?>
                                    <img src="<?php echo esc_url($image_url); ?>" 
                                         alt="<?php echo esc_attr($image_alt); ?>" 
                                         loading="lazy">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php
                    $i++;
                endforeach;
                ?>
            </div>

        </div>

        <?php
        return ob_get_clean();
    }
    
    /**
     * Add Shortcode Meta Box
     */
    public function add_shortcode_meta_box() {
        add_meta_box(
            'hero_slider_shortcode_box',
            'Shortcode',
            array($this, 'shortcode_meta_box_callback'),
            'hero_slider',
            'side',
            'high'
        );
    }
    
    /**
     * Shortcode Meta Box Callback
     */
    public function shortcode_meta_box_callback($post) {
        $shortcode = '[hero_slider id="' . $post->ID . '"]';
        ?>
        <div style="padding: 10px 0;">
            <p><strong>Shortcode:</strong></p>
            <input type="text" readonly value="<?php echo esc_attr($shortcode); ?>" 
                   style="width: 100%; padding: 8px; font-family: monospace; background: #f5f5f5; border: 1px solid #ddd;" 
                   onclick="this.select();">
            <p style="margin-top: 10px;">
                <button type="button" class="button button-primary" 
                        onclick="document.querySelector('input[value=&quot;<?php echo esc_js($shortcode); ?>&quot;]').select(); document.execCommand('copy'); alert('Shortcode kopiert!');">
                    Kopieren
                </button>
            </p>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets() {
        // Enqueue CSS
        wp_enqueue_style(
            'kmtheme-hero-slider-css',
            KMTHEME_HERO_SLIDER_PLUGIN_URL . 'assets/css/kmtheme-hero-slider.css',
            array(),
            KMTHEME_HERO_SLIDER_VERSION,
            'all'
        );
        
        // Enqueue JS
        $js_dependencies = array('jquery');
        
        // Thêm GSAP dependencies nếu có
        if (wp_script_is('gsap', 'registered')) {
            $js_dependencies[] = 'gsap';
        }
        if (wp_script_is('gsap-scrolltrigger', 'registered')) {
            $js_dependencies[] = 'gsap-scrolltrigger';
        }
        
        wp_enqueue_script(
            'kmtheme-hero-slider-js',
            KMTHEME_HERO_SLIDER_PLUGIN_URL . 'assets/js/kmtheme-hero-slider.js',
            $js_dependencies,
            KMTHEME_HERO_SLIDER_VERSION,
            true
        );
    }
}

// Initialize plugin
function kmtheme_hero_slider_init() {
    return KMTheme_Hero_Slider::get_instance();
}

// Start the plugin
kmtheme_hero_slider_init();


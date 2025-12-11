<?php
/**
 * Plugin Name: KMTheme Gallery
 * Plugin URI: https://keymmedia.vn
 * Description: Galerie verwalten
 * Version: 1.0.0
 * Author: kminhhi__
 * Author URI: https://keymmedia.vn
 * License: GPL v2 or later
 * Text Domain: kmtheme-gallery
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KMTHEME_GALLERY_VERSION', '1.0.0');
define('KMTHEME_GALLERY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KMTHEME_GALLERY_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
class KMTheme_Gallery {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_gallery_cpt'));
        add_action('init', array($this, 'register_gallery_taxonomy'));
        add_shortcode('kmtheme_gallery', array($this, 'gallery_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('add_meta_boxes', array($this, 'add_gallery_meta_box'));
        add_action('add_meta_boxes', array($this, 'add_shortcode_meta_box'));
        add_action('save_post', array($this, 'save_gallery_images'));
    }
    
    /**
     * Register Gallery Custom Post Type
     */
    public function register_gallery_cpt() {
        $labels = array(
            'name'                  => 'Galleries',
            'singular_name'         => 'Gallery',
            'menu_name'             => 'Galleries',
            'name_admin_bar'        => 'Gallery',
            'add_new'               => 'Thêm mới',
            'add_new_item'          => 'Thêm Gallery mới',
            'new_item'              => 'Gallery mới',
            'edit_item'             => 'Chỉnh sửa Gallery',
            'view_item'             => 'Xem Gallery',
            'all_items'             => 'Tất cả Galleries',
            'search_items'          => 'Tìm kiếm Gallery',
            'not_found'             => 'Không tìm thấy Gallery',
            'not_found_in_trash'    => 'Không tìm thấy Gallery trong thùng rác',
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'gallery'),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-format-gallery',
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt'),
        );
        
        register_post_type('gallery', $args);
    }
    
    /**
     * Register Gallery Category Taxonomy
     */
    public function register_gallery_taxonomy() {
        $labels = array(
            'name'              => 'Danh mục Gallery',
            'singular_name'     => 'Danh mục Gallery',
            'search_items'      => 'Tìm kiếm danh mục',
            'all_items'         => 'Tất cả danh mục',
            'parent_item'       => 'Danh mục cha',
            'parent_item_colon' => 'Danh mục cha:',
            'edit_item'         => 'Chỉnh sửa danh mục',
            'update_item'       => 'Cập nhật danh mục',
            'add_new_item'      => 'Thêm danh mục mới',
            'new_item_name'     => 'Tên danh mục mới',
            'menu_name'         => 'Danh mục',
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'gallery-category'),
        );
        
        register_taxonomy('gallery_category', array('gallery'), $args);
    }
    
    /**
     * Add Gallery Images Meta Box
     */
    public function add_gallery_meta_box() {
        add_meta_box(
            'gallery_images_meta_box',
            'Gallery Images',
            array($this, 'gallery_images_meta_box_callback'),
            'gallery',
            'normal',
            'high'
        );
    }
    
    /**
     * Add Shortcode Meta Box
     */
    public function add_shortcode_meta_box() {
        add_meta_box(
            'gallery_shortcode_box',
            'Shortcode',
            array($this, 'shortcode_meta_box_callback'),
            'gallery',
            'side',
            'high'
        );
    }
    
    /**
     * Shortcode Meta Box Callback
     */
    public function shortcode_meta_box_callback($post) {
        $shortcode = '[kmtheme_gallery id="' . $post->ID . '"]';
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
     * Gallery Images Meta Box Callback
     */
    public function gallery_images_meta_box_callback($post) {
        wp_nonce_field('gallery_images_meta_box', 'gallery_images_meta_box_nonce');
        
        $gallery_images = get_post_meta($post->ID, '_gallery_images', true);
        $image_ids = $gallery_images ? explode(',', $gallery_images) : array();
        
        ?>
        <div class="gallery-images-container">
            <p>
                <button type="button" class="button button-primary" id="gallery-add-images">
                    Thêm ảnh vào Gallery
                </button>
                <button type="button" class="button" id="gallery-remove-all">
                    Xóa tất cả
                </button>
            </p>
            <input type="hidden" id="gallery-images-input" name="gallery_images" value="<?php echo esc_attr($gallery_images); ?>">
            <div id="gallery-images-preview" class="gallery-images-preview">
                <?php
                if (!empty($image_ids)) {
                    foreach ($image_ids as $image_id) {
                        if ($image_id) {
                            $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                            if ($image_url) {
                                ?>
                                <div class="gallery-image-item" data-image-id="<?php echo esc_attr($image_id); ?>">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="">
                                    <button type="button" class="gallery-remove-image" data-image-id="<?php echo esc_attr($image_id); ?>"><i class="fas fa-times"></i></button>
                                </div>
                                <?php
                            }
                        }
                    }
                }
                ?>
            </div>
        </div>
        <style>
        .gallery-images-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .gallery-image-item {
            position: relative;
            width: 150px;
            height: 150px;
            border: 2px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .gallery-image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .gallery-remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.8);
            color: white;
            border: none;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .gallery-remove-image:hover {
            background: rgba(255, 0, 0, 1);
        }
        .gallery-remove-image i {
            font-size: 14px;
        }
        </style>
        <script>
        jQuery(document).ready(function($) {
            var frame;
            
            $('#gallery-add-images').on('click', function(e) {
                e.preventDefault();
                
                if (frame) {
                    frame.open();
                    return;
                }
                
                frame = wp.media({
                    title: 'Chọn ảnh cho Gallery',
                    button: {
                        text: 'Thêm vào Gallery'
                    },
                    multiple: true,
                    library: {
                        type: 'image'
                    }
                });
                
                frame.on('select', function() {
                    var selection = frame.state().get('selection');
                    var imageIds = $('#gallery-images-input').val();
                    var ids = imageIds ? imageIds.split(',') : [];
                    
                    selection.each(function(attachment) {
                        var id = attachment.id;
                        if (ids.indexOf(id.toString()) === -1) {
                            ids.push(id);
                            var url = attachment.attributes.sizes && attachment.attributes.sizes.thumbnail ? 
                                     attachment.attributes.sizes.thumbnail.url : 
                                     attachment.attributes.url;
                            
                            $('#gallery-images-preview').append(
                                '<div class="gallery-image-item" data-image-id="' + id + '">' +
                                '<img src="' + url + '" alt="">' +
                                '<button type="button" class="gallery-remove-image" data-image-id="' + id + '"><i class="fas fa-times"></i></button>' +
                                '</div>'
                            );
                        }
                    });
                    
                    $('#gallery-images-input').val(ids.join(','));
                });
                
                frame.open();
            });
            
            $(document).on('click', '.gallery-remove-image', function() {
                var imageId = $(this).data('image-id');
                $(this).closest('.gallery-image-item').remove();
                
                var imageIds = $('#gallery-images-input').val();
                var ids = imageIds ? imageIds.split(',') : [];
                ids = ids.filter(function(id) {
                    return id != imageId;
                });
                $('#gallery-images-input').val(ids.join(','));
            });
            
            $('#gallery-remove-all').on('click', function() {
                if (confirm('Bạn có chắc chắn muốn xóa tất cả ảnh?')) {
                    $('#gallery-images-preview').empty();
                    $('#gallery-images-input').val('');
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save Gallery Images
     */
    public function save_gallery_images($post_id) {
        // Check nonce
        if (!isset($_POST['gallery_images_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['gallery_images_meta_box_nonce'], 'gallery_images_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save gallery images
        if (isset($_POST['gallery_images'])) {
            update_post_meta($post_id, '_gallery_images', sanitize_text_field($_POST['gallery_images']));
        } else {
            delete_post_meta($post_id, '_gallery_images');
        }
    }
    
    /**
     * Gallery Shortcode
     */
    public function gallery_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'category' => '',
            'columns' => '3',
            'limit' => '-1',
            'layout' => 'grid', // grid, masonry, carousel
        ), $atts);
        
        $args = array(
            'post_type' => 'gallery',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
        );
        
        if (!empty($atts['id'])) {
            $args['p'] = intval($atts['id']);
        }
        
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'gallery_category',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                ),
            );
        }
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<p>Không có ảnh nào trong gallery.</p>';
        }
        
        ob_start();
        ?>
        <div class="kmtheme-gallery kmtheme-gallery-<?php echo esc_attr($atts['layout']); ?>" data-columns="<?php echo esc_attr($atts['columns']); ?>">
            <div class="gallery-grid">
                <?php
                while ($query->have_posts()) {
                    $query->the_post();
                    $gallery_images = get_post_meta(get_the_ID(), '_gallery_images', true);
                    $image_ids = $gallery_images ? explode(',', $gallery_images) : array();
                    
                    if (!empty($image_ids)) {
                        foreach ($image_ids as $image_id) {
                            if ($image_id) {
                                $image_url = wp_get_attachment_image_url($image_id, 'large');
                                $image_full = wp_get_attachment_image_url($image_id, 'full');
                                $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                                
                                if ($image_url) {
                                    ?>
                                    <div class="gallery-item">
                                        <a href="<?php echo esc_url($image_full); ?>" class="gallery-link" data-lightbox="gallery-<?php echo get_the_ID(); ?>">
                                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt ? $image_alt : get_the_title()); ?>" loading="lazy">
                                            <div class="gallery-overlay">
                                                <i class="fas fa-search gallery-icon"></i>
                                            </div>
                                        </a>
                                    </div>
                                    <?php
                                }
                            }
                        }
                    }
                }
                wp_reset_postdata();
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue Assets
     */
    public function enqueue_assets() {
        // Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
        
        // CSS
        wp_enqueue_style(
            'kmtheme-gallery-css',
            KMTHEME_GALLERY_PLUGIN_URL . 'assets/css/kmtheme-gallery.css',
            array('font-awesome'),
            KMTHEME_GALLERY_VERSION
        );
        
        // JS
        wp_enqueue_script(
            'kmtheme-gallery-js',
            KMTHEME_GALLERY_PLUGIN_URL . 'assets/js/kmtheme-gallery.js',
            array('jquery'),
            KMTHEME_GALLERY_VERSION,
            true
        );
    }
}

// Initialize plugin
new KMTheme_Gallery();


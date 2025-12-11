<?php
/**
 * Plugin Name: KMTheme Kontaktformular
 * Plugin URI: https://keymmedia.vn
 * Description: Kontaktformular verwalten
 * Version: 1.0.0
 * Author: kminhhi__
 * Author URI: https://keymmedia.vn
 * License: GPL v2 or later
 * Text Domain: kmtheme-contact-form
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KMTHEME_CF_VERSION', '1.0.0');
define('KMTHEME_CF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KMTHEME_CF_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
class KMTheme_Contact_Form {
    
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'kmtheme_contact_submissions';
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('kmtheme_contact_form', array($this, 'contact_form_shortcode'));
        add_action('wp_ajax_kmtheme_submit_contact_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_kmtheme_submit_contact_form', array($this, 'handle_form_submission'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Plugin Activation
     */
    public function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            telefon varchar(50) NOT NULL,
            nachricht text NOT NULL,
            ip_address varchar(45) NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Set default email content
        if (!get_option('kmtheme_cf_email_subject')) {
            update_option('kmtheme_cf_email_subject', 'Vielen Dank für Ihre Kontaktaufnahme');
        }
        if (!get_option('kmtheme_cf_email_content')) {
            update_option('kmtheme_cf_email_content', 'Vielen Dank, dass Sie sich mit uns in Verbindung gesetzt haben. Wir werden Ihre Nachricht so schnell wie möglich bearbeiten.');
        }
    }
    
    /**
     * Plugin Deactivation
     */
    public function deactivate() {
        // Clean up if needed
    }
    
    /**
     * Initialize
     */
    public function init() {
        load_plugin_textdomain('kmtheme-contact-form', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Add Admin Menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Kontaktformular',
            'Kontaktformular',
            'manage_options',
            'kmtheme-contact-form',
            array($this, 'admin_page_submissions'),
            'dashicons-email-alt',
            30
        );
        
        add_submenu_page(
            'kmtheme-contact-form',
            'Daten',
            'Daten',
            'manage_options',
            'kmtheme-contact-form',
            array($this, 'admin_page_submissions')
        );
        
        add_submenu_page(
            'kmtheme-contact-form',
            'E-Mail-Einstellungen',
            'E-Mail-Einstellungen',
            'manage_options',
            'kmtheme-contact-form-email',
            array($this, 'admin_page_email')
        );
        
        add_submenu_page(
            'kmtheme-contact-form',
            'reCAPTCHA',
            'reCAPTCHA',
            'manage_options',
            'kmtheme-contact-form-recaptcha',
            array($this, 'admin_page_recaptcha')
        );
    }
    
    /**
     * Register Settings
     */
    public function register_settings() {
        register_setting('kmtheme_cf_email_settings', 'kmtheme_cf_email_subject');
        register_setting('kmtheme_cf_email_settings', 'kmtheme_cf_email_content');
        register_setting('kmtheme_cf_recaptcha_settings', 'kmtheme_cf_recaptcha_site_key');
        register_setting('kmtheme_cf_recaptcha_settings', 'kmtheme_cf_recaptcha_secret_key');
    }
    
    /**
     * Admin Page - Submissions
     */
    public function admin_page_submissions() {
        global $wpdb;
        
        // Handle delete
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            check_admin_referer('delete_submission_' . $_GET['id']);
            $wpdb->delete($this->table_name, array('id' => intval($_GET['id'])), array('%d'));
            echo '<div class="notice notice-success"><p>Eintrag gelöscht.</p></div>';
        }
        
        // Get submissions
        $submissions = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY submitted_at DESC");
        
        ?>
        <div class="wrap">
            <h1>Kontaktformular Daten</h1>
            
            <div class="postbox" style="margin-bottom: 20px;">
                <div class="inside">
                    <h3>Shortcode</h3>
                    <p>Verwenden Sie diesen Shortcode, um das Kontaktformular anzuzeigen:</p>
                    <input type="text" readonly value="[kmtheme_contact_form]" style="width: 100%; padding: 10px; font-family: monospace; background: #f5f5f5; border: 1px solid #ddd;" onclick="this.select();">
                    <p><button type="button" class="button" onclick="document.querySelector('input[value=&quot;[kmtheme_contact_form]&quot;]').select(); document.execCommand('copy'); alert('Shortcode kopiert!');">Kopieren</button></p>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Telefon</th>
                        <th>Nachricht</th>
                        <th>IP-Adresse</th>
                        <th>Datum</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="7">Keine Einträge gefunden.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?php echo esc_html($submission->name); ?></td>
                                <td><?php echo esc_html($submission->email); ?></td>
                                <td><?php echo esc_html($submission->telefon); ?></td>
                                <td><?php echo esc_html(wp_trim_words($submission->nachricht, 20)); ?></td>
                                <td><?php echo esc_html($submission->ip_address); ?></td>
                                <td><?php echo esc_html($submission->submitted_at); ?></td>
                                <td>
                                    <a href="?page=kmtheme-contact-form&action=view&id=<?php echo $submission->id; ?>" class="button button-small">Anzeigen</a>
                                    <a href="<?php echo wp_nonce_url('?page=kmtheme-contact-form&action=delete&id=' . $submission->id, 'delete_submission_' . $submission->id); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?');">Löschen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])): ?>
                <?php
                $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", intval($_GET['id'])));
                if ($submission):
                ?>
                    <div class="postbox" style="margin-top: 20px;">
                        <div class="inside">
                            <h3>Details</h3>
                            <p><strong>Name:</strong> <?php echo esc_html($submission->name); ?></p>
                            <p><strong>E-Mail:</strong> <?php echo esc_html($submission->email); ?></p>
                            <p><strong>Telefon:</strong> <?php echo esc_html($submission->telefon); ?></p>
                            <p><strong>Nachricht:</strong></p>
                            <p><?php echo nl2br(esc_html($submission->nachricht)); ?></p>
                            <p><strong>IP-Adresse:</strong> <?php echo esc_html($submission->ip_address); ?></p>
                            <p><strong>Datum:</strong> <?php echo esc_html($submission->submitted_at); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Admin Page - Email Settings
     */
    public function admin_page_email() {
        if (isset($_POST['submit'])) {
            check_admin_referer('kmtheme_cf_email_settings');
            update_option('kmtheme_cf_email_subject', sanitize_text_field($_POST['kmtheme_cf_email_subject']));
            update_option('kmtheme_cf_email_content', wp_kses_post($_POST['kmtheme_cf_email_content']));
            echo '<div class="notice notice-success"><p>Einstellungen gespeichert.</p></div>';
        }
        
        $email_subject = get_option('kmtheme_cf_email_subject', 'Vielen Dank für Ihre Kontaktaufnahme');
        $email_content = get_option('kmtheme_cf_email_content', 'Vielen Dank, dass Sie sich mit uns in Verbindung gesetzt haben. Wir werden Ihre Nachricht so schnell wie möglich bearbeiten.');
        
        ?>
        <div class="wrap">
            <h1>E-Mail-Einstellungen</h1>
            <form method="post" action="">
                <?php wp_nonce_field('kmtheme_cf_email_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kmtheme_cf_email_subject">E-Mail-Betreff</label>
                        </th>
                        <td>
                            <input type="text" id="kmtheme_cf_email_subject" name="kmtheme_cf_email_subject" value="<?php echo esc_attr($email_subject); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kmtheme_cf_email_content">E-Mail-Inhalt</label>
                        </th>
                        <td>
                            <?php
                            wp_editor($email_content, 'kmtheme_cf_email_content', array(
                                'textarea_name' => 'kmtheme_cf_email_content',
                                'textarea_rows' => 10,
                                'media_buttons' => false,
                            ));
                            ?>
                            <p class="description">Dieser Inhalt wird an Kunden gesendet, die das Kontaktformular ausfüllen.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Speichern">
                </p>
            </form>
            
            <div class="postbox" style="margin-top: 20px;">
                <div class="inside">
                    <h3>E-Mail-Vorschau</h3>
                    <div style="border: 1px solid #ddd; padding: 20px; background: #fff;">
                        <?php echo $this->get_email_template($email_subject, $email_content, 'Max Mustermann', 'max@example.com', '0123456789', 'Testnachricht'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Admin Page - reCAPTCHA Settings
     */
    public function admin_page_recaptcha() {
        if (isset($_POST['submit'])) {
            check_admin_referer('kmtheme_cf_recaptcha_settings');
            update_option('kmtheme_cf_recaptcha_site_key', sanitize_text_field($_POST['kmtheme_cf_recaptcha_site_key']));
            update_option('kmtheme_cf_recaptcha_secret_key', sanitize_text_field($_POST['kmtheme_cf_recaptcha_secret_key']));
            echo '<div class="notice notice-success"><p>Einstellungen gespeichert.</p></div>';
        }
        
        $site_key = get_option('kmtheme_cf_recaptcha_site_key', '');
        $secret_key = get_option('kmtheme_cf_recaptcha_secret_key', '');
        
        ?>
        <div class="wrap">
            <h1>reCAPTCHA v2 Einstellungen</h1>
            <p>Das Formular funktioniert auch ohne reCAPTCHA-Schlüssel. Wenn Sie reCAPTCHA verwenden möchten, erhalten Sie die Schlüssel von <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA</a>.</p>
            <form method="post" action="">
                <?php wp_nonce_field('kmtheme_cf_recaptcha_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="kmtheme_cf_recaptcha_site_key">Site Key</label>
                        </th>
                        <td>
                            <input type="text" id="kmtheme_cf_recaptcha_site_key" name="kmtheme_cf_recaptcha_site_key" value="<?php echo esc_attr($site_key); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="kmtheme_cf_recaptcha_secret_key">Secret Key</label>
                        </th>
                        <td>
                            <input type="text" id="kmtheme_cf_recaptcha_secret_key" name="kmtheme_cf_recaptcha_secret_key" value="<?php echo esc_attr($secret_key); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Speichern">
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Contact Form Shortcode
     */
    public function contact_form_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts);
        
        ob_start();
        ?>
        <div class="kmtheme-contact-form-wrapper">
            <form id="kmtheme-contact-form" class="kmtheme-contact-form">
                <div class="form-field">
                    <input type="text" name="name" id="cf-name" placeholder="Name" required>
                </div>
                <div class="form-field">
                    <input type="email" name="email" id="cf-email" placeholder="E-Mail" required>
                </div>
                <div class="form-field">
                    <input type="tel" name="telefon" id="cf-telefon" placeholder="Telefonnummer" required>
                </div>
                <div class="form-field">
                    <textarea name="nachricht" id="cf-nachricht" placeholder="Nachricht" rows="5" required></textarea>
                </div>
                <?php
                $recaptcha_site_key = get_option('kmtheme_cf_recaptcha_site_key', '');
                if (!empty($recaptcha_site_key)):
                ?>
                    <div class="form-field">
                        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_site_key); ?>"></div>
                    </div>
                <?php endif; ?>
                <div class="form-field">
                    <button type="submit" class="form-submit-btn">
                        <span class="btn-text">Senden</span>
                        <span class="btn-loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Wird gesendet...
                        </span>
                    </button>
                </div>
                <div class="form-message" style="display: none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle Form Submission
     */
    public function handle_form_submission() {
        check_ajax_referer('kmtheme_contact_form_nonce', 'nonce');
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $telefon = sanitize_text_field($_POST['telefon'] ?? '');
        $nachricht = sanitize_textarea_field($_POST['nachricht'] ?? '');
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        
        // Validation
        if (empty($name) || empty($email) || empty($telefon) || empty($nachricht)) {
            wp_send_json_error(array('message' => 'Bitte füllen Sie alle Felder aus.'));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.'));
        }
        
        // Verify reCAPTCHA if key is set
        $recaptcha_secret_key = get_option('kmtheme_cf_recaptcha_secret_key', '');
        if (!empty($recaptcha_secret_key) && !empty($recaptcha_response)) {
            $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
            $response = wp_remote_post($verify_url, array(
                'body' => array(
                    'secret' => $recaptcha_secret_key,
                    'response' => $recaptcha_response,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                )
            ));
            
            if (is_wp_error($response)) {
                wp_send_json_error(array('message' => 'Fehler bei der reCAPTCHA-Überprüfung.'));
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!$body['success']) {
                wp_send_json_error(array('message' => 'reCAPTCHA-Überprüfung fehlgeschlagen.'));
            }
        }
        
        // Save to database
        global $wpdb;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'name' => $name,
                'email' => $email,
                'telefon' => $telefon,
                'nachricht' => $nachricht,
                'ip_address' => $ip_address,
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Fehler beim Speichern der Nachricht.'));
        }
        
        // Send thank you email
        $this->send_thank_you_email($email, $name, $telefon, $nachricht);
        
        wp_send_json_success(array('message' => 'Vielen Dank für Ihre Nachricht! Wir werden uns so schnell wie möglich bei Ihnen melden.'));
    }
    
    /**
     * Send Thank You Email
     */
    private function send_thank_you_email($email, $name, $telefon, $nachricht) {
        $subject = get_option('kmtheme_cf_email_subject', 'Vielen Dank für Ihre Kontaktaufnahme');
        $content = get_option('kmtheme_cf_email_content', 'Vielen Dank, dass Sie sich mit uns in Verbindung gesetzt haben. Wir werden Ihre Nachricht so schnell wie möglich bearbeiten.');
        
        $email_body = $this->get_email_template($subject, $content, $name, $email, $telefon, $nachricht);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($email, $subject, $email_body, $headers);
    }
    
    /**
     * Get Email Template
     */
    private function get_email_template($subject, $content, $name, $email, $telefon, $nachricht) {
        $header = 'Kairo sushi In Eitorf';
        $footer = 'Kairo Restaurant<br>
Bahnhofstraße 6,53783 Eitorf<br>
02243 8457588<br>
Montag–Freitag: 11:30 – 22:00 | Samstag, Sonntag & Feiertage: 12:00 – 22:00';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .email-container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .email-header { background: #F8EFDE; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; color: #333; }
                .email-content { padding: 30px 20px; background: #fff; }
                .email-footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 14px; color: #666; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">' . esc_html($header) . '</div>
                <div class="email-content">
                    <p>Hallo ' . esc_html($name) . ',</p>
                    ' . wpautop($content) . '
                </div>
                <div class="email-footer">' . $footer . '</div>
            </div>
        </body>
        </html>';
        
        return $html;
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
            'kmtheme-contact-form-css',
            KMTHEME_CF_PLUGIN_URL . 'assets/css/kmtheme-contact-form.css',
            array('font-awesome'),
            KMTHEME_CF_VERSION
        );
        
        // JS
        wp_enqueue_script(
            'kmtheme-contact-form-js',
            KMTHEME_CF_PLUGIN_URL . 'assets/js/kmtheme-contact-form.js',
            array('jquery'),
            KMTHEME_CF_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('kmtheme-contact-form-js', 'kmthemeCF', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kmtheme_contact_form_nonce'),
        ));
        
        // reCAPTCHA script
        $recaptcha_site_key = get_option('kmtheme_cf_recaptcha_site_key', '');
        if (!empty($recaptcha_site_key)) {
            wp_enqueue_script(
                'google-recaptcha',
                'https://www.google.com/recaptcha/api.js',
                array(),
                null,
                true
            );
        }
    }
}

// Initialize plugin
new KMTheme_Contact_Form();


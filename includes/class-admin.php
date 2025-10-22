<?php
/**
 * Admin functionality for Cloudflare Responsive Images plugin
 *
 * @package CloudflareResponsiveImages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class CFRI_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
    }
    
    /**
     * Add admin menu
     */
    public function addAdminMenu() {
        add_options_page(
            __('Cloudflare Responsive Images', 'cloudflare-responsive-images'),
            __('Cloudflare Images', 'cloudflare-responsive-images'),
            'manage_options',
            'cloudflare-responsive-images',
            array($this, 'adminPage')
        );
    }
    
    /**
     * Register settings
     */
    public function registerSettings() {
        register_setting('cfri_options', 'cfri_options', array($this, 'sanitizeOptions'));
        
        // General settings section
        add_settings_section(
            'cfri_general',
            __('General Settings', 'cloudflare-responsive-images'),
            array($this, 'generalSectionCallback'),
            'cloudflare-responsive-images'
        );
        
        
        add_settings_field(
            'enable_transform',
            __('Enable Cloudflare Transform', 'cloudflare-responsive-images'),
            array($this, 'enableTransformCallback'),
            'cloudflare-responsive-images',
            'cfri_general'
        );
        
        add_settings_field(
            'disable_image_sizes',
            __('Disable WordPress Image Sizes', 'cloudflare-responsive-images'),
            array($this, 'disableImageSizesCallback'),
            'cloudflare-responsive-images',
            'cfri_general'
        );
        
        // Transform settings section
        add_settings_section(
            'cfri_transform',
            __('Transform Settings', 'cloudflare-responsive-images'),
            array($this, 'transformSectionCallback'),
            'cloudflare-responsive-images'
        );
        
        add_settings_field(
            'quality',
            __('Image Quality', 'cloudflare-responsive-images'),
            array($this, 'qualityCallback'),
            'cloudflare-responsive-images',
            'cfri_transform'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueueAdminScripts($hook) {
        if ($hook !== 'settings_page_cloudflare-responsive-images') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'cfri-admin',
            CFRI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            CFRI_VERSION,
            true
        );
        
        wp_enqueue_style(
            'cfri-admin',
            CFRI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CFRI_VERSION
        );
        
        // Add nonce for AJAX requests
        wp_localize_script('cfri-admin', 'cfri_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cfri_admin_nonce')
        ));
    }
    
    /**
     * Admin page
     */
    public function adminPage() {
        $options = get_option('cfri_options', array());
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="cfri-admin-header">
                <p><?php _e('Configure Cloudflare Transform settings for responsive images. This plugin disables WordPress image size generation and uses Cloudflare Transform instead.', 'cloudflare-responsive-images'); ?></p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('cfri_options');
                do_settings_sections('cloudflare-responsive-images');
                submit_button();
                ?>
            </form>
            
            <div class="cfri-info-box">
                <h3><?php _e('How it works:', 'cloudflare-responsive-images'); ?></h3>
                <ol>
                    <li><?php _e('Disables WordPress automatic image size generation to save storage space', 'cloudflare-responsive-images'); ?></li>
                    <li><?php _e('Uses Cloudflare Transform to generate responsive images on-demand', 'cloudflare-responsive-images'); ?></li>
                    <li><?php _e('Automatically adds Cloudflare Transform URLs to all image sources', 'cloudflare-responsive-images'); ?></li>
                    <li><?php _e('Supports WebP and AVIF formats for better compression', 'cloudflare-responsive-images'); ?></li>
                </ol>
                
                <h3><?php _e('Cloudflare Setup:', 'cloudflare-responsive-images'); ?></h3>
                <p><?php _e('Make sure your site is using Cloudflare and that Transform is enabled in your Cloudflare dashboard.', 'cloudflare-responsive-images'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Sanitize options
     */
    public function sanitizeOptions($input) {
        $sanitized = array();
        
        $sanitized['enable_transform'] = isset($input['enable_transform']) ? (bool) $input['enable_transform'] : false;
        $sanitized['disable_image_sizes'] = isset($input['disable_image_sizes']) ? (bool) $input['disable_image_sizes'] : false;
        $sanitized['quality'] = absint($input['quality']);
        
        return $sanitized;
    }
    
    /**
     * General section callback
     */
    public function generalSectionCallback() {
        echo '<p>' . __('Configure basic settings for the plugin.', 'cloudflare-responsive-images') . '</p>';
    }
    
    /**
     * Transform section callback
     */
    public function transformSectionCallback() {
        echo '<p>' . __('Configure Cloudflare Transform parameters.', 'cloudflare-responsive-images') . '</p>';
    }
    
    
    
    
    
    /**
     * Enable transform callback
     */
    public function enableTransformCallback() {
        $options = get_option('cfri_options', array());
        $value = isset($options['enable_transform']) ? $options['enable_transform'] : true;
        ?>
        <label>
            <input type="checkbox" id="enable_transform" name="cfri_options[enable_transform]" value="1" <?php checked($value); ?> />
            <?php _e('Enable Cloudflare Transform for images', 'cloudflare-responsive-images'); ?>
        </label>
        <?php
    }
    
    /**
     * Disable image sizes callback
     */
    public function disableImageSizesCallback() {
        $options = get_option('cfri_options', array());
        $value = isset($options['disable_image_sizes']) ? $options['disable_image_sizes'] : true;
        ?>
        <label>
            <input type="checkbox" id="disable_image_sizes" name="cfri_options[disable_image_sizes]" value="1" <?php checked($value); ?> />
            <?php _e('Disable WordPress image size generation', 'cloudflare-responsive-images'); ?>
        </label>
        <p class="description"><?php _e('This will prevent WordPress from generating multiple image sizes and save storage space.', 'cloudflare-responsive-images'); ?></p>
        <?php
    }
    
    /**
     * Quality callback
     */
    public function qualityCallback() {
        $options = get_option('cfri_options', array());
        $value = isset($options['quality']) ? $options['quality'] : 85;
        ?>
        <div class="cfri-transform-settings">
            <input type="number" id="quality" name="cfri_options[quality]" value="<?php echo esc_attr($value); ?>" min="1" max="100" />
            <p class="description"><?php _e('Image quality (1-100)', 'cloudflare-responsive-images'); ?></p>
        </div>
        <?php
    }
    
    
    
    
    
}

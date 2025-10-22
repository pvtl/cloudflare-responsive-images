<?php
/**
 * Plugin Name: Cloudflare Responsive Images
 * Plugin URI: https://github.com/your-username/cloudflare-responsive-images
 * Description: Disables WordPress image variants and uses Cloudflare Transform for responsive images.
 * Version: 1.0.0
 * Author: Pivotal Agency
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cloudflare-responsive-images
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CFRI_PLUGIN_FILE', __FILE__);
define('CFRI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CFRI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CFRI_VERSION', '1.0.0');

/**
 * Main plugin class
 */
class CloudflareResponsiveImages {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Plugin options
     */
    private $options = array();
    
    /**
     * Get plugin instance
     */
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Load plugin textdomain
        add_action('plugins_loaded', array($this, 'loadTextdomain'));
        
        // Load plugin options
        $this->loadOptions();
        
        // Initialize hooks
        $this->initHooks();
        
        // Load admin if in admin area
        if (is_admin()) {
            $this->loadAdmin();
        }
        
        // Load template functions
        $this->loadTemplateFunctions();
    }
    
    /**
     * Load plugin textdomain
     */
    public function loadTextdomain() {
        load_plugin_textdomain('cloudflare-responsive-images', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Load plugin options
     */
    private function loadOptions() {
        $default_options = array(
            'enable_transform' => true,
            'disable_image_sizes' => true,
            'quality' => 85
        );
        
        $saved_options = get_option('cfri_options', $default_options);
        
        // Ensure we always have an array and merge with defaults
        if (!is_array($saved_options)) {
            $this->options = $default_options;
        } else {
            $this->options = array_merge($default_options, $saved_options);
        }
    }
    
    /**
     * Initialize hooks
     */
    private function initHooks() {
        // Disable WordPress image size generation
        if ($this->options['disable_image_sizes']) {
            add_filter('intermediate_image_sizes_advanced', array($this, 'disableImageSizes'));
            add_filter('wp_generate_attachment_metadata', array($this, 'preventImageGeneration'), 10, 2);
        }
        
        // Add Cloudflare Transform to image URLs
        if ($this->options['enable_transform']) {
            add_filter('wp_get_attachment_image_src', array($this, 'addCloudflareTransform'), 10, 4);
            add_filter('wp_get_attachment_url', array($this, 'addCloudflareTransformToUrl'), 10, 2);
            add_filter('wp_get_attachment_image_srcset', array($this, 'addCloudflareTransformToSrcset'), 10, 5);
            add_filter('wp_calculate_image_srcset', array($this, 'addCloudflareTransformToSrcset'), 10, 5);
            add_filter('wp_get_attachment_image', array($this, 'addCloudflareTransformToImageHtml'), 10, 5);
            add_filter('the_content', array($this, 'addCloudflareTransformToContent'), 20);
        }
        
        // Add responsive image attributes
        add_filter('wp_get_attachment_image_attributes', array($this, 'addResponsiveAttributes'), 10, 3);
        
        // Add custom image sizes for Cloudflare Transform
        add_action('init', array($this, 'addCustomImageSizes'));
        
        // Add Cloudflare Transform to ACF image fields
        add_filter('acf/format_value/type=image', array($this, 'addCloudflareTransformToAcf'), 10, 3);
    }
    
    /**
     * Load admin functionality
     */
    private function loadAdmin() {
        require_once CFRI_PLUGIN_DIR . 'includes/class-admin.php';
        new CFRI_Admin();
    }
    
    /**
     * Load template functions
     */
    private function loadTemplateFunctions() {
        require_once CFRI_PLUGIN_DIR . 'includes/template-functions.php';
    }
    
    /**
     * Disable WordPress image size generation
     */
    public function disableImageSizes($sizes) {
        return array();
    }
    
    /**
     * Prevent image generation during upload
     */
    public function preventImageGeneration($metadata, $attachment_id) {
        if ($this->options['disable_image_sizes']) {
            // Remove all generated sizes except the original
            if (isset($metadata['sizes'])) {
                $metadata['sizes'] = array();
            }
        }
        return $metadata;
    }
    
    /**
     * Add Cloudflare Transform to image URLs
     */
    public function addCloudflareTransform($image, $attachment_id, $size, $icon) {
        if (!$image || !$this->options['enable_transform']) {
            return $image;
        }
        
        $url = $image[0];
        $transformed_url = $this->getCloudflareTransformUrl($url, $size);
        
        if ($transformed_url) {
            $image[0] = $transformed_url;
        }
        
        return $image;
    }
    
    /**
     * Add Cloudflare Transform to attachment URLs
     */
    public function addCloudflareTransformToUrl($url, $attachment_id) {
        if (!$url || !$this->options['enable_transform']) {
            return $url;
        }
        
        return $this->getCloudflareTransformUrl($url, 'full');
    }
    
    /**
     * Add Cloudflare Transform to srcset
     */
    public function addCloudflareTransformToSrcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        if (!$this->options['enable_transform']) {
            return $sources;
        }
        
        $transformed_sources = array();
        
        foreach ($sources as $width => $source) {
            $original_url = $source['url'];
            $transformed_url = $this->getCloudflareTransformUrl($original_url, $width);
            
            if ($transformed_url) {
                $transformed_sources[$width] = array(
                    'url' => $transformed_url,
                    'descriptor' => $source['descriptor'],
                    'value' => $source['value']
                );
            } else {
                $transformed_sources[$width] = $source;
            }
        }
        
        return $transformed_sources;
    }
    
    /**
     * Add Cloudflare Transform to complete image HTML
     */
    public function addCloudflareTransformToImageHtml($html, $attachment_id, $size, $icon, $attr) {
        if (!$this->options['enable_transform']) {
            return $html;
        }
        
        // Generate srcset for responsive images
        $srcset = $this->generateResponsiveSrcset($attachment_id);
        
        if ($srcset) {
            // Add srcset to the img tag
            $html = preg_replace('/<img([^>]*?)>/i', '<img$1 srcset="' . esc_attr($srcset) . '">', $html);
        }
        
        return $html;
    }
    
    /**
     * Add Cloudflare Transform to content images
     */
    public function addCloudflareTransformToContent($content) {
        if (!$this->options['enable_transform']) {
            return $content;
        }
        
        // Pattern to match img tags with src attributes
        $pattern = '/<img([^>]*?)src=["\']([^"\']*?)["\']([^>]*?)>/i';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $before_src = $matches[1];
            $src = $matches[2];
            $after_src = $matches[3];
            
            // Check if this is a WordPress upload URL (original or already transformed)
            $upload_dir = wp_upload_dir();
            $is_upload_url = strpos($src, $upload_dir['baseurl']) !== false;
            $is_transformed_url = strpos($src, '/cdn-cgi/image/') !== false;
            
            if ($is_upload_url || $is_transformed_url) {
                // If it's already transformed, extract the original URL
                $original_url = $src;
                if ($is_transformed_url) {
                    // Extract original URL from transformed URL
                    $path_match = preg_match('/\/cdn-cgi\/image\/[^\/]+\/(.+)$/', $src, $url_matches);
                    if ($path_match && isset($url_matches[1])) {
                        // The extracted path already includes /app/uploads, so just prepend the domain
                        $parsed_src = parse_url($src);
                        $original_url = $parsed_src['scheme'] . '://' . $parsed_src['host'] . '/' . ltrim($url_matches[1], '/');
                    }
                }
                
                // Try to extract attachment ID from the original URL
                $attachment_id = $this->getAttachmentIdFromUrl($original_url);
                
                // Generate srcset if we have an attachment ID
                $srcset = '';
                if ($attachment_id) {
                    // Get the original URL directly from the attachment metadata
                    $attachment_meta = wp_get_attachment_metadata($attachment_id);
                    $upload_dir = wp_upload_dir();
                    $original_attachment_url = $upload_dir['baseurl'] . '/' . $attachment_meta['file'];
                    
                    $srcset = $this->generateResponsiveSrcsetFromUrl($original_attachment_url);
                } else {
                    // Fallback: try to use the extracted original URL directly
                    $srcset = $this->generateResponsiveSrcsetFromUrl($original_url);
                }
                
                // Transform the main src URL with quality and width parameters
                $transformed_src = $this->getCloudflareTransformUrl($original_url, 'full');
                
                // Build the img tag with srcset
                $img_tag = '<img' . $before_src . 'src="' . $transformed_src . '"';
                if ($srcset) {
                    $img_tag .= ' srcset="' . esc_attr($srcset) . '"';
                }
                $img_tag .= $after_src . '>';
                
                return $img_tag;
            }
            
            return $matches[0];
        }, $content);
        
        return $content;
    }
    
    /**
     * Add Cloudflare Transform to ACF image fields
     */
    public function addCloudflareTransformToAcf($value, $post_id, $field) {
        if (!$value || !$this->options['enable_transform']) {
            return $value;
        }
        
        if (is_array($value) && isset($value['url'])) {
            $value['url'] = $this->getCloudflareTransformUrl($value['url'], 'full');
        }
        
        return $value;
    }
    
    /**
     * Add responsive image attributes
     */
    public function addResponsiveAttributes($attr, $attachment, $size) {
        if (!$this->options['enable_transform']) {
            return $attr;
        }
        
        // Add loading="lazy" if not already present
        if (!isset($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }
        
        // Add decoding="async" if not already present
        if (!isset($attr['decoding'])) {
            $attr['decoding'] = 'async';
        }
        
        // Generate and add srcset
        $srcset = $this->generateResponsiveSrcset($attachment->ID);
        if ($srcset) {
            $attr['srcset'] = $srcset;
        }
        
        return $attr;
    }
    
    /**
     * Add custom image sizes for Cloudflare Transform
     */
    public function addCustomImageSizes() {
        if (!$this->options['disable_image_sizes']) {
            return;
        }
        
        // Use default WordPress sizes instead of custom sizes
        // This method is kept for compatibility but doesn't add custom sizes anymore
    }
    
    /**
     * Get Cloudflare Transform URL
     */
    private function getCloudflareTransformUrl($url, $size = 'full', $format = null) {
        // Check if URL is from our upload directory
        $upload_dir = wp_upload_dir();
        $is_upload_url = strpos($url, $upload_dir['baseurl']) !== false;
        $is_transformed_url = strpos($url, '/cdn-cgi/image/') !== false;
        
        if (!$is_upload_url && !$is_transformed_url) {
            return $url;
        }
        
        // Build Cloudflare Transform URL using current domain (without /wp path)
        $site_url = home_url();
        
        // If it's already a transformed URL, extract the original path
        if ($is_transformed_url) {
            // Extract original path from Cloudflare URL
            $path_match = preg_match('/\/cdn-cgi\/image\/[^\/]+\/(.+)$/', $url, $matches);
            if ($path_match && isset($matches[1])) {
                // The extracted path already includes the full path, so use it directly
                $url = $site_url . $matches[1];
            }
        }
        
        // Get size dimensions
        $width = $this->getSizeWidth($size);
        $transform_params = array();
        
        // Always add format=auto
        $transform_params[] = 'format=auto';
        
        // Add width parameter
        if ($width) {
            $transform_params[] = 'width=' . $width;
        }
        
        // Add quality parameter
        if ($this->options['quality'] && $this->options['quality'] != 85) {
            $transform_params[] = 'quality=' . $this->options['quality'];
        }

        // Add slow-connection-quality parameter
        $transform_params[] = 'slow-connection-quality=30';
        
        if (!empty($transform_params)) {
            $transform_string = implode(',', $transform_params);
            
            // Extract the full path from the domain root
            $parsed_url = parse_url($url);
            
            // Handle different URL formats consistently
            if (isset($parsed_url['path'])) {
                $full_path = $parsed_url['path'];
            } else {
                // If no path, use the full URL as path
                $full_path = $url;
            }
            
            // Ensure path starts with /
            if (!empty($full_path) && $full_path[0] !== '/') {
                $full_path = '/' . $full_path;
            }
            
            // Ensure we have the complete WordPress uploads path structure
            // If the path starts with /uploads/, prepend /app/ to match WordPress structure
            if (strpos($full_path, '/uploads/') === 0) {
                $full_path = '/app' . $full_path;
            }
            
            // Add onerror parameter to redirect to original image
            $transform_string .= ',onerror=redirect';
            
            $transformed_url = $site_url . '/cdn-cgi/image/' . $transform_string . $full_path;
            
            return $transformed_url;
        }
        
        return $url;
    }
    
    /**
     * Get attachment ID from URL
     */
    private function getAttachmentIdFromUrl($url) {
        // Extract the filename from the URL
        $filename = basename(parse_url($url, PHP_URL_PATH));
        
        // Query for the attachment by filename
        $attachment = get_posts(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'meta_query' => array(
                array(
                    'key' => '_wp_attached_file',
                    'value' => $filename,
                    'compare' => 'LIKE'
                )
            )
        ));
        
        if (!empty($attachment)) {
            return $attachment[0]->ID;
        }
        
        return 0;
    }
    
    /**
     * Generate responsive srcset from URL
     */
    private function generateResponsiveSrcsetFromUrl($url) {
        if (!$url) {
            return '';
        }
        
        // Define responsive breakpoints
        $breakpoints = array(
            640 => '640w',
            1024 => '1024w',
            1200 => '1200w',
            1920 => '1920w'
        );
        
        $srcset_parts = array();
        
        foreach ($breakpoints as $width => $descriptor) {
            // Generate transformed URL with width parameter
            $transformed_url = $this->getCloudflareTransformUrl($url, $width);
            if ($transformed_url) {
                $srcset_parts[] = $transformed_url . ' ' . $descriptor;
            }
        }
        
        return implode(', ', $srcset_parts);
    }
    
    /**
     * Generate responsive srcset for an attachment
     */
    private function generateResponsiveSrcset($attachment_id) {
        if (!$attachment_id) {
            return '';
        }
        
        // Get the original image URL
        $original_url = wp_get_attachment_url($attachment_id);
        if (!$original_url) {
            return '';
        }
        
        return $this->generateResponsiveSrcsetFromUrl($original_url);
    }
    
    /**
     * Get width for image size
     */
    private function getSizeWidth($size) {
        // If size is already a number, return it
        if (is_numeric($size)) {
            return (int) $size;
        }
        
        if ($size === 'full') {
            return null;
        }

        // Use WordPress default sizes
        $default_sizes = array(
            'thumbnail' => 150,
            'medium' => 300,
            'medium_large' => 768,
            'large' => 1024
        );
        
        if (!is_array($size) && array_key_exists($size, $default_sizes)) {
            $default_size = $default_sizes[$size];
            // Handle case where default size might be an array with width property
            if (is_array($default_size) && isset($default_size['width'])) {
                return (int) $default_size['width'];
            } elseif (is_numeric($default_size)) {
                return (int) $default_size;
            }
        }
        
        return null;
    }
    
    /**
     * Get plugin options
     */
    public function getOptions() {
        return $this->options;
    }
    
    /**
     * Update plugin options
     */
    public function updateOptions($options) {
        $this->options = array_merge($this->options, $options);
        update_option('cfri_options', $this->options);
    }
}

// Initialize the plugin
function cfri_init() {
    return CloudflareResponsiveImages::getInstance();
}

// Start the plugin
add_action('plugins_loaded', 'cfri_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    // Set default options
    $default_options = array(
        'enable_transform' => true,
        'disable_image_sizes' => true,
        'quality' => 85
    );
    
    add_option('cfri_options', $default_options);
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up if needed
});

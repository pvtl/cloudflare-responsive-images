<?php
/**
 * Cloudflare Transform helper class
 *
 * @package CloudflareResponsiveImages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cloudflare Transform class
 */
class CFRI_CloudflareTransform {
    
    /**
     * Plugin options
     */
    private $options;
    
    /**
     * Constructor
     */
    public function __construct($options) {
        $this->options = $options;
    }
    
    /**
     * Generate Cloudflare Transform URL
     */
    public function generateTransformUrl($original_url, $width = null, $height = null, $quality = null, $format = null) {
        // Check if URL is from our domain
        $upload_dir = wp_upload_dir();
        if (strpos($original_url, $upload_dir['baseurl']) === false) {
            return $original_url;
        }
        
        // Use options defaults if not specified
        $width = $width ?: $this->getDefaultWidth();
        $quality = $quality ?: $this->options['quality'];
        
        // Build transform parameters
        $transform_params = array();
        
        // Always add format=auto
        $transform_params[] = 'format=auto';
        
        if ($width) {
            $transform_params[] = 'width=' . $width;
        }
        
        if ($height) {
            $transform_params[] = 'height=' . $height;
        }
        
        if ($quality && $quality != 85) {
            $transform_params[] = 'quality=' . $quality;
        }
        
        // Add slow-connection-quality parameter
        $transform_params[] = 'slow-connection-quality=30';
        
        // Build final URL
        if (!empty($transform_params)) {
            $site_url = get_site_url();
            $transform_string = implode(',', $transform_params);
            return $site_url . '/cdn-cgi/image/' . $transform_string . '/' . $original_url;
        }
        
        return $original_url;
    }
    
    /**
     * Generate responsive image srcset
     */
    public function generateSrcset($original_url, $sizes = null) {
        if (!$sizes) {
            // Use default WordPress sizes
            $sizes = array(
                'thumbnail' => 150,
                'medium' => 300,
                'medium_large' => 768,
                'large' => 1024
            );
        }
        
        $srcset = array();
        
        foreach ($sizes as $name => $width) {
            $transform_url = $this->generateTransformUrl($original_url, $width);
            $srcset[] = $transform_url . ' ' . $width . 'w';
        }
        
        return implode(', ', $srcset);
    }
    
    /**
     * Generate responsive image sizes attribute
     */
    public function generateSizes($breakpoints = null) {
        if (!$breakpoints) {
            $breakpoints = array(
                'small' => '(max-width: 640px)',
                'medium' => '(max-width: 1024px)',
                'large' => '(max-width: 1200px)',
                'xlarge' => '(min-width: 1201px)'
            );
        }
        
        // Use default WordPress sizes
        $default_sizes = array(
            'small' => 640,
            'medium' => 1024,
            'large' => 1200,
            'xlarge' => 1920
        );
        
        $sizes = array();
        
        foreach ($breakpoints as $name => $media_query) {
            $width = isset($default_sizes[$name]) ? $default_sizes[$name] : '100vw';
            $sizes[] = $media_query . ' ' . $width . 'px';
        }
        
        return implode(', ', $sizes);
    }
    
    /**
     * Check if URL is a Cloudflare Transform URL
     */
    public function isTransformUrl($url) {
        return strpos($url, '/cdn-cgi/image/') !== false;
    }
    
    /**
     * Extract original URL from Cloudflare Transform URL
     */
    public function extractOriginalUrl($transform_url) {
        if (!$this->isTransformUrl($transform_url)) {
            return $transform_url;
        }
        
        // Extract the original URL after the transform parameters
        $parts = explode('/cdn-cgi/image/', $transform_url);
        if (count($parts) < 2) {
            return $transform_url;
        }
        
        $after_transform = $parts[1];
        $url_parts = explode('/', $after_transform, 2);
        
        if (count($url_parts) < 2) {
            return $transform_url;
        }
        
        return $url_parts[1];
    }
    
    /**
     * Get default width based on context
     */
    private function getDefaultWidth() {
        // Return the largest default size
        $default_sizes = array(150, 300, 768, 1024);
        return max($default_sizes);
    }
    
    /**
     * Validate Cloudflare domain
     */
    public function validateDomain($domain) {
        if (empty($domain)) {
            return false;
        }
        
        // Remove protocol if present
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        
        // Add https protocol
        $domain = 'https://' . $domain;
        
        // Validate URL format
        return filter_var($domain, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Test Cloudflare Transform connection
     */
    public function testConnection() {
        // Create a test URL using current site URL
        $site_url = get_site_url();
        $test_url = $site_url . '/cdn-cgi/image/width=100/test-image.jpg';
        
        // Make a HEAD request to test the connection
        $response = wp_remote_head($test_url, array(
            'timeout' => 10,
            'user-agent' => 'CloudflareResponsiveImages/1.0'
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return array('success' => true, 'message' => 'Connection successful');
        } elseif ($response_code === 404) {
            return array('success' => true, 'message' => 'Connection successful (404 expected for test image)');
        } else {
            return array(
                'success' => false,
                'message' => 'Unexpected response code: ' . $response_code
            );
        }
    }
    
}

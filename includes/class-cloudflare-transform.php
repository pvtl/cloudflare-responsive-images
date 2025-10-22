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
        // Check if URL is from our upload directory
        $upload_dir = wp_upload_dir();
        $is_upload_url = strpos($original_url, $upload_dir['baseurl']) !== false;
        $is_transformed_url = strpos($original_url, '/cdn-cgi/image/') !== false;
        
        if (!$is_upload_url && !$is_transformed_url) {
            return $original_url;
        }
        
        // Build Cloudflare Transform URL using current domain (without /wp path)
        $site_url = home_url();
        
        // If it's already a transformed URL, extract the original path
        if ($is_transformed_url) {
            // Extract original path from Cloudflare URL
            $path_match = preg_match('/\/cdn-cgi\/image\/[^\/]+\/(.+)$/', $original_url, $matches);
            if ($path_match && isset($matches[1])) {
                // The extracted path already includes the full path, so use it directly
                $original_url = $site_url . $matches[1];
            }
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
            $transform_string = implode(',', $transform_params);
            
            // Extract the full path from the domain root
            $parsed_url = parse_url($original_url);
            
            // Handle different URL formats consistently
            if (isset($parsed_url['path'])) {
                $full_path = $parsed_url['path'];
            } else {
                // If no path, use the full URL as path
                $full_path = $original_url;
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

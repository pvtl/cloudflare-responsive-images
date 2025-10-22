<?php
/**
 * Image utilities for Cloudflare Responsive Images plugin
 *
 * @package CloudflareResponsiveImages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image utilities class
 */
class CFRI_ImageUtils {
    
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
     * Get image dimensions from URL
     */
    public function getImageDimensions($url) {
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
        
        if (file_exists($file_path)) {
            $image_info = getimagesize($file_path);
            if ($image_info) {
                return array(
                    'width' => $image_info[0],
                    'height' => $image_info[1],
                    'mime' => $image_info['mime']
                );
            }
        }
        
        return false;
    }
    
    /**
     * Check if image is from WordPress uploads
     */
    public function isUploadImage($url) {
        $upload_dir = wp_upload_dir();
        return strpos($url, $upload_dir['baseurl']) !== false;
    }
    
    /**
     * Get responsive image HTML
     */
    public function getResponsiveImageHtml($attachment_id, $size = 'full', $attr = array()) {
        $image = wp_get_attachment_image_src($attachment_id, $size);
        
        if (!$image) {
            return '';
        }
        
        $src = $image[0];
        $width = $image[1];
        $height = $image[2];
        
        // Generate srcset
        $srcset = $this->generateSrcset($attachment_id);
        
        // Generate sizes attribute
        $sizes = $this->generateSizes();
        
        // Default attributes
        $default_attr = array(
            'src' => $src,
            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'width' => $width,
            'height' => $height,
            'loading' => 'lazy',
            'decoding' => 'async'
        );
        
        if ($srcset) {
            $default_attr['srcset'] = $srcset;
        }
        
        if ($sizes) {
            $default_attr['sizes'] = $sizes;
        }
        
        // Merge with provided attributes
        $attr = array_merge($default_attr, $attr);
        
        // Build HTML
        $html = '<img';
        foreach ($attr as $name => $value) {
            $html .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
        }
        $html .= ' />';
        
        return $html;
    }
    
    /**
     * Generate srcset for attachment
     */
    public function generateSrcset($attachment_id) {
        $original_url = wp_get_attachment_url($attachment_id);
        
        if (!$original_url || !$this->isUploadImage($original_url)) {
            return '';
        }
        
        $srcset = array();
        // Use default WordPress sizes
        $default_sizes = array(
            'thumbnail' => 150,
            'medium' => 300,
            'medium_large' => 768,
            'large' => 1024
        );
        
        foreach ($default_sizes as $name => $width) {
            $transform_url = $this->getCloudflareTransformUrl($original_url, $width);
            if ($transform_url) {
                $srcset[] = $transform_url . ' ' . $width . 'w';
            }
        }
        
        return implode(', ', $srcset);
    }
    
    /**
     * Generate sizes attribute
     */
    public function generateSizes() {
        $sizes = array();
        
        // Use default WordPress sizes
        $default_sizes = array(
            'small' => 640,
            'medium' => 1024,
            'large' => 1200,
            'xlarge' => 1920
        );
        
        $breakpoints = array(
            'small' => '(max-width: 640px)',
            'medium' => '(max-width: 1024px)',
            'large' => '(max-width: 1200px)',
            'xlarge' => '(min-width: 1201px)'
        );
        
        foreach ($breakpoints as $name => $media_query) {
            if (isset($default_sizes[$name])) {
                $sizes[] = $media_query . ' ' . $default_sizes[$name] . 'px';
            }
        }
        
        // Add default size
        $sizes[] = '100vw';
        
        return implode(', ', $sizes);
    }
    
    /**
     * Get Cloudflare Transform URL
     */
    private function getCloudflareTransformUrl($url, $width) {
        $site_url = get_site_url();
        $transform_params = array();
        
        // Always add format=auto
        $transform_params[] = 'format=auto';
        
        if ($width) {
            $transform_params[] = 'width=' . $width;
        }
        
        if ($this->options['quality'] && $this->options['quality'] != 85) {
            $transform_params[] = 'quality=' . $this->options['quality'];
        }
        
        // Add slow-connection-quality parameter
        $transform_params[] = 'slow-connection-quality=30';
        
        if (!empty($transform_params)) {
            $transform_string = implode(',', $transform_params);
            return $site_url . '/cdn-cgi/image/' . $transform_string . '/' . $url;
        }
        
        return $url;
    }
    
}

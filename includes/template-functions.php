<?php
/**
 * Template functions for Cloudflare Responsive Images plugin
 *
 * @package CloudflareResponsiveImages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get responsive image HTML
 *
 * @param int $attachment_id Attachment ID
 * @param string $size Image size
 * @param array $attr Image attributes
 * @return string HTML for responsive image
 */
function cfri_get_responsive_image($attachment_id, $size = 'full', $attr = array()) {
    $plugin = CloudflareResponsiveImages::getInstance();
    $options = $plugin->getOptions();
    
    if (!$options['enable_transform']) {
        return wp_get_attachment_image($attachment_id, $size, false, $attr);
    }
    
    $image_utils = new CFRI_ImageUtils($options);
    return $image_utils->getResponsiveImageHtml($attachment_id, $size, $attr);
}

/**
 * Echo responsive image HTML
 *
 * @param int $attachment_id Attachment ID
 * @param string $size Image size
 * @param array $attr Image attributes
 */
function cfri_responsive_image($attachment_id, $size = 'full', $attr = array()) {
    echo cfri_get_responsive_image($attachment_id, $size, $attr);
}

/**
 * Get Cloudflare Transform URL
 *
 * @param string $url Original image URL
 * @param int $width Desired width
 * @param int $height Desired height
 * @return string Cloudflare Transform URL
 */
function cfri_get_transform_url($url, $width = null, $height = null) {
    $plugin = CloudflareResponsiveImages::getInstance();
    $options = $plugin->getOptions();
    
    if (!$options['enable_transform']) {
        return $url;
    }
    
    $cloudflare_transform = new CFRI_CloudflareTransform($options);
    return $cloudflare_transform->generateTransformUrl($url, $width, $height);
}

/**
 * Get responsive image srcset
 *
 * @param int $attachment_id Attachment ID
 * @return string Srcset attribute value
 */
function cfri_get_srcset($attachment_id) {
    $plugin = CloudflareResponsiveImages::getInstance();
    $options = $plugin->getOptions();
    
    if (!$options['enable_transform']) {
        return wp_get_attachment_image_srcset($attachment_id, 'full');
    }
    
    $image_utils = new CFRI_ImageUtils($options);
    return $image_utils->generateSrcset($attachment_id);
}

/**
 * Get responsive image sizes attribute
 *
 * @return string Sizes attribute value
 */
function cfri_get_sizes() {
    $plugin = CloudflareResponsiveImages::getInstance();
    $options = $plugin->getOptions();
    
    if (!$options['enable_transform']) {
        return '100vw';
    }
    
    $image_utils = new CFRI_ImageUtils($options);
    return $image_utils->generateSizes();
}

/**
 * Check if Cloudflare Transform is enabled
 *
 * @return bool True if enabled
 */
function cfri_is_transform_enabled() {
    $plugin = CloudflareResponsiveImages::getInstance();
    $options = $plugin->getOptions();
    
    return $options['enable_transform'];
}

/**
 * Test Cloudflare connection
 *
 * @return array Test results
 */
function cfri_test_connection() {
    $plugin = CloudflareResponsiveImages::getInstance();
    $options = $plugin->getOptions();
    
    $cloudflare_transform = new CFRI_CloudflareTransform($options);
    return $cloudflare_transform->testConnection();
}

/**
 * Get responsive image for ACF field
 *
 * @param array $field ACF field value
 * @param string $size Image size
 * @param array $attr Image attributes
 * @return string HTML for responsive image
 */
function cfri_get_acf_responsive_image($field, $size = 'full', $attr = array()) {
    if (empty($field) || !is_array($field) || !isset($field['ID'])) {
        return '';
    }
    
    return cfri_get_responsive_image($field['ID'], $size, $attr);
}

/**
 * Echo responsive image for ACF field
 *
 * @param array $field ACF field value
 * @param string $size Image size
 * @param array $attr Image attributes
 */
function cfri_acf_responsive_image($field, $size = 'full', $attr = array()) {
    echo cfri_get_acf_responsive_image($field, $size, $attr);
}

/**
 * Get responsive image for post thumbnail
 *
 * @param int $post_id Post ID
 * @param string $size Image size
 * @param array $attr Image attributes
 * @return string HTML for responsive image
 */
function cfri_get_post_thumbnail($post_id = null, $size = 'full', $attr = array()) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $attachment_id = get_post_thumbnail_id($post_id);
    
    if (!$attachment_id) {
        return '';
    }
    
    return cfri_get_responsive_image($attachment_id, $size, $attr);
}

/**
 * Echo responsive image for post thumbnail
 *
 * @param int $post_id Post ID
 * @param string $size Image size
 * @param array $attr Image attributes
 */
function cfri_post_thumbnail($post_id = null, $size = 'full', $attr = array()) {
    echo cfri_get_post_thumbnail($post_id, $size, $attr);
}

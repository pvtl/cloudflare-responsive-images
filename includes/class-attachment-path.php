<?php
/**
 * Attachment path helpers for Cloudflare Responsive Images
 *
 * @package CloudflareResponsiveImages
 */

// Prevent direct access when loaded inside WordPress
if (defined('ABSPATH') === false && php_sapi_name() !== 'cli') {
    exit;
}

/**
 * Resolves WordPress uploads relative paths from URLs.
 */
class CFRI_AttachmentPath {

    /**
     * Extract the uploads-relative path (e.g. 2026/08/GalleryImg2.jpg) from a URL.
     *
     * @param string $url Absolute or path-only URL.
     * @return string Relative path or empty string when not an uploads URL.
     */
    public static function relativeFromUrl($url) {
        if (empty($url) || !is_string($url)) {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (empty($path) || !is_string($path)) {
            return '';
        }

        if (preg_match('#/(?:app/)?uploads/(.+)$#', $path, $matches)) {
            return ltrim($matches[1], '/');
        }

        return '';
    }

    /**
     * Compare two uploads-relative paths for an exact match.
     *
     * @param string $left  Relative path.
     * @param string $right Relative path.
     * @return bool
     */
    public static function pathsMatch($left, $right) {
        if ($left === '' || $right === '') {
            return false;
        }

        return $left === $right;
    }
}

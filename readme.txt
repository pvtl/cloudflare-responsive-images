=== Cloudflare Responsive Images ===
Contributors: your-username
Tags: cloudflare, responsive images, image optimization, performance, transform
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disables WordPress image variants and uses Cloudflare Transform for responsive images to improve performance and save storage space.

== Description ==

Cloudflare Responsive Images is a performance optimization plugin that disables WordPress automatic image size generation and uses Cloudflare Transform instead to generate responsive images on-demand.

**Key Features:**

* **Disables WordPress Image Sizes**: Prevents WordPress from generating multiple image sizes, saving significant storage space
* **Cloudflare Transform Integration**: Uses Cloudflare's image transformation service for responsive images
* **Automatic URL Conversion**: Automatically converts image URLs to use Cloudflare Transform
* **WebP and AVIF Support**: Automatically serves modern image formats for better compression
* **Custom Image Sizes**: Define your own responsive breakpoints and image sizes
* **ACF Integration**: Works seamlessly with Advanced Custom Fields image fields
* **Performance Monitoring**: Track bandwidth savings and performance improvements

**How it Works:**

1. Disables WordPress automatic image size generation during upload
2. Intercepts image URLs and converts them to use Cloudflare Transform
3. Generates responsive srcset and sizes attributes for optimal loading
4. Serves WebP/AVIF formats when supported by the browser

**Requirements:**

* WordPress 5.0 or higher
* PHP 7.4 or higher
* Cloudflare account with Transform enabled
* Site must be using Cloudflare as CDN

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/cloudflare-responsive-images` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > Cloudflare Images to configure the plugin
4. Enter your Cloudflare domain and configure transform settings
5. Test the connection to ensure everything is working

== Frequently Asked Questions ==

= Do I need a Cloudflare account? =

Yes, you need a Cloudflare account with Transform enabled. This plugin uses Cloudflare's image transformation service to generate responsive images on-demand.

= Will this affect my existing images? =

The plugin will automatically convert existing image URLs to use Cloudflare Transform. Your original images remain unchanged in your media library.

= Can I still use WordPress image functions? =

Yes, all WordPress image functions continue to work. The plugin simply modifies the URLs to use Cloudflare Transform instead of generated image sizes.

= What image formats are supported? =

The plugin supports all formats that Cloudflare Transform supports, including JPEG, PNG, WebP, and AVIF. You can configure which formats to use in the plugin settings.

= Will this work with my theme? =

Yes, the plugin works with any theme. It automatically intercepts image URLs and converts them to use Cloudflare Transform.

== Screenshots ==

1. Plugin settings page
2. Transform configuration options
3. Custom image sizes setup
4. Performance statistics

== Changelog ==

= 1.0.0 =
* Initial release
* Disable WordPress image size generation
* Cloudflare Transform integration
* WebP and AVIF support
* Custom image sizes configuration
* ACF integration
* Performance monitoring

== Upgrade Notice ==

= 1.0.0 =
Initial release of Cloudflare Responsive Images plugin.

== Support ==

For support, please visit the plugin's GitHub repository or contact the plugin author.

== Privacy Policy ==

This plugin does not collect or store any personal data. It only processes image URLs to use Cloudflare Transform for better performance.

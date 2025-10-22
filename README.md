# Cloudflare Responsive Images

A WordPress plugin that optimizes images using Cloudflare Transform instead of generating multiple image sizes.

## What it does

- **Disables WordPress image size generation** - Saves storage space by preventing WordPress from creating multiple image variants
- **Uses Cloudflare Transform** - Generates responsive images on-demand using Cloudflare's image transformation service
- **Automatic URL conversion** - Converts image URLs to use Cloudflare Transform automatically
- **Modern format support** - Serves WebP and AVIF formats for better compression
- **ACF integration** - Works seamlessly with Advanced Custom Fields

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Cloudflare account with Transform enabled
- Site using Cloudflare as CDN

## Installation

### Manual Installation
1. Upload the plugin to `/wp-content/plugins/cloudflare-responsive-images/`
2. Activate the plugin in WordPress admin
3. Go to **Settings > Cloudflare Images** to configure
4. Enter your Cloudflare domain and test the connection

### Composer Installation
```bash
# 1. Get it ready (to use a repo outside of packagist)
composer config repositories.cloudflare-responsive-images git https://github.com/pvtl/cloudflare-responsive-images.git

# 2. Install the Plugin - we want all updates from this major version (while non-breaking)
composer require "pvtl/cloudflare-responsive-images:~1.0"
```

## How it works

1. Disables WordPress automatic image size generation during upload
2. Intercepts image URLs and converts them to use Cloudflare Transform
3. Generates responsive `srcset` and `sizes` attributes
4. Serves optimized formats (WebP/AVIF) when supported

## Author

Pivotal Agency

## License

GPL v2 or later

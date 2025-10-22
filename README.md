# Cloudflare Responsive Images

By default, WordPress automatically creates several sizes of each image uploaded to the media library, and will save them to disk. This can use a lot of disk space on sites with many images (or image variants).

Cloudflare Images can solve this by transforming the original images into other sizes on the fly.

This plugin will re-write the URL's of all WP images to ensure they run through the Cloudflare Image transformation serivce.

## What it does

- **Disables WordPress image size generation** - Saves storage space by preventing WordPress from creating multiple image variants
- **Uses Cloudflare Transform** - Generates responsive images on-demand using Cloudflare's image transformation service
- **Automatic URL conversion** - Converts image URLs to use Cloudflare Transform automatically
- **Modern format support** - Serves WebP and AVIF formats for better compression
- **ACF integration** - Works seamlessly with Advanced Custom Fields

## Requirements

- Cloudflare
    - Site with proxy **ENABLED**
    - Account with Image Transformations **ENABLED**
- WordPress 5.0+

## Installation

### Composer / Wordpress Bedrock
```bash
composer require pvtl/cloudflare-responsive-images
```

### Manual
1. Upload plugin to `wp-content/plugins/cloudflare-responsive-images/`
2. Activate plugin in WordPress admin

## Author

Pivotal Agency Pty Ltd

## License

MIT

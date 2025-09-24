# Armoury Cache

A lightweight WordPress plugin that integrates SpinupWP cache purging with Cloudflare cache management.

## Description

Armoury Cache bridges the gap between SpinupWP's server-level cache management and Cloudflare's CDN cache. When SpinupWP purges its entire cache, this plugin automatically purges your Cloudflare cache, ensuring your static assets are refreshed across Cloudflare's global network.

This plugin is designed for WordPress sites that use:
- **SpinupWP** for HTML page caching
- **Cloudflare** for static asset caching (CSS, JS, images, fonts)

## Features

- ✅ Automatic Cloudflare cache purging when SpinupWP purges all caches
- ✅ Zero configuration interface - just set your API credentials
- ✅ Lightweight and focused - does one thing well
- ✅ Secure - API credentials stored in wp-config.php, not the database
- ✅ Compatible with WordPress 5.8+ and PHP 7.4+

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- [SpinupWP plugin](https://wordpress.org/plugins/spinupwp/) installed and activated
- Cloudflare account with your domain configured

## Installation

1. Download the plugin files to your `/wp-content/plugins/armoury-cache` directory
2. Install and activate the [SpinupWP plugin](https://wordpress.org/plugins/spinupwp/) if not already active
3. Add your Cloudflare credentials to `wp-config.php` (see Configuration below)
4. Activate the plugin through the 'Plugins' menu in WordPress

## Configuration

Add the following constants to your `wp-config.php` file above the line that says `/* That's all, stop editing! */`:

```php
// Armoury Cache Configuration
define( 'ARMOURY_CF_ZONE_ID', 'your_cloudflare_zone_id' );
define( 'ARMOURY_CF_API_TOKEN', 'your_cloudflare_api_token' );
```

### Getting Your Cloudflare Zone ID

1. Log in to your Cloudflare dashboard
2. Select your domain
3. In the right sidebar, locate the "Zone ID" under the API section
4. Copy this value

### Creating a Cloudflare API Token

1. Go to Cloudflare dashboard → My Profile → API Tokens
2. Click "Create Token"
3. Select "Create Custom Token"
4. Configure the token:
   - **Token name**: "Armoury Cache Plugin" (or any descriptive name)
   - **Permissions**: 
     - Zone → Cache Purge → Purge
   - **Zone Resources**: 
     - Include → Specific zone → Select your domain
5. Click "Continue to summary" and "Create Token"
6. Copy the token value (you won't be able to see it again)

**Security Note**: This token only has permission to purge cache, following the principle of least privilege.

## How It Works

1. When you click "Purge All Caches" in the SpinupWP admin bar menu
2. SpinupWP purges its server-level page and object caches
3. Armoury Cache automatically triggers a Cloudflare cache purge
4. Your static assets are purged across Cloudflare's CDN

## Debugging

Enable WordPress debug mode to see plugin activity in your debug.log:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Log messages will appear in `/wp-content/debug.log`

## Use Cases

This plugin is ideal when:
- You use SpinupWP for WordPress hosting
- You use Cloudflare for CDN and static asset caching
- You want automatic cache synchronization between both services
- You prefer simple, focused plugins over complex solutions

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/armourymedia/armoury-cache).

## License

GPL v3 or later

## Author

[Armoury Media](https://www.armourymedia.com/)

## Changelog

### 1.0.0
- Initial release
- Automatic Cloudflare cache purging on SpinupWP full cache purge
- Secure credential management via wp-config.php
- Comprehensive error logging

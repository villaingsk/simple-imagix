=== Simple Imagix ===
Contributors: Kref Studio
Tags: webp, converter, optimization, image, compression
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 1.1

Convert JPG/PNG to WebP automatically + bulk conversion tool + quality control + space saving options.

== Description ==

Simple Imagix is a lightweight yet powerful plugin designed to modernize your WordPress media library. It automatically handles image optimization by converting uploads to the modern WebP format, ensuring faster load times and better performance for your website.

**Requirements:**
*   **PHP GD Library**: Must be enabled on your server (standard on most hosting).

**Key Features:**

*   **Automatic WebP Conversion**: Seamlessly converts every new JPG and PNG image to WebP format immediately upon upload.
*   **Bulk Conversion Tool**: Includes a built-in tool to scan and convert all existing images in your media library to WebP with a single click.
*   **Compression Quality Control**: Fully adjustable quality setting (1-100) allowing you to find the perfect balance between image sharpness and file size reduction.
*   **Space Saving Mode**: Optional setting to automatically delete the original JPG/PNG files after successful conversion, keeping your server clean.
*   **High Compatibility**: Optimized to run smoothly on PHP 7.4, 8.0, and newer versions, as well as the latest WordPress releases.
*   **Simple Management**: Easy-to-use settings page located under **Settings > Simple Imagix**.

== Installation ==

1. Upload the `simple-imagix` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > Simple Imagix** to configure your compression quality and preferences.

== Frequently Asked Questions ==

= Does this delete my original images? =
By default, no. However, there is a checkbox in the settings "Delete Original Image" (`Hapus JPG/PNG setelah convert ke WebP`) if you wish to remove the originals to save space.

= Is it compatible with PHP 8? =
Yes, Simple Imagix is fully compatible with PHP 7.4 and PHP 8.0+.

= Does it work on Nginx/LiteSpeed? =
Yes! Because Simple Imagix works at the application level (PHP) and replaces the file directly in the database, it **works on all web servers** (Apache, Nginx, LiteSpeed, IIS, etc.) without needing any special configuration files or `.htaccess` editing.

== Screenshots ==

1. Settings page with quality control and bulk conversion options.

== Changelog ==

= 1.1 - 2026-02-18 =
* Fixed: Linter errors in admin page (escaped output).
* Improved: Performance for bulk conversion (suppressed direct DB query warnings).
* Initial release on WordPress.org (v1.0 was internal).

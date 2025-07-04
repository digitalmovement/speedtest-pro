=== Speedtest Pro ===
Contributors: digitalmovementstudio
Plugin URI: https://wpspeedtestpro.com/
Tags: performance, speed test, benchmark, server performance, pagespeed
Requires at least: 6.2
Tested up to: 6.8
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Author: Digital Movement Studio
Author URI: https://digitalmovement.co.uk

Speedtest Pro analyzes your site's performance with comprehensive server benchmarks and speed testing tools.

== Description ==

Speedtest Pro helps you benchmark your WordPress site using real-world performance data and Google PageSpeed Insights. With the new server performance benchmarking feature, you can now get detailed insights into your server's capabilities.

Key features include:

* Comprehensive server performance benchmarks
* Math, string, loops, and conditionals tests
* MySQL performance testing
* Hosting speed and stability metrics
* Google PageSpeed API integration
* Historical data tracking and visualization
* Easy-to-understand charts and graphs

Whether you're a site owner looking to improve your website's speed or a developer needing to diagnose performance issues, WP Speedtest Pro provides the data you need to make informed decisions.

== Installation ==

1. Upload the zip file to the folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the 'Speedtest Pro' menu in your WordPress admin panel to start using the plugin
4. You will need to obtain the following API keys:
   * UptimeRobot API Key - Get it from [UptimeRobot.com](https://uptimerobot.com/)
   * Google API Key - Get it from [Google Cloud Console](https://console.cloud.google.com/)
5. Enter your API keys in the plugin settings page.

== Frequently Asked Questions ==

= How often should I run the server performance tests? =

We recommend running the tests once a week to track your server's performance over time. However, you can run them as often as you like, especially after making changes to your server configuration.

= Will running these tests affect my site's performance? =

The tests are designed to be non-intrusive, but they do use some server resources. We recommend running them during low-traffic periods to minimize any potential impact on your site's performance.

== Screenshots ==

1. Server Performance Dashboard
2. Benchmark Results Chart
3. Google PageSpeed Insights Historical Performance Data

== Changelog ==

= 1.1.0 =
* Added comprehensive server performance benchmarking feature
* Introduced new tests for math, string, loops, and conditionals performance
* Added MySQL and specific performance tests
* Implemented historical data tracking and visualization
* Updated user interface for easier interpretation of results

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.1.1 =
This update introduces powerful server performance benchmarking features. Upgrade to gain valuable insights into your server's capabilities and track performance over time.

== Credits ==

This plugin uses the following third-party resources:

* Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com
  License: https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License)



== External Services ==

This plugin connects to the following external services:

* WP Speedtest Pro Configuration Service (assets.wpspeedtestpro.com) - Retrieves plugin configuration data including:
  - Hosting provider database (wphostingproviders.json)
  - SSL email templates (ssl_emails.json) 
  - Performance benchmarks (performance-test-averages.json)
  - Dashboard content (dashboard-advertisers.json)
  [Privacy Policy](https://wpspeedtestpro.com/privacy-policy/) [Terms of Service](https://wpspeedtestpro.com/terms-of-service/)

* WP Speedtest Pro Analytics (analytics.wpspeedtestpro.com/upload) - Used to collect anonymous usage statistics and performance data to improve the plugin and help the wider community to find the fastest hosting. This is our own service [Privacy Policy](https://wpspeedtestpro.com/privacy-policy/) [Terms of Service](https://wpspeedtestpro.com/terms-of-service/)
* UptimeRobot API - Used for monitoring website uptime. [Privacy Policy](https://uptimerobot.com/privacy/)
* Cloudflare - Used for performance testing from multiple global locations. [Privacy Policy](https://www.cloudflare.com/privacypolicy/)
* Google Cloud Platform - Used for data processing, page speed testing and storage. (www.googleapis.com) [Privacy Policy](https://cloud.google.com/terms/cloud-privacy-notice)
* GCPPing - Used for performance testing from multiple global locations. (https://global.gcping.com/) [Privacy Policy](https://www.gcpping.com/privacy/)
* SSL Labs - Used for SSL testing. [Privacy Policy](https://www.ssllabs.com/privacy/)


Data sent to these services may include your website URL, performance metrics, and basic configuration information. No personal user data is collected or transmitted unless explicitly provided.

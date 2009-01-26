=== Plugin Name ===
Contributors: emjayoh, onewebsite, mikeolaski
Donate link: http://siteRighter.com/
Tags: Navigation, Page, Menu
Requires at least: 2.1
Tested up to: 2.6.3
Stable tag: 0.0.1

Allows user to assign a Page to a particular navigation object such as Super, Head, Sub, Side, or Foot navigation

== Description ==

This plugin adds a new sidebox (only available on the Add/Edit page sections) from which the author can configure the scope of the wp_list_navtype_pages() function and the edited/created page, wp_list_navtype_pages function supports the same parameters wp_list_pages function does, but it also adds support for the new "navigation_type" parameter. Possible values are: wp_list_pages(navigation_type = 'Super' || 'Head' || 'Side' || 'Page' || 'Foot')

== Installation ==

1 Download the plugin
2. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently Asked Questions ==

= Does this plugin work in 2.7 =

Not yet, I haven't had time to update it. If anyone wants to contribute please let me know.
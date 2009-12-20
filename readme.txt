=== Plugin Name ===
Contributors: emjayoh, onewebsite, mikeolaski
Donate link: http://mikeolaski.com/target-page-navigation-download
Tags: Navigation, Page, Menu
Requires at least: 2.1
Tested up to: 2.9
Stable tag: 0.2

Allows user to assign a Page to a particular navigation object such as Super, Head, Sub, Side, or Foot navigation

== Description ==

This plugin adds a new optoin (only available on the Add/Edit page sections) that enables the author to assign the page to one of 4 navigation types(Super, Head, Side, Page, Foot) to be used in a new function (wp_list_navtype_pages), that will replace the wp_list_pages() function.

All the same parameters are acceptable, and we have added one new parameter that sets the navigation type navigationtype= "super" || "head" || "side" || "page" || "foot"

== Installation ==

1 Download the plugin
2. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently Asked Questions ==

= Does this plugin work in 2.9 =

Yes it does
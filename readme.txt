=== COP CSS Custom Post Type Lite ===
Contributors: trevogre 
Donate link:http://www.azuregreencreative.com
Tags: CSS, Custom Post Type
Requires at least: 3.2.1
Tested up to: 3.2.1
Stable tag: trunk

Adds a custom post type for implementing theme independent css.

== Description ==

Adds a custom post type of css that will compile all entries into a single minified css file that will automatically be enqueued to your site.

Minified CSS is stored in a transient for caching.

User now needs 'edit_themes' permission to see the css editor. By default this is only the administrator.

<h4>Shortcodes</h4>

Shortcodes are now processed during compilation of your css. 

An new shortcode has been added called:

[meta key="(name of arbitrary custom field)" post_id="(optional)"]

This allows you to define a whatever custom fields you like on an individual css post and use them in your css.

Ex. a { color: [meta key="my_color"]; }

So you can define common elements and use them throughout your css. 

If you like you can use the option post_id to put those values from another post. So you can have one master css post with your primary colors and such and referance it in other posts.
 

<h4>Credits</h4>

Minification code from <a href="http://www.lateralcode.com/css-minifier/">http://www.lateralcode.com/css-minifier/</a>

<h4>Notes on Function</h4>

All css is compiled on save unless the transient has expired. If which case a query is run to compile the css durning rendering.
I may add option to use either a wp_option or a transient based upon user preferance.  

Css is compiled by menu_order. I would like to add more options for better sorting.

There is an options page that outputs the css into a textarea so that you can review the final css in the dashboard.

Email me to request customizations <a href="mailto:trevor@mailagc.com">trevor@mailagc.com</a>

Future options may also include the ability to write your css to your theme directory. This plugin currently must be active to keep the css available. 


I'm trying to follow the path of Mark Jaquith. Read more here.
<a href="http://markjaquith.wordpress.com/2011/06/07/how-to-write-a-plugin-that-ill-use/">How to Write a Plugin I'll use</a>

<a href="http://www.azuregreencreative.com"><img src="http://www.azuregreencreative.com/images/azure-green-banner.jpg"/></a>
== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

== Frequently Asked Questions ==

Questions Please.

== Screenshots ==


== Changelog ==

0.2.0

Bug Fixes to hook registration

0.1.9
  
Began adding shortcodes to css.
Added [meta key="(arbitrary custom field)"]
 
0.1.8
 
Fixed preview using option instead of transient.
Added check for permission to 'edit_themes' before loading interface.

0.1.7

Initial Public Version

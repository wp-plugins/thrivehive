=== ThriveHive ===
Contributors: thrivehive
Donate link: 
Tags: web analytics, tracking, thrivehive thrive hive
Requires at least: 3
Tested up to: 3.8.1
Stable tag: .1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin will automatically instrument a side with ThriveHive's tracking code. 

== Description ==

This plugin will automatically instrument a site with ThriveHive's tracking code, insert a tracked phone number, and insert a tracked form.

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Get your account's tracking assets and paste them in the appropriate field in the plugin settings.
4. The tracking code will automatically be added to your site. To insert a phone number, add this PHP to your template in the desired location on your page. get_option ('th_phone_number'). To insert a form, use get_option('th_form_html').

== Frequently asked questions ==
1. How do I get the assets to start using this plugin?
Answer: To get started with ThriveHive, you'll need to create an account with ThriveHive at http://thrivehive.com/free-trial/signup. Once you have an account, go to the account page to get your tracking code (which containts your account ID). ThriveHive will help you get your form HTML and tracked phone lines as well.
2. How do I insert the phone number and form into my pages, posts, and templates?
Answer: There are two ways to insert the forms and phone numbers. The first uses "shortcodes" which you can use in your pages and posts. Just type [th_form] or [th_phone] in a post or a page and it will pull in the appropriate asset (assuming you have set one up in the ThriveHive plugin settings page. To insert your phone number and form into your php template files, you will need to include <?php th_display_form(); ?> or <?php th_display_form(); >? in your template.



== Screenshots ==



== Changelog ==
V 1.00  MAJOR Release integrating with the new Thrivehive wordpress interface
V 0.59: Fixing but with getting blog post content
V 0.58: Changing the method for getting public previews
V 0.54: Added rewrite flushing on activation
V 0.51: Bug fix for creating blog posts with no title having all content wiped out
V 0.5: Major update adding integration with ThriveHive to create and view blog posts as well as various usability enhancements


== Upgrade notice ==


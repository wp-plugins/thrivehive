=== ThriveHive ===
Contributors: thrivehive
Donate link:
Tags: web analytics, tracking, thrivehive thrive hive
Requires at least: 3
Tested up to: 4.1
Stable tag: .1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin will automatically instrument a site with ThriveHive's tracking code.

== Description ==

This plugin will automatically instrument a site with ThriveHive's tracking code, insert a tracked phone number, and insert a tracked form.

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Get your account's tracking assets and paste them in the appropriate field in the plugin settings.
4. The tracking code will automatically be added to your site. To insert a phone number, add this PHP to your template in the desired location on your page. get_option ('th_phone_number'). To insert a form, use get_option('th_form_html').

== Frequently asked questions ==
1. How do I get the assets to start using this plugin?
Answer: To get started with ThriveHive, you'll need to create an account with ThriveHive at http://thrivehive.com/free-trial/signup. Once you have an account, go to the account page to get your tracking code (which contains your account ID). ThriveHive will help you get your form HTML and tracked phone lines as well.
2. How do I insert the phone number and form into my pages, posts, and templates?
Answer: There are two ways to insert the forms and phone numbers. The first uses "shortcodes" which you can use in your pages and posts. Just type [th_form] or [th_phone] in a post or a page and it will pull in the appropriate asset (assuming you have set one up in the ThriveHive plugin settings page. To insert your phone number and form into your php template files, you will need to include <?php th_display_form(); ?> or <?php th_display_form(); >? in your template.



== Screenshots ==



== Changelog ==
* V 1.82 Allowing users to set category names to whatever they want
* V 1.81 Minor bug fix with displaying PDF links only
* V 1.72 Fixing social blog roll bug
* V 1.71 Fixing minor bug in new pdf properties
* V 1.7 Adding mappings so that we can move to our new PDF embedder plugin
* V 1.69 Fix for slugs switching back and forth on post update
* V 1.68 Temporary fix for plugin version check
* V 1.67
  * Added default margin to images in .content
  * Added float right to <li> elements in #menu-main
  * Adding in endpoints for getting and setting genesis layout for pages.
  * Adding in sharing for pinterest and linked in on blog pages and phone widget header editing
  * Adding in plugin versioning checks to detect version issues in TH
* V 1.65 Fix for issues with PDF uploads and poor thumbnails for them
* V 1.64 Fix for older PHP version
* V 1.63 Adding dynamic logo tweaks
* V 1.62 Fixing but with landing page template showing nav menu
* V 1.61 Fixing a bug with displaying landing pages in TH
* V 1.60 Fixing a bug introduced with pdf uploads in 1.59
* V 1.59 Fixing a bug with older versions of PHP and accessing the post controller
* V 1.58 Fixing bug with non canonical category slugs and saving posts
* V 1.57 Adding PDF management
* V 1.56 Fixing an issue with landing page template and newer versions of genesis
* V 1.55 Fixing bug with the map shortcode and <br> tags
* V 1.54 Auto-approving comments our authors make in reply to other comments
* V 1.53 Updating comment management to give the gmt date
* V 1.52 Updating get_all_users to return email addresses
* V 1.51 Updating user creation to include email address and adding method to update it
* V 1.5 Minor release changing functionalities for filtering pages by types and comment management
* V 1.40 Fix for excessive slashes in seo homepage
* V 1.39 Fix for RSS XML Feed
* V 1.38 Adding footer changes to include address
* V 1.35 Major release to fuix issues with Metro Pro theme
* V 1.28 Major release to support custom header style options
* V 1.27 Fix for YouTube video tracking
* V 1.26 Fix for overlapping forms on landing pages
* V 1.25 increasing version number to allow  update on some sites
* V 1.24 Major release supporting forms, custom css/js, lightbox, categories, authors, background * image
* V 1.23 Added comments field to default contact us form
* V 1.22 Fix for YouTube embed showing up at top of page/post
* V 1.21 Fix for Youtube tracking environment url
* V 1.20 Major release supporting new shortcodes for youtube and image gallery, LinkedIn and Yelp * widget buttons,
* V 1.10 Fixed a bug for opening blog posts in thrivehive
* V 1.09 Styling fix on contact us form generator
* V 1.08  Function naming conflict issue with a specific theme
* V 1.07 Fix to make sure menu item shows up, and shows up last
* V 1.06  Bug fix for theme page templates not showing up
* V 1.05  Bug fix for PHP Version <5.4
* V 1.04  Updating social buttons to be optional
* V 1.03  changed social buttons to be echo'd to the page rather than written directly
* V 1.02	Adding validation for current PHP version
* V 1.00  MAJOR Release integrating with the new Thrivehive wordpress interface
* V 0.59: Fixing but with getting blog post content
* V 0.58: Changing the method for getting public previews
* V 0.54: Added rewrite flushing on activation
* V 0.51: Bug fix for creating blog posts with no title having all content wiped out
* V 0.5:  Major update adding integration with ThriveHive to create and view blog posts as well as various usability enhancements


== Upgrade notice ==

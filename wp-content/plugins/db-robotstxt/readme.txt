=== DB Robots.txt ===
Contributors: bisteinoff
Donate link: https://bisteinoff.com
Tags: robots, robots.txt, crawler, google, seo
Requires at least: 4.6
Tested up to: 6.7
Stable tag: 3.12
License: GPL2

DB Robots.txt is an easy-to-use plugin for generating and configuring the file robots.txt that is essential for SEO (search engine optimization).

== Description ==

Have you encountered an obstacle while creating and editing robots.txt file on your website?

DB Robots.txt is an easy-to-use plugin for generating and configuring the file robots.txt that is essential for SEO (search engine optimization). The file should contain the rules for crawler robots of search engines such as Google, Bing, Yahoo!, Yandex, etc.

The plugin works perfectly both if the file robots.txt has never been created or if it already exists. Once installed the plugin makes an optimized robots.txt file that includes special rules common for WordPress websites. After that you can proceed further customization specific for your own website if needed.

If the plugin detects one or several Sitemap XML files it will include them into robots.txt file.

No FTP access, manual coding or file editing is required that makes managing settings easy and convenient!

== Installation ==

1. Upload bisteinoff-robots-txt folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enjoy

== Frequently Asked Questions ==

= Will it conflict with any existing robots.txt file? =

No, it will not. If the file robots.txt is found in the root folder than it will not be overriden. On the Settings page it will appear the corresponding notification and you will find two options: remove or rename the existing file robots.txt. The plugin provides the functionality.

=  Could I accidently block all search robots? =

Once the plugin is installed it will work fine for all search engine robots. If you are not aware of the rules for fine-tune of a robots.txt it is better to leave the file as is or read first a corresponding manual to learn more about the directives used for robots.txt.

Note: the following directives would block the corresponding search robot(s):

Disallow:
Disallow: /
Disallow: *
Disallow: /*
Disallow: */

You should use any of the directives only in case if you do not want any page of your website would be accessible for crawling.

=  Where I could read the up-to-date guide on robots.txt? =

* [Guide by Google](https://developers.google.com/search/docs/crawling-indexing/robots/robots_txt)
* [Guide WordPress SEO](https://wordpress.org/documentation/article/search-engine-optimization/#robots-txt-optimization)

== Changelog ==

= 3.12 =
* Compatible with Wordpress 6.7
* Rewritten the code with depricated and discouraged functions
* Security issues

= 3.11 =
* Design of the Settings page in admin panel

= 3.10 =
* Custom rules for WooCommerce if the plugin is installed and activated
* Fixing ampersand symbol

= 3.9 =
* Security issues

= 3.8 =
* Compatible with Wordpress 6.5

= 3.7 =
* Security issues

= 3.6 =
* Compatible with Wordpress 6.3
* Security issues

= 3.5 =
* Compatible with multisites

= 3.4.2 =
* Corrected errors in the functions for translation of the plugin

= 3.4.1 =
* Now the translations are automatically downloaded from https://translate.wordpress.org/projects/wp-plugins/db-robotstxt/ If there is not a translation into your language, please, don't hesitate to contribute!

= 3.4 =
* Compatible with GlotPress

= 3.3 =
* New options to rename or delete the existing robots.txt file

= 3.2 =
* New option to disable the rules for Yandex
* Design of the Settings page in admin panel

= 3.1 =
* New basic regular rules for Googlebot and Yandex
* Now more possibilities to manage your robots.txt: you can add custom rules for Googlebot, Yandex and other User-agents
* More information about your robots.txt on the settings page

= 3.0 =
* Added a settings page in admin panel for custom rules

= 2.3 =
* Tested with WordPress 6.2.
* The code is optimized
* Added the robots directives for new types of images WebP, Avif

= 2.2 =
* Fixed Sitemap option

= 2.1 =
* Tested with WordPress 5.5.
* Added wp-sitemap.xml

= 2.0 =
* Tested with WordPress 5.0.
* The old Host directive is removed, as no longer supported by Yandex.
* The robots directives are improved and updated.
* Added the robots directives, preventing indexind duplicate links with UTM, Openstat, From, GCLID, YCLID, YMCLID links 

= 1.0 =
* Initial release.

== Screenshots ==

1. The Settings Page.

2. Error message if a non-virtual file robots.txt exists and the functionality to fix it.

3. The message when the problem is fixed.
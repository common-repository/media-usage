=== Media Usage ===
Contributors: TigrouMeow
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=H2S7S3G4XMJ6J
Tags: image, image, optimization, logging, system, clean, cleaning, delete
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 0.0.4

This plugin tracks the real usage of your media and display this statistics in a simple way in the Media Library.

== Description ==

This plugin tracks the real usage of your media and display this statistics in a simple way in the Media Library. This is useful to discover which images (or sized images) aren't viewed in your WordPress at all.

In the Media Library, a new column "Usage" is added. In it, all your sized images for a given media are shown in the form of a little square with a letter assigned (T = Thumbnail, M = Medium, etc) and a color.

* Blue: Has been viewed by at least two visitors.
* Gray: Has been viewed by only one visitor.
* Black: Has never been viewed.
* Red: There is an issue with this image.

You can hover on this little square to get more information such as what it is, the number of visits and views, and also when the image was seen for the first and last time.

***IMPORTANT***. This plugin is not supposed to be run continuously forever. Your media are normally delivered statically, with this plugin they will be delivered dynamically to make logging possible. That will therefore slow down your website.

***BETA***. This plugin has just been created. Please be kind to me and help me by sharing with me your issues and your feature requests. I am here, ready to improve this beta version towards an awesome plugin.

***COMPATIBILITY***. For now, the plugin works with Apache servers. You will also need to disable your CDN or whatever caching system you are using with your images.

***FUTURE***. This is not a secret. Once the plugin will be working really well, then the Media Cleaner will use this data to clean your Media Library.

More information available one http://meowapps.com/media-usage/.

= Quickstart =

1. In your menu, click on Meow Apps > Media Usage. Enable Logging then Save Changes.
2. Visit your websites with a different computer or log off.
3. Check your Media Library (list mode), statistics are available.

== Changelog ==

= 0.0.4 =
* Fix: Many installs don't support X-Sendfile so it is now an option.
* Add: Debugging function (called Debug URL).
* Update: Internal improvement.

= 0.0.3 =
* Fix: Retina detection.
* Fix: Potential PHP notice and added a handler test in the settings.

= 0.0.1 =
* Very first release.

== Installation ==

Install it directly from WordPress > Add Plugins.

== Frequently Asked Questions ==

No questions yet.

== Upgrade Notice ==

None.

== Screenshots ==

1. Media Library

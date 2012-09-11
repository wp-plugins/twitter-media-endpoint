=== Plugin Name ===
Contributors: sterling
Donate link: http://sterlinganderson.net/twitter-media
Tags: twitter, media, image, video, mobile
Requires at least: 3.1
Tested up to: 3.4.2
Stable tag: 0.8

The Twitter Media Endpoint plugin allows you to turn your Wordpress install in to a Twitpic/YFrog like service for your mobile device.

== Description ==

Twitter Media Endpoint allows you to use your Wordpress install with your Twitter client to upload media, and host it on your site, where you maintain control and ownership.

This is an initial release, and my first WP plugin, please report back any issues.

When you use a service like Twitpic to upload media your media falls under that site's terms. You often lose most, if not all rights to your photos. With Twitter Media Endpoint you are uploading you images to you personal blog. A site you own have control over, thus you maintain control of your work.

Curious about many photo sharing sites' terms? Here are some news stories:

http://www.zdnet.com/blog/projectfailures/why-i-abandoned-twitpic-for-photo-sharing/13365
http://thenextweb.com/industry/2011/05/11/your-photos-not-so-according-to-many-popular-photo-sharing-apps/
http://boingboing.net/2011/05/12/all-your-pics-are-be.html

== Installation ==

1. Place the twitter-media-endpoint folder in the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. In your Admin section under **Media** you need to set up a Twitter application, and set an endpoint URL at a minimum.
1. Each Wordpress user must authorize the new Twitter app you created in order to use the new media endpoint in their Twitter app
1. In your Twitter application set your Custom Media Upload Service to the URL you set in your Wordpress Media Settings.

== Frequently Asked Questions ==

= What Twitter clients will this work with? =

This plugin has been test with the official Twitter app on the iPhone and iPad, and Twitterrific on the iPhone, iPad, and Mac.


== Screenshots ==

1. Wordpress Media Settings page options.
2. User Settings after user has authorized plugin.

== Changelog ==

= 0.1 =
Initial release

= 0.2 =
Found a bug if you de-authorize your Twitter app, then want to re-authorize.

= 0.3 =
Forgot to change the version number...

= 0.5 =
Removed __DIR__ contstant which is not present in PHP prior to version 5.3

== Upgrade Notice ==

= 0.1 =
Initial release

= 0.2 =
Found a bug if you de-authorize your Twitter app, then want to re-authorize.

= 0.3 =
Forgot to change the version number...

= 0.5 =
Removed __DIR__ contstant which is not present in PHP prior to version 5.3

= 0.6 =
Changed date and time of uploaded image to use date offset of WP install, not server time.

= 0.7 = 
Changed OAuth library to tmhOAuth (https://github.com/themattharris/tmhOAuth). Plugin should work with latest WP release.

=== Better WordPress External Links ===
Contributors: OddOneOut
Donate link: http://betterwp.net/wordpress-plugins/bwp-external-links/#contributions
Tags: external links, external images, external domains, external, nofollow, link-target, link-icon
Requires at least: 3.0
Tested up to: 3.9
Stable tag: 1.1.3
License: GPLv3 or later

Gives you total control over external links on your website.

== Description ==

This plugin gives you total control over external links on your website. This plugin also comes with a comprehensive domain filtering feature. BWP External Links is based on the popular Prime Links phpBB mod by Ken F. Innes IV (Prime Halo).

**Demo**

If you want to see this plugin in action, you can either visit [Better WordPress](http://betterwp.net) or [a user's website](http://wordpress.grc.nasa.gov) (NASA Glenn Research Center's Website).

**Tutorials**

Check out this [redirect external links tutorial](http://betterwp.net/wordpress-tips/redirect-external-links/) for some tips on how to set up a redirection/disclaimer page using BWP External Links.

**Some Features**

* Process links for post contents, comment text, text widgets, or the whole page
* Domain filtering:
    * You can specify which sub-domains of your website to consider local (all subdomains, no subdomain, or some)
    * You can specify which external domains to consider local
    * You can forbid certain external domains (e.g. pron.com or warez.com) and replace URLs linking to them with a URL of choice (useful for filtering links in visitors' comments)
    * Wildcard support, e.g. `*.example.com`, `*.subdomain.example.com`
* You can add `rel="external"`, `rel="nofollow"` or any custom relation tag you could think of to external links
* You can specify CSS classes for both local and external links, as well as external image links
* You can use provided CSS rules (an external icon is added to each external link) or define your own
* Choose whether or not to open a completely new window for each external link
* Choose between three 'new window' modes: `onclick` attribute, `target` attribute or jQuery
* Add a prefix to external links. Example use would be a redirection page that warns your visitors about the danger of visiting external sites, e.g. `http://yourdomain.com/out?`
* WordPress Multi-site compatible
* And more...

It is highly recommended that you give the [Official Documentation](http://betterwp.net/wordpress-plugins/bwp-external-links/#usage) a good read to make the most out of **BWP External Links**.

Please don't forget to rate this plugin [5 shining stars](http://wordpress.org/support/view/plugin-reviews/bwp-external-links?filter=5) if you like it, thanks!

**Get in touch**

* Support is provided via [BetterWP.net Community](http://betterwp.net/community/).
* You can also follow me on [Twitter](http://twitter.com/0dd0ne0ut).
* Check out [latest WordPress Tips and Ideas](http://feeds.feedburner.com/BetterWPnet) from BetterWP.net.

**Languages**

* English - default
* Danish - Danske (da_DK) - Thanks to [Randi](http://runit.nu)!
* Malaysian (ms_MY) - Thanks to [d4rkcry3r](http://d4rkcry3r.com/)!
* Dutch (nl_NL) - Thanks to Juliette!
* Serbo-Croatian (sr_RS) - Thanks to [Web Hosting Hub](http://www.webhostinghub.com)!

Please [help translate](http://betterwp.net/wordpress-tips/create-pot-file-using-poedit/) this plugin!

Visit [Plugin's Official Page](http://betterwp.net/wordpress-plugins/bwp-external-links/) for more information!

== Installation ==

1. Upload the `bwp-external-links` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the Plugins menu in WordPress. After activation, you should see a menu of this plugin under Settings.
3. Configure the plugin. 
4. Enjoy!

It is highly recommended that you give the [Official Documentation](http://betterwp.net/wordpress-plugins/bwp-external-links/#usage) a good read to make the most out of **BWP External Links**.

== Frequently Asked Questions ==

[Check plugin news and ask questions](http://betterwp.net/topic/bwp-external-links/).

== Screenshots ==

1. Plugin Functionality and Link Settings
2. Attribute and Display Settings
3. External links in action!

== Changelog ==

= 1.1.3 =
* WordPress 3.9 compatible.
* Added ability to use wildcards in domain settings.
* Added a CSS class for external image link, default is `ext-image`.
* Added a Serbo-Croatian translation - Thanks to Borisa Djuraskovic!
* Improved handling of external links.
* Updated the default 'external' image (`external.png`) to use an optimized version (download size goes down from ~50KB to 404 bytes) - Thanks to Beheerder!
* Updated Danish translation - Thanks to Randi!
* Fixed an issue where links are not processed (priorities set for some filters were too high), thanks to user **jrf**.
* Other fixes and enhancements

I have updated the [Official Documentation](http://betterwp.net/wordpress-plugins/bwp-external-links/#usage) to reflect all changes so it is highly recommended that you give it a good read.

Also check out this [redirect external links tutorial](http://betterwp.net/wordpress-tips/redirect-external-links/) for some tips on how to set up a redirection/disclaimer page using BWP External Links.

Note that BWP External Links now requires WordPress 3.0 or later.

= 1.1.2 =
* Marked as WordPress 3.7 compatible.
* Added an Italian translation. Thanks to Paolo Stivanin!
* Added a Dutch translation. Thanks to Juliette!
* Updated BWP Framework to fix a possible bug that causes BWP setting pages to go blank.
* **Good news**: ManageWP.com has become the official sponsor for BWP External Links - [Read more](http://betterwp.net/319-better-wordpress-plugins-updates-2013/).

= 1.1.1 =
* Added Malaysian translation - Thanks to d4rkcry3r!
* Added a new sample CSS file - Thanks to empathik!
* Both lowercase and uppercase anchors and href attributes are now processed correctly.
* Both single and double quotes are now recognized correctly.
* Other minor bug fixes and improvements.

**Note**: Plugin translators please update your translations, thanks!

= 1.1.0 =
* Fixed the incorrect behaviour of 'Ignoring links poiting to All sub-domains' option. It should now act like *.domain.tld.
* Improved support for country-based domains.
* Added an option to process links in text widgets.
* Added an option to process every link on a given page (experimental).
* Added Danish translation - Thanks to Randi!
For more information, please refer to the [release announcement](http://betterwp.net/231-bwp-external-links-1-1-0/).

= 1.0.0 =
* Initial Release.

== Upgrade Notice ==

= 1.0.0 =
* Enjoy the plugin!

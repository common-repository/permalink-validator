=== Plugin Name ===
Tags: iis, redirect, 301, 404, permalink, wp_rewrite_rules, isapi, isapi_rewrite, rewriterule, htaccess, mod_rewrite, seo, url, www, canonical 
Requires at least: 2.0.0
Tested up to: 2.6
Stable tag: 0.8

Validates the URL used and if not matching the official permalink then it issues a HTTP 301 or HTTP 404 message.

== Description ==

Wordpress 2.3 now includes validation of the permalink URL when using Apache web server,
but if using IIS then this plugin is still relevant.

Permalink Validator helps Search Engine Optimization (SEO) as it prevents duplicate
contents on your Wordpress blog:

* Adds trailing back-slash if missing (Can also be done with [htaccess](http://www.alistercameron.com/2007/01/12/two-wordpress-plugins-you-dont-need-and-shouldnt-use/) or [isapi_rewrite](http://cephas.net/blog/2005/07/11/trailing-slashes-iis-and-f5-big-ip/)).
* Adds or removes www prefix according to your permalink structure (Can also be done with [htaccess](http://andybeard.eu/2007/04/the-ultimate-wordpress-htaccess-file.html) or isapi_rewrite).
* Forces a correct 404 page instead of showing an empty search result when using an invalid URL
* Works only on post, pages and categories. Archives based on date (Daily, Monthly, Yearly) and search-result-pages should use noindex. Feed and trackback pages should be added to the robots.txt.
* Fixes pagination for WP on IIS.
* Fixes trailing slash for pages and categories on WP 2.2 when not having trailing slash in post permalink structure.

Wordpress it very forgiving when supplying an URL that doesn't match the
actual permalink to a post or a page. This is caused by Wordpress using some
very greedy wp\_rewrite\_rules, which accepts almost any URL as valid.

This means that multiple URLs could be used to reach the page, which search engines
sees a duplicate content and leads to penalty.

For example it will accept the following permalink URL as valid:

> http://example.com/post/hello-world/2

Even though the official URL is this:

> http://example.com/post/hello-world/

Permalink Validator makes a hook to template_redirect, and then adds some
extra validation of the URL supplied before actually calling the
theme-templates.

== Installation ==

1. Upload `permalink-validator.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Why should I use this instead of htaccess or isapi_rewrite ? =
If having the ability to use htaccess or isapi_rewrite, then one should continue
to use these to add missing trailing back-slash or www prefix. They are a lot
faster at redirecting than Permalink Validator, as they are activated without
starting the PHP script engine when the URL has incorrect format.

Permalink Validator extends the validation so besides checking the format,
then it also checks that the url is referring to something valid. The validation
of the url doesn't generate any extra overhead in database queries (unless the url requires redirection).

= Why does it redirect continously when activating the plugin ? =
The plugin makes a redirect when the url used to reach the page doesn't match the expected permalink url.
Some times this leads to an endless loop, which Firefox shows as:

> The page isn't redirecting properly <br/>
> <br/>
> Firefox has detected that the server is redirecting the request for this address in a way that will never complete. <br/>
>   * This problem can sometimes be caused by disabling or refusing to accept cookies. <br/>

This can be caused by a conflict in the htaccess / isapi_rewrite redirection rules and the given permalink structure:

* If enforcing trailing back-slash and the permalink structure doesn't include it.

If this is not the case then you are very welcome to post a topic with your problem as feedback to this plugin.
Please include your permalink-structure and Wordpress version in your description of the problem.

= How to exclude pages from being permalink validated ? =
The Permalink Validator will pretty much validate any URL you throw at it,
but sometimes there are URL's that should not be validated.

Edit the plugin-code and replace this line:

> $excludes = array();

With an array of the URL's to ignore:

> $excludes = array("/forum");

Plugins that requires an URL excluded:

* [Subscribe to Comments](http://wordpress.org/extend/plugins/subscribe-to-comments/) - Exclude "/?wp-subscription-manager"
* [wp-forum](http://wordpress.org/extend/plugins/wp-forum/) - Exclude "/forum"

= How to block search engines from spidering Wordpress feed and trackback ? =
Add the following lines to your [robots.txt](http://www.robotstxt.org/):

> Disallow: \*/feed <br/>
> Disallow: \*/atom <br/>
> Disallow: \*/rss2 <br/>
> Disallow: \*/rss <br/>
> Disallow: \*/trackback <br/>
> Disallow: /\*/feed <br/>
> Disallow: /\*/atom <br/>
> Disallow: /\*/rss2 <br/>
> Disallow: /\*/rss <br/>
> Disallow: /\*/trackback <br/>

= How to make proper permalink URL's in IIS ? =
Wordpress supports by default Apache htaccess and its mod_rewrite rules.
When using Wordpress on IIS then these will not work and one is limited
to this type of permalink:

> http://example.com/index.php/post/hello-world/

To remove the index.php, then one can either use the [custom 404 redirects](http://www.keyboardface.com/IIS-Permalinks/)
or [isapi_rewrite](http://www.basilv.com/psd/blog/2006/running-wordpress-20-under-iis).

If using isapi_rewrite and changing from using index.php to not using index.php,
then one can use the following httpd.ini (Assumes Wordpress is installed in the root):

>[ISAPI_Rewrite]<br/>
>\# Rules to ensure that normal content gets through <br/>
>RewriteRule /software-files/(.\*) /software-files/$1 [L] <br/>
>RewriteRule /images/(.\*) /images/$1 [L] <br/>
>RewriteRule /favicon.ico /favicon.ico [L] <br/>
>RewriteRule /robots.txt /robots.txt [L] <br/>
>
>\# For file-based wordpress content (i.e. theme), admin, etc. <br/>
>RewriteRule /wp-(.\*) /wp-$1 [L] <br/>
>
>\# Rule to perform 301 redirect to ensure trailing back-slash on post and pages <br/>
>RewriteCond Host: (.\*) <br/>
>RewriteRule ([^.?]+[^.?/]) http\://$1$2/ [I,R] <br/>
>
>\# Rule to perform 301 redirect (Remove index.php if specified) <br/>
>RewriteRule /index.php/(.\*) /$1 [I,RP] <br/>
>
>\# For normal wordpress content, via index.php <br/>
>RewriteRule ^/$ /index.php [L] <br/>
>RewriteRule /(.\*) /index.php/$1 [L] <br/>

= Why doesn't it redirect on IIS when the URL is wrong ? =
Permalink Validator cannot see the difference between this url:

> http://example.com/

And this url on IIS:

> http://example.com/index.php

It can also not see the difference between this url:

> http://example.com/

And this url on IIS:

> http://example.com/////

This is because REQUEST\_URI is not supported properly on IIS, and this
plugin can only simulate REQUEST\_URI to a certain limit. Therefore
it is impossible to know whether index.php or extra slashes was specified or not.
The solution is to use a rewrite engine like [ISAPI_rewrite](http://www.isapirewrite.com/)
or [IIS mod-rewrite](http://www.micronovae.com/) as they can provide a proper REQUEST\_URI.

= Why does a non existing page give HTTP error 200 on IIS  ? =
Microsoft IIS fails to reply with error code 404 in the HTTP header,
when trying to access an non existing Wordpress page. This usually happens
when using a custom 404 page on IIS.

It seems that when using PHP on IIS, then it is not possible to reply
with a proper HTTP header. Apparently the only way to return a proper
404 on IIS is to use ASP.

= Will Google tracking code work with this plugin ? =
Google tracking code adds a question-mark (?) option to the URL, which the
Permalink Validator will strip because it is not part of the permalink URL.

Instead of using a question-mark (?), then one could use a hash (#) value,
and then modify the Google tracking code to extract the hash value
instead of the question-mark value.

== Version History ==
Version 0.8

* Fixed PHP Warning: strpos() [function.strpos]: Empty delimiter
* Converted the PHP file from UTF8 to ANSI (Removed special BOM character in file beginning)

Version 0.7

* Permalink Validator will not perform redirect of POST server request

Version 0.6

* Fixed a redirection bug introduced in 0.5 when the front page is a page of posts

Version 0.5

* Wordpress 2.3 includes permalink validation (canonical urls) when using Apache, so this plugin should now only be used with IIS
* Added support for HTTPS urls
* Added support for special IIS installations where PATH_INFO and SCRIPT_NAME is the same
* Added small hack to exclude pages from validation (One is required to edit the source to use it)
* Added detection of the plugin [Jerome's Keywords](http://vapourtrails.ca/wp-keywords)

Version 0.4

* Better guessing of a proper REQUEST\_URI
* Finds the proper REQUEST\_URI when using isapi_rewrite
* Finds the proper REQUEST\_URI when using IIS Mod-Rewrite
* Improved validation as it nows expects a proper REQUEST_URI (Guess Apache is now supported)

Version 0.3

* Fixes trailing slash for pages and categories in WP 2.2 when using a post permalink structure without ending slash.

Version 0.2

* Added support for static front page.
* Added detection of integrated [BBPress](http://bbpress.org/).
* Checks that Wordpress has been started before adding hooks

Version 0.1

* Fixes REQUEST_URI for WP on IIS, which also fixes pagination.
* Handles permalink structure with or without index.php.
* Handles default permalink structure using only post-id.

== Testing Procedures ==
For each of the different permalink structures:

* Default permalink structure (Using query string)
* Permalink structure with index.php 
* Permalink structure without index.php (Using ISAPI_rewrite)
* Permalink structure ending with .html
* All the above in a sub-directory

Tries the following pages:

* Front page and paged
* Static page
* Post page
* Category page and paged
* Front page as static page (WP 2.2+)
* Front page as post page (WP 2.2+)

== Contributors ==
* [Scott Yang](http://fucoder.com/code/permalink-redirect/) for giving me the idea and the example of to how make this plugin.
* [Gabe Anderson](http://www.articulate.com/blog/) for giving me the initiative to release this plugin.
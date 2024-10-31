=== Phoenix Folding@Home Stats ===
Contributors: jamesjonesphoenix
Tags: folding, stanford, folding at home, folding@home, stats, sidebar, widget, shortcode, table
Donate link: https://phoenixweb.com.au/
Requires at least: 4.6
Tested up to: 5.7
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to display Folding@Home Stats for you or your team in a shortcode or widget.

== Description ==

This plugin retrieves stats from the [Folding@Home API](http://folding.stanford.edu/stats/api) and displays them on your WordPress Website. You can display stats for your donor account or for a Folding team. You can show the stats in your sidebar as a widget or in your content area as a shortcode. The following stats are shown:

* Work units.
* Total credit in points.
* Ranking.
* Date you completed the last work unit.
* Teams you are a member of/Donors who contribute to your team.

It takes less than a minute to setup. All you need is your donor/team ID or name. The stats are displayed as an HTML table.

= What is Folding@Home? =

Folding@home is a project run by Stanford University which allows anyone to assist with disease research by donating their unused computer processing power. Your processing power is used to simulate protein folding, a process whereby proteins assemble themselves into tools which your body can use. Joining in is a doddle; simply [download the F@H software](https://foldingathome.stanford.edu/download/) to get started. Find out more at the [Folding@Home website](https://foldingathome.stanford.edu/).

You can see an example at https://phoenixweb.com.au/display-folding-at-home-stats-wordpress-plugin/ which is also our announcement post.

== Installation ==

=Install=

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly. More detailed instructions for this step at https://phoenixweb.com.au/display-folding-at-home-stats-wordpress-plugin/
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. * To display the stats as a widget go to the Appearance->Widgets screen and add the widget 'Folding at Home' to a widget area.
   * To display the stats within a post or page, enter the post/page editing screen and add the shortcode.

=Get Donor name or Team ID=

Learn how to get your id [here](https://phoenixweb.com.au/display-folding-at-home-stats-wordpress-plugin#headingafterinstallation).

=Uninstall=

   If you uninstall this plugin, any stats the plugin recorded to your database will be deleted. No muck left behind.

== Shortcode ==

Display the shortcode in your content by writing [phoenix_folding_stats] in the post/page editing area.

* `type`             - The type of table to display. Set to either 'team' or 'donor'. Defaults to 'team'.
* `id`               - Your donor or team numerical id or name.
* `class`            - CSS class to add to the HTML table. This can help with custom styling or getting the style in line with other theme tables. Defaults to empty.
* `show_donor_teams` - On a donor table, show or hide the teams the donor is a member of. Set as 'false' or 'no' to hide. Defaults to 'yes'.
* `show_donor_teams` - On a team table, show or hide the donors that contribute to the team. Set as 'false' or 'no' to hide. Defaults to 'yes'.
* `show_id`          - Show a table row with numerical id. Defaults to 'false'.
* `show_logo`        - Show F@H arrows just after the table header. Set as 'false' or 'no' to hide. Defaults to 'yes'.
* `show_tagline`     - Show very short spiel about F@H at the bottom of the table. Set as 'false' or 'no' to hide. Defaults to 'yes'.
* `show_rank`        - Show team/donor rank.

An example of a shortcode with all these parameters in use:

`[phoenix_folding_stats type="donor" class="table table-striped" show_id="yes" show_donor_teams="yes" id="James_Jones" show_logo="no" show_tagline="yes"]`

== Making Changes ==

 - If you just want to change how something looks, the table elements are full of classes you can target with CSS. The plugin itself includes a small amount of CSS which you can easily overwrite.
 - I've sprinkled in a few WordPress filters so you can change things.
 - You can overwrite the templates in the plugin by adding your own templates in your theme. Create a folder in your theme called `phoenix-folding-at-home-stats` and add templates in there. So for example if you want to overwrite the donor table, you would create a PHP file - `wp-content/themes/yourtheme/phoenix-folding-at-home-stats/donor.php` and make your own table in there.
 - Contact me at the support forums or [our website](http://phoenixweb.com.au/contact-us/) with your desired change. If it's good I will probably add it in.

== Frequently Asked Questions ==
= Does it cost money? =

No. The plugin is free. The Folding@Home software is free. The only real cost is electricity usage while your computer processes calculations.

= Does it really make a difference? =

Yes, but we don't have nearly enough people contributing. Read [this](https://www.reddit.com/r/askscience/comments/33mx7v/has_foldinghome_really_accomplished_anything_part/), [this](https://www.reddit.com/r/askscience/comments/r93i6/has_foldinghome_really_accomplished_anything/) and [this](http://www.geek.com/news/foldinghome-actually-solves-something-1587368/).

== Screenshots ==

1. Folding@Home widget editing meta box
2. Folding stats donor content table on our website
3. Folding stats donor widget on default 2016 theme
4. Folding stats team widget on default 2016 theme
5. Folding stats donor content on default 2015 theme
6. Folding stats team widget on default 2015 theme
7. Folding stats team content table on our website

== Changelog ==
= 2.0.0 =
21/5/2021

* improved: Rewrote most of the plugin code.
* improved: You can now use your team name in the id field, along with numerical id.
* improved: Now storing data in a transient instead of a raw option. Transient has a default lifetime of 1 year.
* improved: More robust code for refreshing stats. The plugin will make an API call no less than 3 hours after the last one to avoid getting blocked by the F@H servers.
  If an API call fails, the stale stats from the database transient will be used.
  Plugin attempts to refresh stats on post update and widget update to try and avoid expensive API calls during visitor's page load.
* improved: Transients are updated on WordPress shutdown hook to avoid slowing down page from visitor's point of view.
* improved: More robust error reporting using WP_Error, errors only visible to site admins.
* improved: Table compacted if stats are missing.
* fixed: Updated API URLs from dead links to current
* removed: Can no longer display last work unit completed for team as this data is no longer available via F@H API. Still available for individual donors.

= 1.0.2 =
02/12/2016

 * fixed: enqueues style when widget and no shortcode present
 * fixed: version numbers in base plugin file added to cooperate with wordpress.org madness

= 1.0.1 =
01/12/2016

 * fixed: (top x of y) message was showing incorrectly
 * fixed: empty table row for donor table with single team
 * fixed: 'team ranking' to 'donor ranking' on donor table
 * improved: readme.txt

= 1.0 =
28/11/2016
* Initial Release

== Upgrade Notice ==
= 1.0.1 =
small bug fixes

= 1.0 =
Initial Release
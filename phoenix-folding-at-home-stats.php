<?php

/**
 * Plugin Name: Phoenix Folding At Home Stats
 * Plugin URI: https://phoenixweb.com.au/display-folding-at-home-stats-wordpress-plugin/
 * Description: This plugin allows you to display Folding@Home Stats for you or your team in a shortcode or widget.
 * Version: 2.0.0
 * Author: Phoenix Web
 * Author URI: https://phoenixweb.com.au
 * Requires at least: 4.6
 * Tested up to: 5.7
 *
 * Text Domain: ph-folding
 */

use PhoenixFAH\FoldingHomeStats;

include_once('src/FoldingHomeStats.php');

$fah = new FoldingHomeStats();
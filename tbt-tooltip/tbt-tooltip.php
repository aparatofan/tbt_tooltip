<?php
/**
 * Plugin Name: TBT Tooltip
 * Description: Inline definition tooltips for The Blue Tree English lessons. Includes an admin generator (Tools → TBT Tooltip) that turns pasted lesson text into ready-to-use tooltip HTML for a Code block.
 * Version: 1.0.0
 * Author: Mariusz Mirecki
 * Text Domain: tbt-tooltip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TBT_TOOLTIP_VERSION', '1.0.0' );
define( 'TBT_TOOLTIP_URL', plugin_dir_url( __FILE__ ) );
define( 'TBT_TOOLTIP_PATH', plugin_dir_path( __FILE__ ) );

// Load the admin generator page (Tools → TBT Tooltip).
require_once TBT_TOOLTIP_PATH . 'admin/tbt-tooltip-admin.php';

/**
 * Enqueue the frontend tooltip CSS/JS on every public page.
 *
 * Deliberately unconditional: the tooltip markup is pasted into Code
 * blocks by hand, so there is no shortcode to detect, and a post_content
 * scan would miss content stored in Divi Theme Builder layouts. Both
 * files are tiny, and loading them everywhere guarantees tooltips can
 * never silently break on a specific category/template combination —
 * the failure mode of the old Divi Hacks / Magnific Popup setup.
 *
 * Handles are unique on purpose (tbt-tooltip-css / tbt-tooltip-js) so
 * they are easy to spot in the Network tab and easy to exclude from
 * LiteSpeed Cache's Combine/Minify tuning if ever needed.
 */
function tbt_tooltip_enqueue_assets() {
    wp_enqueue_style(
        'tbt-tooltip-css',
        TBT_TOOLTIP_URL . 'assets/css/tbt-tooltip.css',
        array(),
        TBT_TOOLTIP_VERSION
    );

    wp_enqueue_script(
        'tbt-tooltip-js',
        TBT_TOOLTIP_URL . 'assets/js/tbt-tooltip.js',
        array(),
        TBT_TOOLTIP_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'tbt_tooltip_enqueue_assets' );

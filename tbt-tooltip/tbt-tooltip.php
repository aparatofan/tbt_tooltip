<?php
/**
 * Plugin Name: TBT Tooltip
 * Description: Inline definition tooltips for The Blue Tree English lessons. Includes an admin generator (TBT → TBT Tooltip) that turns pasted lesson text into ready-to-use tooltip HTML for a Code block.
 * Version: 1.0.2
 * Author: Mariusz Mirecki
 * Text Domain: tbt-tooltip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TBT_TOOLTIP_VERSION', '1.0.2' );
define( 'TBT_TOOLTIP_URL', plugin_dir_url( __FILE__ ) );
define( 'TBT_TOOLTIP_PATH', plugin_dir_path( __FILE__ ) );

// Load the admin generator page (TBT → TBT Tooltip).
require_once TBT_TOOLTIP_PATH . 'admin/tbt-tooltip-admin.php';

// Load the saved-tooltip post type + [tbt_tooltip id="…"] shortcode.
require_once TBT_TOOLTIP_PATH . 'includes/tbt-tooltip-cpt.php';

/**
 * Register this plugin on the TBT Hub Overview page.
 *
 * Placed in the main plugin file (not the admin subfile) so it loads
 * unconditionally.
 *
 * @param array $items Existing hub items.
 * @return array
 */
function tbt_tooltip_register_hub_item( $items ) {
	$items[] = array(
		'slug'        => 'edit.php?post_type=tbt_tooltip',
		'title'       => 'Tooltips',
		'description' => 'Your saved tooltip documents. Embed one in a lesson with [tbt_tooltip id="…"].',
		'capability'  => 'edit_posts',
		'url'         => admin_url( 'edit.php?post_type=tbt_tooltip' ),
	);
	return $items;
}
add_filter( 'tbt_hub_items', 'tbt_tooltip_register_hub_item' );

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

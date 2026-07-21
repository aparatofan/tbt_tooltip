<?php
/**
 * TBT Tooltip — saved tooltip documents (custom post type) and the
 * [tbt_tooltip id="…"] shortcode that renders them.
 *
 * Each tooltip document is a `tbt_tooltip` post whose post_content holds the
 * ready-made markup produced by the generator (paragraphs of
 * .tbt-tooltip-trigger / .tbt-tooltip-bubble spans). Lesson pages embed a
 * document with [tbt_tooltip id="368298"]; the shortcode outputs that post's
 * content, which the always-on frontend CSS/JS (enqueued in the main plugin
 * file) then styles and makes interactive.
 *
 * This restores the two pieces the earlier database-backed plugin provided —
 * the post type (the admin list of tooltips) and the shortcode — so the
 * tooltip documents already in the database display again without re-entry.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the tooltip-document post type.
 *
 * Lives under the TBT hub when active, otherwise its own top-level menu, so
 * the list of saved tooltips is always reachable. Not public: the documents
 * are embedded via the shortcode, never visited at their own URL, so no
 * rewrite rules (and no activation-time flush) are needed.
 */
function tbt_tooltip_register_cpt() {
    $show_in_menu = defined( 'TBT_HUB_SLUG' ) ? TBT_HUB_SLUG : true;

    register_post_type(
        'tbt_tooltip',
        array(
            'labels'          => array(
                'name'               => __( 'Tooltips', 'tbt-tooltip' ),
                'singular_name'      => __( 'Tooltip', 'tbt-tooltip' ),
                'menu_name'          => __( 'Tooltips', 'tbt-tooltip' ),
                'add_new_item'       => __( 'Add New Tooltip', 'tbt-tooltip' ),
                'edit_item'          => __( 'Edit Tooltip', 'tbt-tooltip' ),
                'new_item'           => __( 'New Tooltip', 'tbt-tooltip' ),
                'view_item'          => __( 'View Tooltip', 'tbt-tooltip' ),
                'search_items'       => __( 'Search Tooltips', 'tbt-tooltip' ),
                'not_found'          => __( 'No tooltips found.', 'tbt-tooltip' ),
                'not_found_in_trash' => __( 'No tooltips found in Trash.', 'tbt-tooltip' ),
            ),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => $show_in_menu,
            'menu_icon'       => 'dashicons-editor-help',
            'menu_position'   => 4,
            'supports'        => array( 'title', 'editor' ),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        )
    );
}
add_action( 'init', 'tbt_tooltip_register_cpt' );

/**
 * [tbt_tooltip id="123"] — output the stored markup of a tooltip document.
 *
 * Returns an empty string for a missing / non-published / wrong-type id so a
 * stale shortcode never prints as raw text on the page again.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function tbt_tooltip_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'id' => '' ), $atts, 'tbt_tooltip' );

    $id = absint( $atts['id'] );
    if ( ! $id ) {
        return '';
    }

    $post = get_post( $id );
    if ( ! $post || 'tbt_tooltip' !== $post->post_type || 'publish' !== $post->post_status ) {
        return '';
    }

    // The content is already complete tooltip markup with its own <p>
    // paragraphs, so it is output as-is — no wpautop, which would wrap the
    // already-wrapped paragraphs. do_shortcode is a harmless pass-through here
    // (there are no nested shortcodes) and future-proofs embedded shortcodes.
    return do_shortcode( $post->post_content );
}
add_shortcode( 'tbt_tooltip', 'tbt_tooltip_shortcode' );

/**
 * Add a "Shortcode" column to the tooltips list table so the embed code for
 * each document is one click away.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function tbt_tooltip_admin_columns( $columns ) {
    $columns['tbt_tooltip_shortcode'] = __( 'Shortcode', 'tbt-tooltip' );
    return $columns;
}
add_filter( 'manage_tbt_tooltip_posts_columns', 'tbt_tooltip_admin_columns' );

/**
 * Render the Shortcode column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Current row's post ID.
 */
function tbt_tooltip_admin_column_content( $column, $post_id ) {
    if ( 'tbt_tooltip_shortcode' === $column ) {
        echo '<code>[tbt_tooltip id="' . esc_attr( $post_id ) . '"]</code>';
    }
}
add_action( 'manage_tbt_tooltip_posts_custom_column', 'tbt_tooltip_admin_column_content', 10, 2 );

<?php
/**
 * TBT Tooltip — admin generator page (TBT → TBT Tooltip).
 *
 * Workflow: paste lesson text → select fragments with the mouse → type a
 * tooltip for each selected fragment → generate the final HTML → copy it
 * into a Code block in the lesson page. All logic is client-side; nothing
 * is stored in the database.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hook suffix of our admin page, captured at registration so the enqueue
 * callback can match it whether we live under the TBT hub or the fallback
 * top-level menu.
 *
 * @var string
 */
$GLOBALS['tbt_tooltip_page_hook'] = '';

/**
 * Register the generator page: under the TBT hub when active, otherwise a
 * top-level menu of its own so the tool is never unreachable.
 */
function tbt_tooltip_register_admin_page() {
    if ( defined( 'TBT_HUB_SLUG' ) ) {
        $hook = add_submenu_page(
            TBT_HUB_SLUG,
            __( 'TBT Tooltip Generator', 'tbt-tooltip' ),
            __( 'TBT Tooltip', 'tbt-tooltip' ),
            'edit_posts',
            'tbt-tooltip',
            'tbt_tooltip_render_admin_page'
        );
    } else {
        $hook = add_menu_page(
            __( 'TBT Tooltip Generator', 'tbt-tooltip' ),
            __( 'TBT Tooltip', 'tbt-tooltip' ),
            'edit_posts',
            'tbt-tooltip',
            'tbt_tooltip_render_admin_page',
            'dashicons-editor-help',
            3
        );
    }
    $GLOBALS['tbt_tooltip_page_hook'] = $hook;
}
add_action( 'admin_menu', 'tbt_tooltip_register_admin_page' );

/**
 * Enqueue the generator assets, plus the frontend CSS/JS so the live
 * preview on the page behaves exactly like a published lesson.
 *
 * @param string $hook Current admin page hook suffix.
 */
function tbt_tooltip_admin_enqueue_assets( $hook ) {
    if ( empty( $GLOBALS['tbt_tooltip_page_hook'] ) || $hook !== $GLOBALS['tbt_tooltip_page_hook'] ) {
        return;
    }

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

    wp_enqueue_style(
        'tbt-tooltip-admin-css',
        TBT_TOOLTIP_URL . 'assets/css/tbt-tooltip-admin.css',
        array( 'tbt-tooltip-css' ),
        TBT_TOOLTIP_VERSION
    );
    wp_enqueue_script(
        'tbt-tooltip-admin-js',
        TBT_TOOLTIP_URL . 'assets/js/tbt-tooltip-admin.js',
        array(),
        TBT_TOOLTIP_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'tbt_tooltip_admin_enqueue_assets' );

/**
 * Render the generator page.
 */
function tbt_tooltip_render_admin_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        return;
    }
    ?>
    <div class="wrap tbt-tt-wrap">
        <h1><?php esc_html_e( 'TBT Tooltip Generator', 'tbt-tooltip' ); ?></h1>
        <p class="tbt-tt-intro">
            <?php esc_html_e( 'Turn lesson text into tooltip HTML: paste the text, select the words that need a tooltip, write the tooltip for each one, then copy the generated HTML into a Code block on the lesson page.', 'tbt-tooltip' ); ?>
        </p>

        <div class="tbt-tt-step">
            <h2><?php esc_html_e( 'Step 1 — Paste the text', 'tbt-tooltip' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Each line becomes its own paragraph. Blank lines are ignored.', 'tbt-tooltip' ); ?></p>
            <textarea id="tbt-tt-input" rows="6" placeholder="<?php esc_attr_e( 'Paste the lesson text here…', 'tbt-tooltip' ); ?>"></textarea>
            <p>
                <button type="button" class="button button-primary" id="tbt-tt-load"><?php esc_html_e( 'Load text', 'tbt-tooltip' ); ?></button>
            </p>
        </div>

        <div class="tbt-tt-step">
            <h2><?php esc_html_e( 'Step 2 — Select the fragments', 'tbt-tooltip' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Select a word or phrase below with the mouse. Each selection is added to the list in Step 3.', 'tbt-tooltip' ); ?></p>
            <div id="tbt-tt-preview" class="tbt-tt-preview">
                <p class="tbt-tt-placeholder"><?php esc_html_e( 'Load some text in Step 1 first.', 'tbt-tooltip' ); ?></p>
            </div>
        </div>

        <div class="tbt-tt-step">
            <h2><?php esc_html_e( 'Step 3 — Write the tooltips', 'tbt-tooltip' ); ?></h2>
            <div id="tbt-tt-list" class="tbt-tt-list"></div>
            <p class="description tbt-tt-list-empty" id="tbt-tt-list-empty"><?php esc_html_e( 'No fragments selected yet.', 'tbt-tooltip' ); ?></p>
        </div>

        <div class="tbt-tt-step">
            <h2><?php esc_html_e( 'Step 4 — Generate and copy', 'tbt-tooltip' ); ?></h2>
            <p>
                <button type="button" class="button button-primary" id="tbt-tt-generate"><?php esc_html_e( 'Generate HTML', 'tbt-tooltip' ); ?></button>
                <button type="button" class="button" id="tbt-tt-copy" disabled><?php esc_html_e( 'Copy to clipboard', 'tbt-tooltip' ); ?></button>
                <span id="tbt-tt-copy-status" class="tbt-tt-copy-status" aria-live="polite"></span>
            </p>
            <textarea id="tbt-tt-output" rows="8" readonly placeholder="<?php esc_attr_e( 'Generated HTML will appear here…', 'tbt-tooltip' ); ?>"></textarea>

            <h3><?php esc_html_e( 'Live preview', 'tbt-tooltip' ); ?></h3>
            <p class="description"><?php esc_html_e( 'Hover (or click, like a tablet tap) the underlined words to test the tooltips.', 'tbt-tooltip' ); ?></p>
            <div id="tbt-tt-live" class="tbt-tt-live"></div>
        </div>
    </div>
    <?php
}

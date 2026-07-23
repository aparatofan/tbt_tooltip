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
 * Send the Tooltips "Add New" screen to the generator.
 *
 * The generator (TBT → TBT Tooltip) is now the create-and-save front door, so
 * the post type's native blank editor for *new* tooltips would be a dead end
 * (no fragment UI). Editing an existing tooltip still opens the classic editor
 * as normal — this only intercepts post-new.php for our post type.
 */
function tbt_tooltip_redirect_add_new() {
    global $pagenow;

    if ( 'post-new.php' !== $pagenow ) {
        return;
    }

    $post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing decision, no state change.
    if ( 'tbt_tooltip' !== $post_type ) {
        return;
    }

    $url = menu_page_url( 'tbt-tooltip', false );
    if ( $url ) {
        wp_safe_redirect( $url );
        exit;
    }
}
add_action( 'admin_init', 'tbt_tooltip_redirect_add_new' );

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

    // Hand the save endpoint + nonce to the admin JS so it can POST the
    // generated markup back and create a tbt_tooltip post.
    wp_localize_script(
        'tbt-tooltip-admin-js',
        'tbtTooltipSave',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'tbt_tooltip_save' ),
        )
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

            <h3><?php esc_html_e( 'Save this tooltip', 'tbt-tooltip' ); ?></h3>
            <p class="description">
                <?php esc_html_e( 'Give it a name (e.g. tp_food_4) and save it as a reusable Tooltip. You will get a shortcode to paste into the lesson.', 'tbt-tooltip' ); ?>
            </p>
            <p>
                <label for="tbt-tt-title" class="screen-reader-text"><?php esc_html_e( 'Tooltip name', 'tbt-tooltip' ); ?></label>
                <input type="text" id="tbt-tt-title" class="regular-text"
                       placeholder="<?php esc_attr_e( 'Tooltip name, e.g. tp_food_4', 'tbt-tooltip' ); ?>" disabled>
                <button type="button" class="button button-primary" id="tbt-tt-save" disabled>
                    <?php esc_html_e( 'Save as Tooltip', 'tbt-tooltip' ); ?>
                </button>
                <span id="tbt-tt-save-status" class="tbt-tt-copy-status" aria-live="polite"></span>
            </p>
            <div id="tbt-tt-save-result" style="display:none;">
                <p>
                    <label for="tbt-tt-shortcode"><strong><?php esc_html_e( 'Shortcode', 'tbt-tooltip' ); ?></strong></label><br>
                    <input type="text" id="tbt-tt-shortcode" class="regular-text code" readonly>
                    <button type="button" class="button" id="tbt-tt-shortcode-copy"><?php esc_html_e( 'Copy shortcode', 'tbt-tooltip' ); ?></button>
                    <a href="#" id="tbt-tt-edit-link" class="button" target="_blank" rel="noopener"><?php esc_html_e( 'Edit tooltip', 'tbt-tooltip' ); ?></a>
                </p>
            </div>
        </div>
    </div>
    <?php
}

add_action( 'wp_ajax_tbt_tooltip_save', 'tbt_tooltip_ajax_save' );

/**
 * Save generator output as a tbt_tooltip post and return its shortcode.
 *
 * The client sends the exact markup already built in #tbt-tt-output; we do
 * not re-serialize it. Safety comes from a targeted wp_kses allowlist that
 * keeps only the tags/attributes the generator emits — so the trigger/bubble
 * spans survive and nothing else does, regardless of the saver's
 * unfiltered_html capability.
 */
function tbt_tooltip_ajax_save() {
    check_ajax_referer( 'tbt_tooltip_save', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tbt-tooltip' ) ), 403 );
    }

    $title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
    $raw   = isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '';

    if ( '' === trim( $title ) ) {
        wp_send_json_error( array( 'message' => __( 'Please enter a name for the tooltip.', 'tbt-tooltip' ) ), 400 );
    }
    if ( '' === trim( $raw ) ) {
        wp_send_json_error( array( 'message' => __( 'Nothing to save. Generate the HTML first.', 'tbt-tooltip' ) ), 400 );
    }

    // Targeted allowlist: exactly the tags/attributes the generator emits.
    // Guarantees the trigger/bubble spans survive AND nothing else gets through,
    // regardless of the saver's unfiltered_html capability.
    $allowed = array(
        'p'    => array( 'class' => true ),
        'span' => array( 'class' => true ),
    );
    $content = wp_kses( $raw, $allowed );

    // post_status => 'publish' so the shortcode resolves immediately, matching
    // how existing tooltips are stored. Publishing needs publish_posts, which
    // admin/teacher accounts have; a lower-capability saver would have WP store
    // this as pending and the shortcode would return empty until published.
    $post_id = wp_insert_post(
        array(
            'post_type'    => 'tbt_tooltip',
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
        ),
        true
    );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( array( 'message' => $post_id->get_error_message() ), 500 );
    }

    wp_send_json_success(
        array(
            'id'        => $post_id,
            'shortcode' => '[tbt_tooltip id="' . $post_id . '"]',
            'editUrl'   => get_edit_post_link( $post_id, 'raw' ),
        )
    );
}

<?php
/**
 * Add a button to wp_editor() instances to allow easier tag insertion.
 *
 * @package     EDD
 * @subpackage  Email
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get registered emails.
 *
 * This assumes emails are "registered" by using a section in the Emails tab.
 *
 * @since 3.0
 *
 * @return array $emails Registered emails.
 */
function edd_email_tags_inserter_get_registered_emails() {
	$settings = edd_get_registered_settings();
	$emails   = $settings['emails'];

	unset( $emails['main'] );

	return array_keys( $emails );
}

/**
 * Wait until the admin has loaded (so edd_get_registered_settings() works)
 * and hook in to WordPress for each registered email.
 *
 * @since 3.0
 */
function edd_email_tags_inserter_register() {
	foreach ( edd_email_tags_inserter_get_registered_emails() as $email ) {

		// Add Thickbox button.
		add_action( 'edd_settings_tab_top_emails_' . $email, 'edd_email_tags_inserter_media_button' );

		// Output Thickbox content.
		add_action( 'edd_settings_tab_top_emails_' . $email, 'edd_email_tags_inserter_thickbox_content' );

		// Enqueue scripts.
		add_action( 'edd_settings_tab_top_emails_' . $email, 'edd_email_tags_inserter_enqueue_scripts' );
	}
}
add_action( 'admin_menu', 'edd_email_tags_inserter_register' );

/**
 * Wait until `media_buttons` action is called.
 *
 * @see edd_email_tags_inserter_media_button_output()
 *
 * @since 3.0
 */
function edd_email_tags_inserter_media_button() {
	add_action( 'media_buttons', 'edd_email_tags_inserter_media_button_output' );
}

/**
 * Adds an 'Insert Email Tag' button above the TinyMCE Editor on email-related
 * `wp_editor()` instances.
 *
 * @since 3.0
 */
function edd_email_tags_inserter_media_button_output() {
	?>
	<a href="#TB_inline?width=640&inlineId=edd-insert-email-tag" class="edd-email-tags-inserter thickbox button edd-thickbox" style="padding-left: 0.4em;">
		<span class="wp-media-buttons-icon dashicons dashicons-editor-code"></span>
		<?php esc_html_e( 'Insert Email Tag', 'easy-digital-downloads' ); ?>
	</a>
	<?php
}

/**
 * Enqueue scripts for clicking a tag inside of Thickbox.
 *
 * @since 3.0
 */
function edd_email_tags_inserter_enqueue_scripts() {

	// Use minified libraries if SCRIPT_DEBUG is turned off
	$css_suffix  = is_rtl() ? '-rtl' : '';
	$css_suffix .= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	$js_suffix   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	wp_register_style( 'edd-tags-media-button', EDD_PLUGIN_URL . 'assets/css/edd-admin-email-tags' . $css_suffix . '.css' );
	wp_register_script( 'edd-tags-media-button', EDD_PLUGIN_URL . 'assets/js/edd-admin-email-tags' . $js_suffix . '.js', array( 'thickbox', 'wp-util' ) );

	wp_enqueue_style( 'edd-tags-media-button' );
	wp_enqueue_script( 'edd-tags-media-button') ;

	// Send information about tags to script.
	$items = array();
	$tags  = edd_get_email_tags();

	foreach ( $tags as $tag ) {
		$items[] = array(
			'title'    => $tag['label'] ? $tag['label'] : $tag['tag'],
			'tag'      => $tag['tag'],
			'keywords' => array_merge(
				explode( ' ', $tag['description'] ),
				array( $tag['tag'] )
			),
		);
	}

	wp_localize_script(
		'edd-tags-media-button',
		'eddEmailTagsInserter',
		array(
			'items' => $items,
		)
	);
}

/**
 * Output Thickbox content.
 *
 * @since 3.0
 */
function edd_email_tags_inserter_thickbox_content() {
	$tags = edd_get_email_tags();
	?>
	<div id="edd-insert-email-tag" style="display: none;">
		<div class="edd-email-tags-filter">
			<input type="search" class="edd-email-tags-filter-search" placeholder="<?php echo esc_attr( __( 'Find a tag...', 'easy-digital-downloads' ) ); ?>" />
		</div>

		<ul class="edd-email-tags-list">
			<?php foreach ( $tags as $tag ) : ?>
			<li id="<?php echo esc_attr( $tag['tag'] ); ?>" data-tag="<?php echo esc_attr( $tag['tag'] ); ?>" class="edd-email-tags-list-item">
				<button class="edd-email-tags-list-button" data-to_insert="{<?php echo esc_attr( $tag['tag'] ); ?>}">
					<strong><?php echo esc_html( $tag['label'] ?: '{' . $tag['tag'] . '}' ); ?></strong>
					<span><?php echo esc_html( $tag['description'] ); ?></span>
				</button>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

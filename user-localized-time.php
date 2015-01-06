<?php
/**
 * Plugin Name: User Localized Time
 * Description: Localize times in the admin for users.
 * Version: 0.1.0
 * Author: Frankie Jarrett
 * Author URI: http://frankiejarrett.com
 * License: GPLv2+
 * Text Domain: user-localized-time
 */

/**
 * Get a user's timezone string
 *
 * $user_id int  Pass a user ID, defaults to the current user's ID
 *
 * @return string
 */
function ult_get_timezone_string( $user_id = null ) {
	$user_id          = is_null( $user_id ) ? get_current_user_id() : absint( $user_id );
	$default_timezone = get_option( 'timezone_string', 'UTC' );
	$user_timezone    = get_the_author_meta( 'ult_timezone_string', $user_id );
	$timezone         = ! empty( $user_timezone ) ? $user_timezone : $default_timezone;

	return $timezone;
}

/**
 * Display custom user meta field
 *
 * @action show_user_profile
 * @action edit_user_profile
 *
 * @return void
 */
function ult_timezone_string_field( $user ) {
	$timezone = ult_get_timezone_string( $user->ID );
	?>
	<table class="form-table">
		<tr>
			<th>
				<label for="ult_timezone_string"><?php _e( 'Timezone', 'user-localized-time' ) ?></label>
			</th>
			<td>
				<select id="ult_timezone_string" name="ult_timezone_string" class="form-control">
					<?php echo wp_timezone_choice( $timezone ) ?>
				</select>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'ult_timezone_string_field', 10, 1 );
add_action( 'edit_user_profile', 'ult_timezone_string_field', 10, 1 );

/**
 * Save custom user meta field
 *
 * @action personal_options_update
 * @action edit_user_profile_update
 *
 * @return void
 */
function ult_save_timezone_string_field( $user_id ) {
	update_user_meta( $user_id, 'ult_timezone_string', sanitize_text_field( $_POST['ult_timezone_string'] ) );
}
add_action( 'personal_options_update', 'ult_save_timezone_string_field', 10, 1 );
add_action( 'edit_user_profile_update', 'ult_save_timezone_string_field', 10, 1 );

/**
 * Modify the DOM on post screens
 *
 * @action admin_footer-post.php
 *
 * @return void
 */
function ult_post_screen_js() {
	global $post;

	if ( 'publish' !== $post->post_status ) {
		return;
	}

	$timezone = ult_get_timezone_string( $user->ID );
	$date     = new DateTime( $post->post_date_gmt );

	$date->setTimezone( new DateTimeZone( $timezone ) );

	$localized_time = $date->format( 'M j, Y @ H:i T' );
	?>
	<script>
	jQuery( document ).ready( function( $ ) {
		$( '#timestamp b' ).text( '<?php echo esc_html( $localized_time ) ?>' );
	});
	</script>
	<?php
}
add_action( 'admin_footer-post.php', 'ult_post_screen_js' );

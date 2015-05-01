<?php
/**
 * Plugin Name: User Localized Time
 * Description: Localize times in the admin to prefer the user's timezone.
 * Version: 0.1.0
 * Author: Frankie Jarrett
 * Author URI: http://frankiejarrett.com
 * License: GPLv3
 * Text Domain: user-localized-time
 */

/**
 * Define plugin constants
 */
define( 'USER_LOCALIZED_TIME_VERSION', '0.1.0' );
define( 'USER_LOCALIZED_TIME_PLUGIN', plugin_basename( __FILE__ ) );
define( 'USER_LOCALIZED_TIME_DIR', plugin_dir_path( __FILE__ ) );
define( 'USER_LOCALIZED_TIME_URL', plugin_dir_url( __FILE__ ) );
define( 'USER_LOCALIZED_TIME_LANG_PATH', dirname( USER_LOCALIZED_TIME_PLUGIN ) . '/languages' );

/**
 * Get timezone string from the session cookie of the current user
 *
 * @param int $user_id (optional)
 *
 * @return string|bool
 */
function ult_get_current_user_timezone_cookie() {
	return empty( $_COOKIE['ult_timezone_string'] ) ? false : (string) $_COOKIE['ult_timezone_string'];
}

/**
 * Get user timezone string
 *
 * @param int $user_id (optional)
 *
 * @return string
 */
function ult_get_user_timezone_string( $user_id = null ) {
	$user_id = is_numeric( $user_id ) ? absint( $user_id ) : get_current_user_id();
	$cookie  = ult_get_current_user_timezone_cookie();
	$default = empty( $cookie ) ? ult_get_site_timezone_string() : $cookie;

	if ( ! get_userdata( $user_id ) ) {
		return $default;
	}

	$user_tz  = get_user_meta( $user_id, 'ult_timezone_string', true );
	$timezone = empty( $user_tz ) ? $default : $user_tz;

	return (string) $timezone;
}

/**
 * Get the unfiltered site timezone string
 *
 * @return string
 */
function ult_get_site_timezone_string() {
	$timezone = get_option( 'timezone_string', 'UTC' );

	return (string) $timezone;
}

/**
 * Returns true if the current user has a custom timezone, otherwise false
 *
 * @param int $user_id (optional)
 *
 * @return bool
 */
function ult_user_has_timezone() {
	$user_tz = ult_get_user_timezone_string();
	$site_tz = ult_get_site_timezone_string();

	return ( $user_tz && $user_tz !== $site_tz );
}

/**
 * Get the offset in seconds between a timezone and UTC
 *
 * @param string $timezone
 *
 * @return int
 */
function ult_get_timezone_offset( $timezone ) {
	$utc_tz   = new DateTimeZone( 'UTC' );
	$other_tz = new DateTimeZone( $timezone );

	$utc_date   = new DateTime( 'now', $utc_tz );
	$other_date = new DateTime( 'now', $other_tz );

	$offset = $other_tz->getOffset( $other_date ) - $utc_tz->getOffset( $utc_date );

	return (int) $offset;
}

/**
 * Display custom user meta field
 *
 * @action show_user_profile
 * @action edit_user_profile
 *
 * @param object $user
 *
 * @return void
 */
function ult_timezone_string_field( $user ) {
	$timezone = ult_get_user_timezone_string( $user->ID );
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
add_action( 'show_user_profile', 'ult_timezone_string_field' );
add_action( 'edit_user_profile', 'ult_timezone_string_field' );

/**
 * Save custom user meta field
 *
 * @action personal_options_update
 * @action edit_user_profile_update
 *
 * @param int $user_id
 *
 * @return void
 */
function ult_save_timezone_string_field( $user_id ) {
	update_user_meta( $user_id, 'ult_timezone_string', sanitize_text_field( $_POST['ult_timezone_string'] ) );
}
add_action( 'personal_options_update', 'ult_save_timezone_string_field' );
add_action( 'edit_user_profile_update', 'ult_save_timezone_string_field' );

/**
 * Register custom scripts
 *
 * @action init
 *
 * @return void
 */
function ult_register_scripts() {
	wp_register_script( 'ult-jstz', USER_LOCALIZED_TIME_URL . 'js/jstz.min.js', array(), '1.0.5', true );
	wp_register_script( 'ult-cookie', USER_LOCALIZED_TIME_URL . 'js/cookie.min.js', array( 'ult-jstz' ), USER_LOCALIZED_TIME_VERSION, true );
	wp_register_script( 'ult-admin', USER_LOCALIZED_TIME_URL . 'js/admin.js', array( 'jquery' ), USER_LOCALIZED_TIME_VERSION, true );
	wp_register_script( 'ult-edit-post', USER_LOCALIZED_TIME_URL . 'js/edit-post.js', array( 'jquery' ), USER_LOCALIZED_TIME_VERSION, true );
	wp_register_script( 'ult-edit-comment', USER_LOCALIZED_TIME_URL . 'js/edit-comment.js', array( 'jquery' ), USER_LOCALIZED_TIME_VERSION, true );

	//echo get_option( 'gmt_offset' );die();
	//echo get_option( 'timezone_string' );die();

	//echo current_time( 'c' ); die();
}
add_action( 'init', 'ult_register_scripts' );

/**
 * Enqueue cookie script everywhere
 *
 * @action wp_enqueue_scripts
 * @action admin_enqueue_scripts
 *
 * @return void
 */
function ult_enqueue_scripts() {
	wp_enqueue_script( 'ult-jstz' );
	wp_enqueue_script( 'ult-cookie' );

	/**
	 * Set the TTL (in hours) for localized time cookies, 24 hours by default
	 *
	 * @param WP_User $user
	 * @param bool    $is_admin
	 *
	 * @return int
	 */
	$cookie_ttl = apply_filters( 'ult_cookie_ttl', 24, wp_get_current_user(), is_admin() );

	wp_localize_script(
		'ult-cookie',
		'ult_cookie',
		array(
			'ttl' => absint( $cookie_ttl ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ult_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'ult_enqueue_scripts' );




function ult_pre_option_gmt_offset( $value ) {
	$seconds = ult_get_timezone_offset( ult_get_user_timezone_string() );
	$hours   = $seconds / 60 / 60;
	$value   = $hours;

	return $value;
}
//add_filter( 'pre_option_gmt_offset', 'ult_pre_option_gmt_offset' );



function ult_pre_option_timezone_string( $value ) {
	return ult_get_user_timezone_string();
}
//add_filter( 'pre_option_timezone_string', 'ult_pre_option_timezone_string' );



/**
 * Filter localized dates to prefer the user timezone
 *
 * @filter date_i18n
 *
 * @param string $j
 * @param string $req_format
 * @param int    $i
 * @param bool   $gmt
 *
 * @return string
 */
function ult_date_i18n( $j, $req_format, $i, $gmt ) {
	if ( $gmt || is_admin() || ! ult_user_has_timezone() ) {
		return $j;
	}

	$j = ult_convert_locale( 'site', 'user', $i, $req_format );

	return $j;
}
//add_filter( 'date_i18n', 'ult_date_i18n', 10, 4 );

/**
 * Filter post times to prefer the user timezone
 *
 * @filter get_post_time
 *
 * @param string|int $time
 * @param string     $d
 * @param bool       $gmt
 *
 * @return string
 */
function ult_get_post_time( $time, $d, $gmt ) {
	if ( $gmt || ! ult_user_has_timezone() ) {
		return $time;
	}

	$time = ( 'U' === $d ) ? $time : strtotime( $time );
	$time = ult_convert_locale( 'site', 'user', $time, $d );

	return $time;
}
//add_filter( 'get_post_time', 'ult_get_post_time', 10, 3 );



/**
 * Enqueue admin-only scripts
 *
 * @action admin_enqueue_scripts
 *
 * @return void
 */
function ult_admin_enqueue_scripts( $hook ) {
	wp_enqueue_script( 'ult-admin' );

	$user_meta = get_user_meta( get_current_user_id(), 'ult_timezone_string', true );
	$source    = ! empty( $user_meta ) ? 'user_meta' : ( isset( $_COOKIE['ult_timezone_string'] ) ? 'cookie' : 'site' );

	$timezone_string = ult_get_user_timezone_string();
	$timezone_offset = ult_get_timezone_offset( $timezone_string );
	$timezone_abbr   = ult_get_timezone_abbr_from_string( $timezone_string );
	$timezone        = str_replace( array( '_', '/' ), array( ' ', ' / ' ), $timezone_string );
	$timestamp       = ult_convert_locale( 'gmt', 'user', time(), 'U' );

	wp_localize_script(
		'ult-admin',
		'ult_admin',
		array(
			'source'          => esc_js( $source ),
			'timezone_string' => esc_js( $timezone_string ),
			'timezone_offset' => intval( $timezone_offset ),
			'timezone_abbr'   => esc_js( $timezone_abbr ),
			'timezone'        => esc_js( $timezone ),
			'cur'             => array(
				'mm' => esc_js( date( 'm', $timestamp ) ),
				'jj' => esc_js( date( 'j', $timestamp ) ),
				'aa' => esc_js( date( 'Y', $timestamp ) ),
				'hh' => esc_js( date( 'H', $timestamp ) ),
				'mn' => esc_js( date( 'i', $timestamp ) ),
			),
		)
	);

	if ( 'post.php' === $hook ) {
		wp_enqueue_script( 'ult-edit-post' );

		global $post;

		$timestamp = strtotime( $post->post_date );

		wp_localize_script(
			'ult-edit-post',
			'ult_edit_post',
			array(
				'date' => array(
					'mm' => esc_js( date( 'm', $timestamp ) ),
					'jj' => esc_js( date( 'j', $timestamp ) ),
					'aa' => esc_js( date( 'Y', $timestamp ) ),
					'hh' => esc_js( date( 'H', $timestamp ) ),
					'mn' => esc_js( date( 'i', $timestamp ) ),
				),
			)
		);
	}

	if ( 'comment.php' === $hook && isset( $_GET['c'] ) ) {
		wp_enqueue_script( 'ult-edit-comment' );

		$comment_id = absint( $_GET['c'] );
		$comment    = get_comment( $comment_id );
		$timestamp  = strtotime( $comment->comment_date );

		wp_localize_script(
			'ult-edit-comment',
			'ult_edit_comment',
			array(
				'date' => array(
					'mm' => esc_js( date( 'm', $timestamp ) ),
					'jj' => esc_js( date( 'j', $timestamp ) ),
					'aa' => esc_js( date( 'Y', $timestamp ) ),
					'hh' => esc_js( date( 'H', $timestamp ) ),
					'mn' => esc_js( date( 'i', $timestamp ) ),
				),
			)
		);
	}
}
//add_action( 'admin_enqueue_scripts', 'ult_admin_enqueue_scripts' );

/**
 * Modify post object dates to prefer the user locale
 *
 * @action the_post
 *
 * @param WP_Post $post
 *
 * @return void
 */
function ult_the_post( $post ) {
	if ( ! ult_user_has_timezone() ) {
		return;
	}

	$post->post_date     = ult_convert_locale( 'site', 'user', strtotime( $post->post_date ), 'Y-m-d H:i:s' );
	$post->post_modified = ult_convert_locale( 'site', 'user', strtotime( $post->post_modified ), 'Y-m-d H:i:s' );
}
//add_action( 'the_post', 'ult_the_post', 99 );

/**
 * Modify the date during post edit to prefer the user locale
 *
 * @filter date_edit_pre
 *
 * @param string $date
 * @param int    $post_id
 *
 * @return string
 */
function ult_date_edit_pre( $date, $post_id ) {
	if ( ! ult_user_has_timezone() ) {
		return $date;
	}

	$date = ult_convert_locale( 'site', 'user', strtotime( $date ), 'Y-m-d H:i:s' );

	return $date;
}
//add_filter( 'date_edit_pre', 'ult_date_edit_pre', 10, 2 );








/**
 * Modify comment object dates to prefer the user locale
 *
 * @filter get_comment
 *
 * @param WP_Comment $comment
 *
 * @return WP_Comment
 */
function ult_get_comment( $comment ) {
	if ( ! ult_user_has_timezone() ) {
		return $comment;
	}

	$comment->comment_date = ult_convert_locale( 'site', 'user', strtotime( $comment->comment_date ), 'Y-m-d H:i:s' );

	return $comment;
}
//add_filter( 'get_comment', 'ult_get_comment', 99 );

/**
 * Convert Unix timestamps to/from various locales
 *
 * @param string $from
 * @param string $to
 * @param int    $time
 * @param string $format (optional)
 *
 * @return string
 */
function ult_convert_locale( $from, $to, $time, $format = 'U' ) {
	// Validate Unix timestamp
	if ( ! is_int( $time ) || $time > PHP_INT_MAX || $time < ~PHP_INT_MAX ) {
		return;
	}

	// Calc "from" offset
	$from = ( 'site' === $from ) ? ult_get_site_timezone_string() : ( ( 'user' === $from ) ? ult_get_user_timezone_string() : 'GMT' );
	$from = ult_get_timezone_offset( $from );

	// Calc "to" offset
	$to = ( 'site' === $to ) ? ult_get_site_timezone_string() : ( ( 'user' === $to ) ? ult_get_user_timezone_string() : 'GMT' );
	$to = ult_get_timezone_offset( $to );

	// Calc GMT time using "from" offset
	$gmt = $time - $from;

	// Calc final date string using "to" offset
	$date = date( $format, $gmt + $to );

	return (string) $date;
}

/**
 * Returns the timezone abbreviation from a timezone string
 *
 * @param string $timezone_string
 *
 * @return string
 */
function ult_get_timezone_abbr_from_string( $timezone_string ) {
	$dateTime = new DateTime();

	$dateTime->setTimeZone( new DateTimeZone( $timezone_string ) );

	return (string) $dateTime->format( 'T' );
}

/**
 * Modify post dates to prefer site timezone before saving
 *
 * @filter wp_insert_post_data
 *
 * @param array $data
 * @param array $postarr
 *
 * @return array
 */
function ult_wp_insert_post_data( $data, $postarr ) {
	if ( ! ult_user_has_timezone() ) {
		return $data;
	}

	$post_date     = strtotime( $data['post_date'] );
	$post_modified = strtotime( $data['post_modified'] );
	$format        = 'Y-m-d H:i:s';

	$data['post_date']         = ult_convert_locale( 'user', 'site', $post_date, $format );
	$data['post_date_gmt']     = ult_convert_locale( 'user', 'gmt', $post_date, $format );
	$data['post_modified']     = ult_convert_locale( 'user', 'site', $post_modified, $format );
	$data['post_modified_gmt'] = ult_convert_locale( 'user', 'gmt', $post_modified, $format );

	return $data;
}
//add_filter( 'wp_insert_post_data', 'ult_wp_insert_post_data', 10, 2 );

/**
 * Modify comment date after edits to prefer site timezone
 *
 * Unfortunately, WordPress does not have any hooks available
 * for modifying the comment date data before update, so we have
 * no choice but to update again it immediately afterwards, also
 * being careful to remove/add the action around the subsequent
 * update to prevent an infinite loop.
 *
 * @action edit_comment
 *
 * @param int $comment_ID
 *
 * @return void
 */
function ult_edit_comment( $comment_ID ) {
	if ( ! ult_user_has_timezone() ) {
		return;
	}

	$data         = get_comment( $comment_ID, ARRAY_A );
	$comment_date = strtotime( $data['comment_date'] );
	$format       = 'Y-m-d H:i:s';

	$args = array(
		'comment_ID'       => $comment_ID,
		'comment_date'     => ult_convert_locale( 'user', 'site', $comment_date, $format ),
		'comment_date_gmt' => ult_convert_locale( 'user', 'gmt', $comment_date, $format ),
	);

	remove_action( 'edit_comment', __FUNCTION__ );

	wp_update_comment( $args );

	add_action( 'edit_comment', __FUNCTION__ );
}
//add_action( 'edit_comment', 'ult_edit_comment' );

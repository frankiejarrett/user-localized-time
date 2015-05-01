<?php
/**
 * Plugin Name: User Localized Time
 * Description: Localize dates and times for users on the frontend of your website.
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
 * Register custom scripts
 *
 * @action init
 *
 * @return void
 */
function ult_register_scripts() {
	wp_register_script( 'ult-jstz', USER_LOCALIZED_TIME_URL . 'lib/js/jstz.min.js', array(), '1.0.5', true );
	wp_register_script( 'ult-cookie', USER_LOCALIZED_TIME_URL . 'lib/js/cookie.min.js', array( 'ult-jstz' ), USER_LOCALIZED_TIME_VERSION, true );
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
	if ( is_admin() || $gmt || ! ult_user_has_timezone() ) {
		return $j;
	}

	$j = ult_convert_locale( 'site', 'user', $i, $req_format );

	return $j;
}
add_filter( 'date_i18n', 'ult_date_i18n', 10, 4 );

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
	if ( is_admin() || $gmt || ! ult_user_has_timezone() ) {
		return $time;
	}

	$time = ( 'U' === $d ) ? $time : strtotime( $time );
	$time = ult_convert_locale( 'site', 'user', $time, $d );

	return $time;
}
add_filter( 'get_post_time', 'ult_get_post_time', 10, 3 );

/**
 * Filter post dates to prefer the user timezone
 *
 * @filter get_the_date
 *
 * @param string  $the_date
 * @param string  $d
 * @param WP_Post $post
 *
 * @return string
 */
function ult_get_the_date( $the_date, $d, $post ) {
	if ( is_admin() || ! ult_user_has_timezone() ) {
		return $the_date;
	}

	$d        = empty( $d ) ? get_option( 'date_format' ) : $d;
	$the_date = strtotime( $post->post_date );
	$the_date = ult_convert_locale( 'site', 'user', $the_date, $d );

	return $the_date;
}
add_filter( 'get_the_date', 'ult_get_the_date', 10, 3 );

/**
 * Filter post modified times to prefer the user timezone
 *
 * @filter get_post_modified_time
 *
 * @param string|int $time
 * @param string     $d
 * @param bool       $gmt
 *
 * @return string
 */
function ult_get_post_modified_time( $time, $d, $gmt ) {
	if ( is_admin() || $gmt || ! ult_user_has_timezone() ) {
		return $time;
	}

	$time = ( 'U' === $d ) ? $time : strtotime( $time );
	$time = ult_convert_locale( 'site', 'user', $time, $d );

	return $time;
}
add_filter( 'get_post_modified_time', 'ult_get_post_modified_time', 10, 3 );

/**
 * Filter comment times to prefer the user timezone
 *
 * @filter get_comment_time
 *
 * @param string|int $date
 * @param string     $d
 * @param bool       $gmt
 * @param bool       $translate
 * @param WP_Comment $comment
 *
 * @return string
 */
function ult_get_comment_time( $date, $d, $gmt, $translate, $comment ) {
	if ( is_admin() || $gmt || ! ult_user_has_timezone() ) {
		return $date;
	}

	$d    = empty( $d ) ? get_option( 'time_format' ) : $d;
	$date = strtotime( $comment->comment_date_gmt );
	$date = ult_convert_locale( 'gmt', 'user', $date, $d );

	return $date;
}
add_filter( 'get_comment_time', 'ult_get_comment_time', 10, 5 );

/**
 * Filter comment dates to prefer the user timezone
 *
 * @filter get_comment_date
 *
 * @param string|int $date
 * @param string     $d
 * @param WP_Comment $comment_id
 *
 * @return string
 */
function ult_get_comment_date( $date, $d, $comment ) {
	if ( is_admin() || ! ult_user_has_timezone() ) {
		return $date;
	}

	$d    = empty( $d ) ? get_option( 'date_format' ) : $d;
	$date = strtotime( $comment->comment_date_gmt );
	$date = ult_convert_locale( 'gmt', 'user', $date, $d );

	return $date;
}
add_filter( 'get_comment_date', 'ult_get_comment_date', 10, 3 );

/* globals jstz, ult_cookie */

var ult_ttl = ( 'undefined' === typeof ult_cookie.ttl || ! ult_cookie.ttl ) ? 24 : ult_cookie.ttl;

ultSetCookie( ult_ttl );

function ultSetCookie( ttl ) {
	var d = new Date();

	d.setTime( d.getTime() + ( parseInt( ttl, 10 ) * 3600 * 1000 ) );
	document.cookie = 'ult_timezone_string=' + jstz.determine().name() + '; expires=' + d.toUTCString() + '; path=/';
}

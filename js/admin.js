/* globals adminpage, ult_admin */

jQuery( document ).ready( function( $ ) {
	if ( 'edit-php' === adminpage ) {
		$( '.timestamp-wrap' ).append( ' <strong>' + ult_admin.timezone_abbr + '</strong>' );
	} else if ( 'post-php' === adminpage || 'comment-php' === adminpage ) {
		$( '#timestamp b' ).append( ' ' + ult_admin.timezone_abbr );
	}
});

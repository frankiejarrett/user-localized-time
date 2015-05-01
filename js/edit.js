/* globals ult_admin */

jQuery( document ).ready( function( $ ) {
	if ( 'edit.php' === ult_admin.screen ) {
		$( '.timestamp-wrap' ).append( ' <strong>' + ult_admin.timezone_abbr + '</strong>' );

		return false;
	}

	$( '#timestamp b' ).append( ' ' + ult_admin.timezone_abbr );
	$( '#mm' ).add( '#hidden_mm' ).val( ult_admin.date.mm );
	$( '#jj' ).add( '#hidden_jj' ).val( ult_admin.date.jj );
	$( '#aa' ).add( '#hidden_aa' ).val( ult_admin.date.aa );
	$( '#hh' ).add( '#hidden_hh' ).val( ult_admin.date.hh );
	$( '#mn' ).add( '#hidden_mn' ).val( ult_admin.date.mn );
	$( '#cur_mm' ).val( ult_admin.curr.mm );
	$( '#cur_jj' ).val( ult_admin.curr.jj );
	$( '#cur_aa' ).val( ult_admin.curr.aa );
	$( '#cur_hh' ).val( ult_admin.curr.hh );
	$( '#cur_mn' ).val( ult_admin.curr.mn );
});

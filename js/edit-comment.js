/* globals ult_admin, ult_edit_comment */

jQuery( document ).ready( function( $ ) {
	$( '#mm option' ).attr( 'selected', false );
	$( '#mm option' ).filter( function() {
		return ( ult_edit_comment.date.mm === $( this ).val() );
	}).attr( 'selected', true );
	$( '#jj' ).add( '#hidden_jj' ).attr( 'value', ult_edit_comment.date.jj );
	$( '#aa' ).add( '#hidden_aa' ).attr( 'value', ult_edit_comment.date.aa );
	$( '#hh' ).add( '#hidden_hh' ).attr( 'value', ult_edit_comment.date.hh );
	$( '#mn' ).add( '#hidden_mn' ).attr( 'value', ult_edit_comment.date.mn );
	$( '#cur_mm' ).attr( 'value', ult_admin.cur.mm );
	$( '#cur_jj' ).attr( 'value', ult_admin.cur.jj );
	$( '#cur_aa' ).attr( 'value', ult_admin.cur.aa );
	$( '#cur_hh' ).attr( 'value', ult_admin.cur.hh );
	$( '#cur_mn' ).attr( 'value', ult_admin.cur.mn );
});

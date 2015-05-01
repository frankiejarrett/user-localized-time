/* globals ult_admin, ult_edit_post */

jQuery( document ).ready( function( $ ) {
	$( '#mm option' ).attr( 'selected', false );
	$( '#mm option' ).filter( function() {
		return ( ult_edit_post.date.mm === $( this ).val() );
	}).attr( 'selected', true );
	$( '#jj' ).attr( 'value', ult_edit_post.date.jj );
	$( '#aa' ).attr( 'value', ult_edit_post.date.aa );
	$( '#hh' ).attr( 'value', ult_edit_post.date.hh );
	$( '#mn' ).attr( 'value', ult_edit_post.date.mn );

	$( '#hidden_hh' ).attr( 'value', '11' );
});

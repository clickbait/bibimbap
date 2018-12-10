jQuery( document ).ready( function( $ ) {
	console.log( ':)' );

	$('a.icon.search').on( 'click', function(e) {
		e.preventDefault();

		$('body').addClass( 'search-bar-active' );

		return false;
	});

	$('body').on( 'click', function(e) {
		if( e.target != this )
			return false;

		$('body').removeClass( 'search-bar-active' );
	});

	$(document).keyup( function(e) {
		if (e.keyCode == 27) {
			$('body').removeClass( 'search-bar-active' );
		}
	});
});

( function () {
	'use strict';

	document.addEventListener( 'submit', function ( event ) {
		var confirmMessage = event.target.querySelector( '[data-alynt-confirm]' );

		if ( confirmMessage && ! window.confirm( confirmMessage.getAttribute( 'data-alynt-confirm' ) ) ) {
			event.preventDefault();
			return;
		}

		var button = event.target.querySelector( 'button[type="submit"]' );

		if ( ! button ) {
			return;
		}

		button.disabled = true;
		button.setAttribute( 'aria-busy', 'true' );
	}, true );
}() );

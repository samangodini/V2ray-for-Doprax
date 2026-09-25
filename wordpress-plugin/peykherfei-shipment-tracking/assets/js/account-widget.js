(function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var roots = document.querySelectorAll( '.pkst-acct' );
		if ( ! roots.length ) {
			return;
		}

		var instances = Array.prototype.map.call( roots, function ( root ) {
			return {
				root: root,
				trigger: root.querySelector( '.pkst-acct-trigger' ),
				panel: root.querySelector( '.pkst-acct-panel' ),
			};
		} ).filter( function ( i ) { return i.trigger && i.panel; } );

		function closeAll( except ) {
			instances.forEach( function ( i ) {
				if ( i === except ) {
					return;
				}
				close( i );
			} );
		}

		function open( i ) {
			closeAll( i );
			i.root.classList.add( 'is-open' );
			i.trigger.setAttribute( 'aria-expanded', 'true' );
			i.panel.setAttribute( 'aria-hidden', 'false' );
		}

		function close( i ) {
			i.root.classList.remove( 'is-open' );
			i.trigger.setAttribute( 'aria-expanded', 'false' );
			i.panel.setAttribute( 'aria-hidden', 'true' );
		}

		function isOpen( i ) {
			return i.root.classList.contains( 'is-open' );
		}

		instances.forEach( function ( i ) {
			i.trigger.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				isOpen( i ) ? close( i ) : open( i );
			} );
		} );

		document.addEventListener( 'click', function ( e ) {
			instances.forEach( function ( i ) {
				if ( isOpen( i ) && ! i.root.contains( e.target ) ) {
					close( i );
				}
			} );
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' !== e.key ) {
				return;
			}
			instances.forEach( function ( i ) {
				if ( isOpen( i ) ) {
					close( i );
					i.trigger.focus();
				}
			} );
		} );
	} );
})();

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

		instances.forEach( initModals );

		/**
		 * The login/register popup -- separate from the small dropdown
		 * above (which stays open behind it visually closed). Scoped per
		 * .pkst-acct root, same as the dropdown, so multiple copies of the
		 * widget on one page (e.g. a separate mobile header) don't clash.
		 */
		function initModals( i ) {
			var root     = i.root;
			var overlay  = root.querySelector( '[data-pkst-overlay]' );
			var modals   = root.querySelectorAll( '[data-pkst-modal]' );
			var openers  = root.querySelectorAll( '[data-pkst-open-modal]' );
			if ( ! overlay || ! modals.length ) {
				return;
			}

			function openModal( name ) {
				close( i );
				var target = null;
				modals.forEach( function ( m ) {
					if ( m.getAttribute( 'data-pkst-modal' ) === name ) {
						target = m;
					}
				} );
				if ( ! target ) {
					return;
				}
				overlay.hidden = false;
				target.hidden = false;
				// Next frame, so the browser paints the hidden->visible
				// state change first and the opacity/transform transition
				// actually has something to animate from.
				requestAnimationFrame( function () {
					overlay.classList.add( 'is-visible' );
					target.classList.add( 'is-visible' );
				} );
				document.body.style.overflow = 'hidden';
				var firstInput = target.querySelector( 'input' );
				if ( firstInput ) {
					setTimeout( function () { firstInput.focus(); }, 320 );
				}
			}

			function closeModal() {
				overlay.classList.remove( 'is-visible' );
				modals.forEach( function ( m ) { m.classList.remove( 'is-visible' ); } );
				document.body.style.overflow = '';
				setTimeout( function () {
					overlay.hidden = true;
					modals.forEach( function ( m ) { m.hidden = true; } );
				}, 350 );
				i.trigger.focus();
			}

			openers.forEach( function ( btn ) {
				btn.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					openModal( btn.getAttribute( 'data-pkst-open-modal' ) );
				} );
			} );

			root.querySelectorAll( '[data-pkst-modal-close]' ).forEach( function ( btn ) {
				btn.addEventListener( 'click', closeModal );
			} );
			overlay.addEventListener( 'click', closeModal );

			document.addEventListener( 'keydown', function ( e ) {
				if ( 'Escape' === e.key && overlay.classList.contains( 'is-visible' ) ) {
					closeModal();
				}
			} );

			root.querySelectorAll( '[data-pkst-toggle-pass]' ).forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					var input = btn.parentNode.querySelector( 'input' );
					if ( ! input ) {
						return;
					}
					input.type = 'password' === input.type ? 'text' : 'password';
				} );
			} );

			root.querySelectorAll( '[data-pkst-login-form]' ).forEach( function ( form ) {
				form.addEventListener( 'submit', function ( e ) {
					e.preventDefault();
					submitLogin( form );
				} );
			} );
		}

		function submitLogin( form ) {
			var errorEl   = form.querySelector( '[data-pkst-login-error]' );
			var submitBtn = form.querySelector( '.pkst-acct-submit' );
			var spinner   = form.querySelector( '.pkst-acct-spinner' );
			var label     = form.querySelector( '.pkst-acct-btn-label' );
			var ajaxUrl   = ( window.PKST_ACCT && window.PKST_ACCT.ajaxUrl ) || '/wp-admin/admin-ajax.php';

			function setError( message ) {
				errorEl.textContent = message;
				errorEl.hidden = false;
			}

			function setLoading( isLoading ) {
				submitBtn.disabled = isLoading;
				spinner.hidden = ! isLoading;
				label.style.visibility = isLoading ? 'hidden' : 'visible';
			}

			errorEl.hidden = true;
			setLoading( true );

			var body = new URLSearchParams( new FormData( form ) );
			body.append( 'action', 'pkst_widget_login' );

			fetch( ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString(),
			} )
				.then( function ( res ) { return res.json(); } )
				.then( function ( data ) {
					if ( data && data.success && data.data && data.data.redirect ) {
						window.location.href = data.data.redirect;
						return;
					}
					setLoading( false );
					setError( ( data && data.data && data.data.message ) || 'خطایی رخ داد. دوباره تلاش کنید.' );
				} )
				.catch( function () {
					setLoading( false );
					setError( 'خطا در برقراری ارتباط. اتصال اینترنت را بررسی کنید.' );
				} );
		}
	} );
})();

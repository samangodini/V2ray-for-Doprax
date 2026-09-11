(function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		initStatusPodToggle();
		initSignaturePad();
		initGeneratePassword();
		initPriceFormatting();
		initCustomerAddresses();
		initUserRoleToggle();
	} );

	/**
	 * Courier-only fields (currently just "vehicle") only make sense when
	 * the "نوع کاربر" select is set to courier; shared by both the
	 * create-user and edit-user forms via the same two element IDs.
	 */
	function initUserRoleToggle() {
		var select = document.getElementById( 'pkst-user-role' );
		var field  = document.getElementById( 'pkst-vehicle-field' );
		if ( ! select || ! field ) {
			return;
		}

		function update() {
			field.style.display = 'pkst_courier' === select.value ? '' : 'none';
		}

		select.addEventListener( 'change', update );
		update();
	}

	/**
	 * When a customer is linked to the shipment being created, fetch that
	 * customer's saved addresses (PKST_Address) and show them as one-click
	 * fill-ins for the destination map, mirroring how Snapp lets you pick
	 * a saved place instead of typing/pinning it again each time.
	 */
	function initCustomerAddresses() {
		var select = document.getElementById( 'customer_user_id' );
		var box    = document.getElementById( 'pkst-customer-addresses' );
		if ( ! select || ! box || typeof PKST_ADMIN === 'undefined' ) {
			return;
		}
		var list = box.querySelector( '.pkst-customer-addresses-list' );

		select.addEventListener( 'change', function () {
			var customerId = select.value;
			if ( ! customerId || '0' === customerId ) {
				box.hidden = true;
				return;
			}

			var body = new URLSearchParams();
			body.append( 'action', 'pkst_get_customer_addresses' );
			body.append( 'nonce', PKST_ADMIN.nonce );
			body.append( 'customer_id', customerId );

			fetch( PKST_ADMIN.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString(),
			} )
				.then( function ( res ) { return res.json(); } )
				.then( function ( data ) {
					var addresses = data && data.success && Array.isArray( data.data ) ? data.data : [];
					renderAddresses( addresses );
				} )
				.catch( function () {
					renderAddresses( [] );
				} );
		} );

		function renderAddresses( addresses ) {
			list.innerHTML = '';
			if ( ! addresses.length ) {
				box.hidden = true;
				return;
			}
			box.hidden = false;
			addresses.forEach( function ( addr ) {
				var chip = document.createElement( 'button' );
				chip.type = 'button';
				chip.className = 'button button-small pkst-address-chip';
				chip.textContent = addr.label;
				chip.title = addr.address;
				chip.addEventListener( 'click', function () {
					applyAddress( addr );
				} );
				list.appendChild( chip );
			} );
		}

		function applyAddress( addr ) {
			var destinationField = document.getElementById( 'destination' );
			if ( destinationField ) {
				destinationField.value = addr.address;
			}
			var picker = document.querySelector( '.pkst-map-picker' );
			if ( picker && picker.pkstMap ) {
				var lat = parseFloat( addr.lat );
				var lng = parseFloat( addr.lng );
				picker.pkstMap.map.setView( [ lat, lng ], 15 );
				picker.pkstMap.setPoint( lat, lng, false );
			}
		}
	}

	/**
	 * Live thousands-separator display only; the server re-parses the raw
	 * digits on submit (PKST_Shipment::sanitize_price()), so this is purely
	 * a readability aid, not the source of truth for the stored value.
	 */
	function initPriceFormatting() {
		var fields = document.querySelectorAll( '.pkst-price-input' );
		fields.forEach( function ( field ) {
			field.addEventListener( 'input', function () {
				var digits = field.value.replace( /[^0-9]/g, '' );
				field.value = digits ? Number( digits ).toLocaleString( 'en-US' ) : '';
			} );
		} );
	}

	function initGeneratePassword() {
		var btn = document.getElementById( 'pkst-generate-password' );
		var field = document.getElementById( 'pkst-password' );
		if ( ! btn || ! field ) {
			return;
		}

		btn.addEventListener( 'click', function () {
			var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
			var out = '';
			for ( var i = 0; i < 12; i++ ) {
				out += chars.charAt( Math.floor( Math.random() * chars.length ) );
			}
			field.value = out;
			field.type = 'text';
			field.focus();
			field.select();
		} );
	}

	var POD_STATUSES = [ 'delivered', 'failed' ];

	function initStatusPodToggle() {
		var select = document.getElementById( 'pkst-status-select' );
		var podFields = document.getElementById( 'pkst-pod-fields' );
		if ( ! select || ! podFields ) {
			return;
		}

		function update() {
			podFields.style.display = POD_STATUSES.indexOf( select.value ) !== -1 ? '' : 'none';
		}

		select.addEventListener( 'change', update );
		update();
	}

	function initSignaturePad() {
		var canvas = document.getElementById( 'pkst-signature-pad' );
		if ( ! canvas || ! canvas.getContext ) {
			return;
		}
		var ctx = canvas.getContext( '2d' );
		ctx.lineWidth = 2;
		ctx.lineJoin = 'round';
		ctx.lineCap = 'round';
		ctx.strokeStyle = '#1d2327';

		var drawing = false;
		var last = null;
		var hasDrawn = false;

		function pointerPos( e ) {
			var rect = canvas.getBoundingClientRect();
			var point = e.touches && e.touches.length ? e.touches[ 0 ] : e;
			return {
				x: ( point.clientX - rect.left ) * ( canvas.width / rect.width ),
				y: ( point.clientY - rect.top ) * ( canvas.height / rect.height ),
			};
		}

		function start( e ) {
			drawing = true;
			last = pointerPos( e );
			e.preventDefault();
		}

		function move( e ) {
			if ( ! drawing ) {
				return;
			}
			var p = pointerPos( e );
			ctx.beginPath();
			ctx.moveTo( last.x, last.y );
			ctx.lineTo( p.x, p.y );
			ctx.stroke();
			last = p;
			hasDrawn = true;
			e.preventDefault();
		}

		function end() {
			drawing = false;
		}

		canvas.addEventListener( 'mousedown', start );
		canvas.addEventListener( 'mousemove', move );
		window.addEventListener( 'mouseup', end );
		canvas.addEventListener( 'touchstart', start, { passive: false } );
		canvas.addEventListener( 'touchmove', move, { passive: false } );
		canvas.addEventListener( 'touchend', end );

		var clearBtn = document.getElementById( 'pkst-clear-signature' );
		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', function () {
				ctx.clearRect( 0, 0, canvas.width, canvas.height );
				hasDrawn = false;
			} );
		}

		var form = canvas.closest( 'form' );
		var hidden = document.getElementById( 'pod_signature_data' );
		if ( form && hidden ) {
			form.addEventListener( 'submit', function () {
				hidden.value = hasDrawn ? canvas.toDataURL( 'image/png' ) : '';
			} );
		}
	}
})();

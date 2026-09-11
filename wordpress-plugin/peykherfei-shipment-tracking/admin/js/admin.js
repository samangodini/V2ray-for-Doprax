(function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		initGatewayToggle();
		initStatusPodToggle();
		initSignaturePad();
		initTestSms();
	} );

	function initGatewayToggle() {
		var select = document.getElementById( 'sms_gateway' );
		if ( ! select ) {
			return;
		}
		var groups = document.querySelectorAll( '.pkst-gateway-fields' );

		function update() {
			groups.forEach( function ( group ) {
				group.classList.toggle( 'pkst-active', group.getAttribute( 'data-gateway' ) === select.value );
			} );
		}

		select.addEventListener( 'change', update );
		update();
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

	function initTestSms() {
		var btn = document.getElementById( 'pkst-test-sms-btn' );
		if ( ! btn || typeof PKST_ADMIN === 'undefined' ) {
			return;
		}

		btn.addEventListener( 'click', function () {
			var phoneInput = document.getElementById( 'pkst-test-phone' );
			var gatewaySelect = document.getElementById( 'sms_gateway' );
			var resultBox = document.getElementById( 'pkst-test-sms-result' );
			var phone = phoneInput ? phoneInput.value.trim() : '';

			if ( ! phone || ! resultBox ) {
				return;
			}

			btn.disabled = true;
			var originalText = btn.textContent;
			btn.textContent = PKST_ADMIN.i18n.testing;

			var body = new URLSearchParams();
			body.append( 'action', 'pkst_test_sms' );
			body.append( 'nonce', PKST_ADMIN.nonce );
			body.append( 'phone', phone );
			if ( gatewaySelect ) {
				body.append( 'gateway', gatewaySelect.value );
			}

			fetch( PKST_ADMIN.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString(),
			} )
				.then( function ( res ) {
					return res.json();
				} )
				.then( function ( data ) {
					var ok = data && data.success;
					var message = data && data.data && data.data.message ? data.data.message : '';
					resultBox.innerHTML = '<div class="notice notice-' + ( ok ? 'success' : 'error' ) + '"><p>' + escapeHtml( message ) + '</p></div>';
				} )
				.catch( function () {
					resultBox.innerHTML = '<div class="notice notice-error"><p>خطای ارتباط با سرور.</p></div>';
				} )
				.finally( function () {
					btn.disabled = false;
					btn.textContent = originalText;
				} );
		} );
	}

	function escapeHtml( str ) {
		var div = document.createElement( 'div' );
		div.textContent = str;
		return div.innerHTML;
	}
})();

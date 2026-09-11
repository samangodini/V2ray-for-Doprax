(function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		initStatusPodToggle();
		initSignaturePad();
	} );

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

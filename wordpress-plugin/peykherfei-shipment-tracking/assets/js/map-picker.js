/**
 * Shared Leaflet-based destination picker: click/drag a marker, or search
 * a place name, and both the hidden lat/lng fields and (via reverse
 * geocoding) the linked address text field are filled in -- used by the
 * admin shipment form and the front-end saved-addresses panel alike.
 */
( function () {
	'use strict';

	function initPicker( root ) {
		var mapEl = root.querySelector( '.pkst-map' );
		if ( ! mapEl || typeof L === 'undefined' || mapEl.dataset.pkstInited ) {
			return;
		}
		mapEl.dataset.pkstInited = '1';

		var latField     = root.querySelector( '.pkst-map-lat' );
		var lngField     = root.querySelector( '.pkst-map-lng' );
		var statusEl     = root.querySelector( '.pkst-map-status' );
		var addressField = mapEl.dataset.addressField ? document.getElementById( mapEl.dataset.addressField ) : null;

		var hasPoint  = latField.value && lngField.value;
		var startLat  = hasPoint ? parseFloat( latField.value ) : parseFloat( mapEl.dataset.defaultLat );
		var startLng  = hasPoint ? parseFloat( lngField.value ) : parseFloat( mapEl.dataset.defaultLng );

		var map = L.map( mapEl ).setView( [ startLat, startLng ], hasPoint ? 14 : 5 );
		L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap contributors',
			maxZoom: 19,
		} ).addTo( map );

		var marker = null;
		if ( hasPoint ) {
			marker = L.marker( [ startLat, startLng ], { draggable: true } ).addTo( map );
			marker.on( 'dragend', function ( e ) {
				var pos = e.target.getLatLng();
				setPoint( pos.lat, pos.lng, true );
			} );
		}

		map.on( 'click', function ( e ) {
			setPoint( e.latlng.lat, e.latlng.lng, true );
		} );

		function setPoint( lat, lng, reverseGeocode ) {
			if ( marker ) {
				marker.setLatLng( [ lat, lng ] );
			} else {
				marker = L.marker( [ lat, lng ], { draggable: true } ).addTo( map );
				marker.on( 'dragend', function ( e ) {
					var pos = e.target.getLatLng();
					setPoint( pos.lat, pos.lng, true );
				} );
			}
			latField.value = lat.toFixed( 7 );
			lngField.value = lng.toFixed( 7 );
			if ( reverseGeocode ) {
				reverseGeocodeAndFill( lat, lng );
			}
		}

		function setStatus( text ) {
			if ( statusEl ) {
				statusEl.textContent = text;
			}
		}

		function reverseGeocodeAndFill( lat, lng ) {
			if ( ! addressField ) {
				return;
			}
			setStatus( 'در حال دریافت آدرس...' );
			fetch(
				'https://nominatim.openstreetmap.org/reverse?format=json&lat=' + encodeURIComponent( lat ) + '&lon=' + encodeURIComponent( lng ) + '&accept-language=fa',
				{ headers: { Accept: 'application/json' } }
			)
				.then( function ( res ) {
					return res.json();
				} )
				.then( function ( data ) {
					if ( data && data.display_name ) {
						addressField.value = data.display_name;
					}
					setStatus( '' );
				} )
				.catch( function () {
					setStatus( '' );
				} );
		}

		var locateBtn = root.querySelector( '.pkst-map-locate' );
		if ( locateBtn && navigator.geolocation ) {
			locateBtn.addEventListener( 'click', function () {
				setStatus( 'در حال دریافت موقعیت...' );
				navigator.geolocation.getCurrentPosition(
					function ( pos ) {
						map.setView( [ pos.coords.latitude, pos.coords.longitude ], 15 );
						setPoint( pos.coords.latitude, pos.coords.longitude, true );
					},
					function () {
						setStatus( 'دسترسی به موقعیت مکانی ممکن نشد.' );
					}
				);
			} );
		}

		var searchInput = root.querySelector( '.pkst-map-search' );
		var searchBtn   = root.querySelector( '.pkst-map-search-btn' );
		function runSearch() {
			var q = searchInput ? searchInput.value.trim() : '';
			if ( ! q ) {
				return;
			}
			setStatus( 'در حال جستجو...' );
			fetch( 'https://nominatim.openstreetmap.org/search?format=json&limit=1&accept-language=fa&q=' + encodeURIComponent( q ) )
				.then( function ( res ) {
					return res.json();
				} )
				.then( function ( results ) {
					if ( results && results[ 0 ] ) {
						var lat = parseFloat( results[ 0 ].lat );
						var lng = parseFloat( results[ 0 ].lon );
						map.setView( [ lat, lng ], 15 );
						setPoint( lat, lng, false );
						if ( addressField ) {
							addressField.value = results[ 0 ].display_name;
						}
					} else {
						setStatus( 'موردی یافت نشد.' );
					}
					setTimeout( function () { setStatus( '' ); }, 2000 );
				} )
				.catch( function () {
					setStatus( '' );
				} );
		}
		if ( searchBtn ) {
			searchBtn.addEventListener( 'click', runSearch );
		}
		if ( searchInput ) {
			searchInput.addEventListener( 'keydown', function ( e ) {
				if ( 'Enter' === e.key ) {
					e.preventDefault();
					runSearch();
				}
			} );
		}

		// The map box can start out hidden (e.g. inside a collapsed panel);
		// Leaflet measures the container on init, so nudge it once shown.
		setTimeout( function () {
			map.invalidateSize();
		}, 200 );

		root.pkstMap = { map: map, setPoint: setPoint };
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.pkst-map-picker' ).forEach( initPicker );
	} );

	window.PKST_initMapPicker = initPicker;
} )();

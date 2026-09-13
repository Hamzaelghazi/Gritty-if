/**
 * Nasm Order Tracking Pro — customer-facing tracking form.
 * No dependencies (no jQuery) to keep the frontend footprint minimal.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.getElementById( 'nasm-ot-track-form' );
		if ( ! form || typeof nasmOtFrontend === 'undefined' ) {
			return;
		}

		var searchPanel = document.getElementById( 'nasm-ot-search-panel' );
		var resultsPanel = document.getElementById( 'nasm-ot-results' );
		var errorBox = document.getElementById( 'nasm-ot-error' );
		var submitBtn = form.querySelector( '.nasm-ot-tracking__submit' );

		function setLoading( isLoading ) {
			submitBtn.disabled = isLoading;
			submitBtn.classList.toggle( 'is-loading', isLoading );
		}

		function showError( message ) {
			errorBox.textContent = message;
			errorBox.hidden = false;
		}

		function hideError() {
			errorBox.hidden = true;
			errorBox.textContent = '';
		}

		function bindResultInteractions() {
			var toggle = document.getElementById( 'nasm-ot-address-toggle' );
			var address = document.getElementById( 'nasm-ot-address' );
			if ( toggle && address ) {
				toggle.addEventListener( 'click', function () {
					var isHidden = address.hidden;
					address.hidden = ! isHidden;
					toggle.setAttribute( 'aria-expanded', isHidden ? 'true' : 'false' );
					toggle.textContent = isHidden
						? toggle.textContent.replace( 'Show', 'Hide' )
						: toggle.textContent.replace( 'Hide', 'Show' );
				} );
			}

			var again = document.getElementById( 'nasm-ot-search-again' );
			if ( again ) {
				again.addEventListener( 'click', function () {
					resultsPanel.hidden = true;
					resultsPanel.innerHTML = '';
					searchPanel.hidden = false;
					form.reset();
					searchPanel.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				} );
			}
		}

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			hideError();

			var orderNumber = document.getElementById( 'nasm-ot-order-number' ).value.trim();
			var lastName = document.getElementById( 'nasm-ot-last-name' ).value.trim();

			if ( ! orderNumber || ! lastName ) {
				showError( nasmOtFrontend.i18n.notFound );
				return;
			}

			setLoading( true );

			var body = new URLSearchParams();
			body.set( 'action', 'nasm_ot_track_order' );
			body.set( 'nonce', nasmOtFrontend.nonce );
			body.set( 'order_number', orderNumber );
			body.set( 'last_name', lastName );

			fetch( nasmOtFrontend.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString(),
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( json ) {
					setLoading( false );

					if ( json && json.success && json.data && json.data.html ) {
						searchPanel.hidden = true;
						resultsPanel.innerHTML = json.data.html;
						resultsPanel.hidden = false;
						bindResultInteractions();
						resultsPanel.scrollIntoView( { behavior: 'smooth', block: 'start' } );
					} else {
						var message = ( json && json.data && json.data.message ) ? json.data.message : nasmOtFrontend.i18n.error;
						showError( message );
					}
				} )
				.catch( function () {
					setLoading( false );
					showError( nasmOtFrontend.i18n.error );
				} );
		} );
	} );
}() );

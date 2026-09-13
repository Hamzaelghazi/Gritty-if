/**
 * Nasm Order Tracking Pro — admin screens.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		// Color pickers (statuses + settings screens).
		if ( $.fn.wpColorPicker ) {
			$( '.nasm-ot-color-picker' ).wpColorPicker();
		}

		// Confirm destructive actions.
		$( document ).on( 'click', '.nasm-ot-delete-link[data-confirm]', function ( e ) {
			var message = ( window.nasmOtAdmin && nasmOtAdmin.i18n && nasmOtAdmin.i18n.confirmDelete )
				? nasmOtAdmin.i18n.confirmDelete
				: 'Are you sure?';
			if ( ! window.confirm( message ) ) {
				e.preventDefault();
			}
		} );

		// Media uploader for the company logo (Settings screen).
		var frame;
		$( '#nasm-ot-logo-select' ).on( 'click', function ( e ) {
			e.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: 'Select Company Logo',
				button: { text: 'Use this logo' },
				multiple: false,
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				$( '#nasm-ot-logo-id' ).val( attachment.id );
				$( '#nasm-ot-logo-preview' ).attr( 'src', attachment.url ).show();
				$( '#nasm-ot-logo-remove' ).show();
			} );

			frame.open();
		} );

		$( '#nasm-ot-logo-remove' ).on( 'click', function ( e ) {
			e.preventDefault();
			$( '#nasm-ot-logo-id' ).val( '' );
			$( '#nasm-ot-logo-preview' ).hide().attr( 'src', '' );
			$( this ).hide();
		} );

		// Statuses screen: populate the form when "Edit" is clicked.
		var $statusForm = $( '#nasm-ot-status-form' );
		if ( $statusForm.length ) {
			var $title = $( '#nasm-ot-status-form-title' );
			var $slugField = $statusForm.find( 'input[name="slug"]' );

			$( document ).on( 'click', '.nasm-ot-edit-status', function ( e ) {
				e.preventDefault();
				var $el = $( this );

				$statusForm.find( 'input[name="status_id"]' ).val( $el.data( 'id' ) );
				$statusForm.find( 'input[name="label"]' ).val( $el.data( 'label' ) );
				$slugField.val( $el.data( 'slug' ) );
				$statusForm.find( 'input[name="icon"]' ).val( $el.data( 'icon' ) );
				$statusForm.find( 'input[name="color"]' ).val( $el.data( 'color' ) ).wpColorPicker( 'color', $el.data( 'color' ) );
				$statusForm.find( 'textarea[name="description"]' ).val( $el.data( 'description' ) );
				$statusForm.find( 'input[name="sort_order"]' ).val( $el.data( 'sort' ) );
				$statusForm.find( 'input[name="is_active"]' ).prop( 'checked', String( $el.data( 'active' ) ) === '1' );

				// Slugs are immutable once a status exists (StatusesRepository::update()
				// never writes the slug column), so lock the field for every edit, not
				// just system statuses.
				$slugField.prop( 'readonly', true );

				$title.text( 'Edit Status' );
				$statusForm[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'center' } );
			} );

			$( '#nasm-ot-status-reset' ).on( 'click', function () {
				$statusForm[ 0 ].reset();
				$statusForm.find( 'input[name="status_id"]' ).val( '' );
				$slugField.prop( 'readonly', false );
				$title.text( 'Add Status' );
			} );
		}

		// Add Tracking screen: search existing WooCommerce orders and autofill
		// order number / customer name / email instead of retyping them.
		var $wcSearch = $( '#nasm-ot-wc-order-search' );
		if ( $wcSearch.length && window.nasmOtAdmin ) {
			var $results  = $( '#nasm-ot-wc-order-results' );
			var $form     = $wcSearch.closest( 'form' );
			var searchXhr = null;
			var debounce  = null;

			function closeResults() {
				$results.empty().hide();
			}

			function renderResults( orders ) {
				$results.empty();

				if ( ! orders.length ) {
					var emptyText = ( nasmOtAdmin.i18n && nasmOtAdmin.i18n.noOrdersFound ) || 'No matching orders found.';
					$results.append( $( '<li class="nasm-ot-wc-result nasm-ot-wc-result--empty"></li>' ).text( emptyText ) );
					$results.show();
					return;
				}

				orders.forEach( function ( order ) {
					var unknownText = ( nasmOtAdmin.i18n && nasmOtAdmin.i18n.unknownCustomer ) || 'Unknown customer';
					var name = ( order.first_name + ' ' + order.last_name ).trim() || order.email || unknownText;
					var $li  = $( '<li class="nasm-ot-wc-result" tabindex="0"></li>' );

					$li.append( $( '<strong></strong>' ).text( '#' + order.order_number + ' — ' + name ) );
					$li.append( $( '<span></span>' ).text( [ order.email, order.date, order.total ].filter( Boolean ).join( ' · ' ) ) );

					$li.on( 'click', function () {
						$form.find( 'input[name="order_number"]' ).val( order.order_number );
						$form.find( 'input[name="first_name"]' ).val( order.first_name );
						$form.find( 'input[name="last_name"]' ).val( order.last_name );
						$form.find( 'input[name="email"]' ).val( order.email );
						$wcSearch.val( '#' + order.order_number + ' — ' + name );
						closeResults();
						$form.find( 'select[name="current_status"]' ).trigger( 'focus' );
					} );

					$results.append( $li );
				} );

				$results.show();
			}

			$wcSearch.on( 'input', function () {
				var term = $wcSearch.val().trim();

				window.clearTimeout( debounce );

				if ( term.length < 2 ) {
					closeResults();
					return;
				}

				debounce = window.setTimeout( function () {
					if ( searchXhr ) {
						searchXhr.abort();
					}

					searchXhr = $.get( nasmOtAdmin.ajaxUrl, {
						action: 'nasm_ot_search_wc_orders',
						nonce: nasmOtAdmin.nonce,
						term: term,
					} ).done( function ( response ) {
						if ( response && response.success ) {
							renderResults( response.data );
						}
					} );
				}, 300 );
			} );

			$( document ).on( 'click', function ( e ) {
				if ( ! $( e.target ).closest( '.nasm-ot-wc-search' ).length ) {
					closeResults();
				}
			} );
		}

		// WooCommerce Sync screen: import existing orders in batches, updating a
		// progress bar, so a big store never relies on one long-running request.
		var $backfillBtn = $( '#nasm-ot-backfill-btn' );
		if ( $backfillBtn.length && window.nasmOtAdmin ) {
			var $progress = $( '#nasm-ot-backfill-progress' );
			var $bar      = $( '#nasm-ot-backfill-bar' );
			var $status   = $( '#nasm-ot-backfill-status' );
			var $total    = $( '#nasm-ot-backfill-total' );

			var totals = { created: 0, exists: 0, ignored: 0, errors: 0 };

			function runBatch( offset ) {
				$.post( nasmOtAdmin.ajaxUrl, {
					action: 'nasm_ot_wc_backfill',
					nonce: nasmOtAdmin.nonce,
					offset: offset,
					batch: 20,
				} ).done( function ( response ) {
					if ( ! response || ! response.success ) {
						$status.text( ( response && response.data && response.data.message ) || 'Import failed. Please try again.' );
						$backfillBtn.prop( 'disabled', false );
						return;
					}

					var data = response.data;
					totals.created += data.created;
					totals.exists += data.exists;
					totals.ignored += data.ignored;
					totals.errors += data.errors;

					var doneCount = data.nextOffset;
					var pct = data.total > 0 ? Math.min( 100, Math.round( ( doneCount / data.total ) * 100 ) ) : 100;
					$bar.css( 'width', pct + '%' );

					$status.text(
						'Imported ' + totals.created +
						' · already tracked ' + totals.exists +
						( totals.errors ? ' · errors ' + totals.errors : '' ) +
						' (' + pct + '%)'
					);

					if ( data.done ) {
						$status.text(
							'Done. Imported ' + totals.created + ' new order(s), skipped ' +
							( totals.exists + totals.ignored ) + ' already-tracked or ineligible' +
							( totals.errors ? ', ' + totals.errors + ' error(s)' : '' ) + '.'
						);
						$backfillBtn.prop( 'disabled', false ).text( 'Import Existing Orders Now' );
						$total.text( '0' );
					} else {
						runBatch( data.nextOffset );
					}
				} ).fail( function () {
					$status.text( 'Import failed. Please try again.' );
					$backfillBtn.prop( 'disabled', false );
				} );
			}

			$backfillBtn.on( 'click', function () {
				totals = { created: 0, exists: 0, ignored: 0, errors: 0 };
				$backfillBtn.prop( 'disabled', true ).text( 'Importing…' );
				$progress.show();
				$bar.css( 'width', '0' );
				$status.text( 'Starting…' );
				runBatch( 0 );
			} );
		}
	} );
}( jQuery ) );

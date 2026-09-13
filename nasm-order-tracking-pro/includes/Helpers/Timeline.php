<?php
/**
 * Builds the Amazon-style checkpoint timeline for an order.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Helpers;

use Nasm\OrderTracking\Database\StatusesRepository;

defined( 'ABSPATH' ) || exit;

class Timeline {

	/**
	 * Build ordered checkpoint steps for an order.
	 *
	 * Every active status is a checkpoint. Checkpoints at or before the order's
	 * current status are "completed" (or "current" for the exact match); everything
	 * after is "upcoming". Terminal/exception statuses (delayed, returned, cancelled)
	 * are excluded from the linear progress checkpoints and surfaced separately.
	 *
	 * @param array $order   Order row (array).
	 * @param array $history Status history rows for the order, chronological.
	 * @return array{steps: array, exception: array|null, percent: int}
	 */
	public static function build( array $order, array $history ) {
		$statuses = StatusesRepository::all( true );

		$exception_slugs = array( 'delayed', 'returned', 'cancelled' );

		$linear_statuses = array_values(
			array_filter(
				$statuses,
				function ( $status ) use ( $exception_slugs ) {
					return ! in_array( $status['slug'], $exception_slugs, true );
				}
			)
		);

		$current_slug = $order['current_status'];
		$is_exception = in_array( $current_slug, $exception_slugs, true );

		// Look up the current status's sort_order regardless of whether it is
		// still active: an order can keep a status slug after an admin
		// deactivates it, and sort_for_slug() must not silently collapse to 0
		// in that case (which would render every checkpoint as "upcoming").
		$current_status_row = StatusesRepository::find_by_slug( $current_slug );
		$current_sort       = $current_status_row ? (int) $current_status_row['sort_order'] : self::sort_for_slug( $statuses, $current_slug );

		$history_by_status = array();
		foreach ( $history as $entry ) {
			// Keep the latest entry per status slug for the checkpoint timestamp.
			$history_by_status[ $entry['status'] ] = $entry;
		}

		$steps   = array();
		$reached = true;

		foreach ( $linear_statuses as $status ) {
			$status_sort = (int) $status['sort_order'];

			if ( $is_exception ) {
				// While in an exception state, anything before the last known
				// linear status stays completed; nothing is "current" on the line.
				$state = ( $status_sort <= $current_sort ) ? 'completed' : 'upcoming';
			} elseif ( $status_sort < $current_sort ) {
				$state = 'completed';
			} elseif ( $status_sort === $current_sort ) {
				$state = 'current';
			} else {
				$state = 'upcoming';
			}

			$entry = $history_by_status[ $status['slug'] ] ?? null;

			$steps[] = array(
				'slug'        => $status['slug'],
				'label'       => $status['label'],
				'icon'        => $status['icon'],
				'color'       => $status['color'],
				'description' => $status['description'],
				'state'       => $state,
				'occurred_at' => $entry['occurred_at'] ?? null,
				'note'        => $entry['note'] ?? '',
			);
		}

		$total_steps     = count( $linear_statuses );
		$completed_steps = count( array_filter( $steps, fn( $s ) => 'completed' === $s['state'] || 'current' === $s['state'] ) );
		$percent         = $total_steps > 0 ? (int) round( ( $completed_steps / $total_steps ) * 100 ) : 0;

		$exception = null;
		if ( $is_exception ) {
			$entry     = $history_by_status[ $current_slug ] ?? null;
			$exception = array(
				'slug'        => $current_slug,
				'label'       => $current_status_row['label'] ?? ucfirst( $current_slug ),
				'icon'        => $current_status_row['icon'] ?? 'dashicons-warning',
				'color'       => $current_status_row['color'] ?? '#d94f4f',
				'occurred_at' => $entry['occurred_at'] ?? null,
				'note'        => $entry['note'] ?? '',
			);
		}

		return array(
			'steps'     => $steps,
			'exception' => $exception,
			'percent'   => $percent,
		);
	}

	/**
	 * Look up the sort_order for a given status slug.
	 *
	 * @param array  $statuses All statuses.
	 * @param string $slug     Slug to find.
	 * @return int
	 */
	protected static function sort_for_slug( array $statuses, $slug ) {
		foreach ( $statuses as $status ) {
			if ( $status['slug'] === $slug ) {
				return (int) $status['sort_order'];
			}
		}
		return 0;
	}
}

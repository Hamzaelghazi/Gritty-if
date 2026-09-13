<?php
/**
 * Shared display-formatting helpers for statuses.
 *
 * Centralizes the "look up a status by slug, fall back to a title-cased
 * version of the slug" pattern that would otherwise be duplicated across
 * the orders table, emails, timeline, and frontend templates.
 *
 * @package Nasm_Order_Tracking
 */

namespace Nasm\OrderTracking\Helpers;

use Nasm\OrderTracking\Database\StatusesRepository;

defined( 'ABSPATH' ) || exit;

class Formatter {

	/**
	 * Human-readable label for a status slug, falling back to a title-cased
	 * version of the slug itself if the status was deleted/renamed.
	 *
	 * @param string     $slug   Status slug.
	 * @param array|null $status Optional pre-fetched status row to avoid a repeat query.
	 * @return string
	 */
	public static function status_label( $slug, $status = null ) {
		if ( null === $status ) {
			$status = StatusesRepository::find_by_slug( $slug );
		}

		return $status['label'] ?? ucfirst( str_replace( '_', ' ', $slug ) );
	}

	/**
	 * Hex color for a status slug, falling back to a neutral gray.
	 *
	 * @param string     $slug   Status slug.
	 * @param array|null $status Optional pre-fetched status row to avoid a repeat query.
	 * @return string
	 */
	public static function status_color( $slug, $status = null ) {
		if ( null === $status ) {
			$status = StatusesRepository::find_by_slug( $slug );
		}

		return $status['color'] ?? '#8a94a6';
	}

	/**
	 * Render a colored pill badge for a status slug.
	 *
	 * @param string     $slug   Status slug.
	 * @param array|null $status Optional pre-fetched status row to avoid a repeat query.
	 * @return string Escaped HTML.
	 */
	public static function status_badge_html( $slug, $status = null ) {
		if ( null === $status ) {
			$status = StatusesRepository::find_by_slug( $slug );
		}

		$label = self::status_label( $slug, $status );
		$color = self::status_color( $slug, $status );

		return sprintf(
			'<span class="nasm-ot-badge" style="background-color:%s1a;color:%s;">%s</span>',
			esc_attr( $color ),
			esc_attr( $color ),
			esc_html( $label )
		);
	}
}

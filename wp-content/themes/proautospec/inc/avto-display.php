<?php
/**
 * Avto CPT display helpers (motorhomes / «Автодома»).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether to show «По запросу» instead of a numeric RUB price.
 *
 * Applies to imported motorhomes (fujicars/bobaedream) and category 1 listings
 * that are not legacy archive posts (no old-id).
 */
function proautospec_avto_price_on_request( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( ! $post_id ) {
		return false;
	}

	$source = get_post_meta( $post_id, '_source', true );
	if ( in_array( $source, array( 'fujicars', 'bobaedream' ), true ) ) {
		return true;
	}

	if ( get_field( 'old-id', $post_id ) ) {
		return false;
	}

	return has_category( 1, $post_id );
}

/**
 * Formatted price label for avto cards and single pages.
 */
function proautospec_avto_price_html( $price, $post_id = null ) {
	if ( proautospec_avto_price_on_request( $post_id ) ) {
		return 'По запросу';
	}

	$amount = is_numeric( $price ) ? (float) $price : 0;
	if ( $amount <= 0 ) {
		return 'По запросу';
	}

	return number_format( (int) $amount, 0, '.', ' ' ) . ' ₽';
}

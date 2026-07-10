<?php
/**
 * Performance helpers for landing pages (front page, /avtodoma/).
 *
 * @package proautospec
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front page and motorhome listing — lighter asset bundle.
 */
function proautospec_is_landing_perf_page() {
	return is_front_page() || is_page( 'avtodoma' );
}

/**
 * Swiper is only used on the front page car slider.
 */
function proautospec_needs_swiper() {
	return is_front_page();
}

/**
 * Select2 is used in catalog filters, not on landing pages.
 */
function proautospec_needs_select2() {
	return ! proautospec_is_landing_perf_page();
}

/**
 * LightGallery / Fancybox — single vehicle and lot pages only.
 */
function proautospec_needs_gallery_libs() {
	if ( is_single() && 'avto' === get_post_type() ) {
		return true;
	}

	return is_page( array( 'car-lot', 'moto-lot', 'hdm-lot' ) );
}

/**
 * Skip heavy scripts/styles on landing pages after global enqueue.
 */
function proautospec_trim_landing_assets() {
	if ( ! proautospec_is_landing_perf_page() ) {
		return;
	}

	$skip_scripts = array( 'lg', 'fancybox', 'hover-carousel', 'popper' );
	$skip_styles  = array( 'fancybox', 'lg' );

	if ( ! proautospec_needs_swiper() ) {
		$skip_scripts[] = 'swiper';
		$skip_styles[]  = 'swiper';
	}

	if ( ! proautospec_needs_select2() ) {
		$skip_scripts[] = 'select2';
		$skip_styles[]  = 'select2';
		$skip_styles[]  = 'select2-dark';
	}

	foreach ( $skip_scripts as $handle ) {
		wp_dequeue_script( $handle );
	}

	foreach ( $skip_styles as $handle ) {
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'proautospec_trim_landing_assets', 102 );

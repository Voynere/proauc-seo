<?php
/**
 * Блок «Полезные статьи» на коммерческих посадочных.
 *
 * @var array $args { cluster: string }
 */

defined( 'ABSPATH' ) || exit;

$cluster = isset( $args['cluster'] ) ? (string) $args['cluster'] : '';

if ( function_exists( 'proauc_render_landing_blog_links' ) ) {
	proauc_render_landing_blog_links( $cluster );
}

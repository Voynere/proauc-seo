#!/usr/bin/env php
<?php
/**
 * Generate blog cover SVGs locally (no WordPress bootstrap required).
 *
 * Usage: php scripts/generate-blog-covers.php
 */

define( 'ABSPATH', true );

$root = dirname( __DIR__ );

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return strip_tags( (string) $text );
	}
}

if ( ! function_exists( 'sanitize_file_name' ) ) {
	function sanitize_file_name( $filename ) {
		$name = (string) $filename;
		$name = preg_replace( '/[^a-z0-9\-_\.]/iu', '-', $name );
		return trim( preg_replace( '/-+/', '-', $name ), '-' );
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( $dir ) {
		return is_dir( $dir ) || mkdir( $dir, 0755, true );
	}
}

if ( ! function_exists( 'get_template_directory' ) ) {
	function get_template_directory() {
		global $root;
		return $root . '/wp-content/themes/proautospec';
	}
}

if ( ! function_exists( 'get_template_directory_uri' ) ) {
	function get_template_directory_uri() {
		return '/wp-content/themes/proautospec';
	}
}

require_once $root . '/wp-content/themes/proautospec/inc/blog-covers.php';
require_once $root . '/wp-content/themes/proautospec/inc/blog-articles.php';

$count = 0;
foreach ( proauc_get_blog_article_seeds() as $seed ) {
	if ( empty( $seed['slug'] ) || empty( $seed['title'] ) ) {
		continue;
	}
	$cluster = ! empty( $seed['cluster'] ) ? $seed['cluster'] : 'yaponiya';
	if ( proauc_write_blog_cover_file( $seed['slug'], $seed['title'], $cluster ) ) {
		++$count;
		echo $seed['slug'] . ".svg\n";
	}
}

echo "Generated {$count} covers in images/blog/\n";

<?php
/*
Plugin Name: DB Robots.txt 
Plugin URI: https://github.com/bisteinoff/db-robotstxt
Description: The plugin automatically creates a virtual file robots.txt including special rules for Google and Yandex. You can also add custom rules for Google, Yandex and any other robots or disable Yandex if you don't need it for search engines optimisation
Version: 3.12
Author: Denis Bisteinov
Author URI: https://bisteinoff.com/
Text Domain: db-robotstxt
License: GPL2
*/

/*  Copyright 2025  Denis BISTEINOV  (email : bisteinoff@gmail.com)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$thisdir = basename( __DIR__ );
define( 'DB_PLUGIN_ROBOTSTXT_DIR', $thisdir );
define( 'DB_PLUGIN_ROBOTSTXT_VERSION', '3.12' );

$if_multi_subcat = false; // if it is the main site of a multisite with subcategories (if true) we will want some special rules
$if_publish = true; // if true than run the plugin

// CHECK MULTISITE
if ( is_multisite() && defined( 'SUBDOMAIN_INSTALL' ) )
	if ( !SUBDOMAIN_INSTALL ) 
		{
			$if_multi_subcat = true;
			if ( get_current_blog_id() !== get_main_site_id() ) $if_publish = false; // if the multisite uses subcategories we don't want a special robots.txt for each subdomain
		}

if ( $if_publish ) :

	add_option( 'db_robots_custom' );
	add_option( 'db_robots_custom_google' );
	add_option( 'db_robots_if_yandex', 'on' );
	add_option( 'db_robots_custom_yandex' );
	add_option( 'db_robots_custom_other' );

	add_action( 'admin_enqueue_scripts', function() {
					wp_enqueue_style( DB_PLUGIN_ROBOTSTXT_DIR . '-admin', plugin_dir_url( __FILE__ ) . 'css/admin.min.css', [], DB_PLUGIN_ROBOTSTXT_VERSION, 'all' );
				},
				99
	);



	function publish_robots_txt( $args ) {

		// BASIC VARIABLES

		$multi = ( $args[0] ? '*' : '' ); // used to change some rules for multisites

		// Basic rules for all user-agents

		$db_rules = [

			'Disallow' => [
				'/cgi-bin',
				$multi . '/?',
				$multi . '/wp-',
				$multi . '/wp/',
				'*/admin/',
				'*/login/',
				'*?p=',
				'*&p=',
				'*?s=',
				'*&s=',
				$multi . '/search/',
				$multi . '/trackback/',
				'*/feed/',
				'*/rss/',
				'*/embed/',
				'*/wlwmanifest.xml',
				'/xmlrpc.php',
				'*?*utm*=',
				'*?*openstat=',
				'*?*from=',
				'*?*yclid=',
				'*?*ymclid=',
				'*?*gclid='
			],
			'Allow' => [
				$multi . '/wp-*/uploads/',
				$multi . '/wp-*.webp',
				$multi . '/wp-*.avif',
				$multi . '/wp-*.jpg',
				$multi . '/wp-*.jpeg',
				$multi . '/wp-*.gif',
				$multi . '/wp-*.png',
				$multi . '/wp-*.svg',
				$multi . '/wp-*.js',
				$multi . '/wp-*.css',
				$multi . '/wp-*.doc',
				$multi . '/wp-*.docx',
				$multi . '/wp-*.xls',
				$multi . '/wp-*.xlsx',
				$multi . '/wp-*.ppt',
				$multi . '/wp-*.ppts',
				$multi . '/wp-*.pptx',
				$multi . '/wp-*.pdf',
				$multi . '/wp-*.txt',
				$multi . '/wp-admin/admin-ajax.php'
			]
		];

		if ( defined( 'WC_VERSION' ) && class_exists( 'WooCommerce' ) ) :

			$db_rules[ 'Disallow' ][] = '*?*added=';
			$db_rules[ 'Disallow' ][] = '*?*add-to-cart=';

		endif;

		$db_basic_rules = '';

		foreach ($db_rules as $db_rules_category => $db_rules_categories) {

			foreach ($db_rules_categories as $key => $db_rules) {

				$db_basic_rules .= "{$db_rules_category}: {$db_rules}\n";

			}

			$db_basic_rules .= "\n";

		}



		// Basic rules for Google

		$db_rules = array(

			'Disallow' => array (
				$multi . '/feed/turbo',
				$multi . '/feed/zen'
			),
			'Allow' => array (
				'*/amp'
			)
		);

		$db_basic_rules_google = '';

		foreach ($db_rules as $db_rules_category => $db_rules_categories) {

			foreach ($db_rules_categories as $key => $db_rules) {

				$db_basic_rules_google .= "{$db_rules_category}: {$db_rules}\n";

			}

			$db_basic_rules_google .= "\n";

		}



		// Basic rules for Yandex

		$db_rules = array(

			'Disallow' => array (
				'*/amp'
			),
			'Allow' => array (
				$multi . '/feed/turbo',
				$multi . '/feed/zen'
			)
		);

		$db_basic_rules_yandex = '';

		foreach ($db_rules as $db_rules_category => $db_rules_categories) {

			foreach ($db_rules_categories as $key => $db_rules) {

				$db_basic_rules_yandex .= "{$db_rules_category}: {$db_rules}\n";

			}

			$db_basic_rules_yandex .= "\n";

		}



		// ROBOTS.TXT

		// User-agent: *

		$db_robots = "# This virtual robots.txt file has been created with the DB Robots.txt WordPress plugin: \n# https://www.wordpress.org/plugins/bisteinoff-robots-txt/";
		$db_robots .= "\n\n\nUser-agent: *\n\n";
		$db_robots .= $db_basic_rules;
		if ( !empty ( $db_robots_custom = get_option('db_robots_custom') ) )
			$db_robots .= preg_replace('/&amp;/', '&', $db_robots_custom ) . "\n";

		// User-agent: GoogleBot

		$db_robots .= "\n\n\nUser-agent: GoogleBot\n\n";
		$db_robots .= $db_basic_rules . $db_basic_rules_google;
		if ( !empty ( $db_robots_custom_google = get_option('db_robots_custom_google') ) )
			$db_robots .= preg_replace('/&amp;/', '&', $db_robots_custom_google ) . "\n";

		// User-agent: Yandex

		if ( get_option('db_robots_if_yandex') === 'on' ) {
			$db_robots .= "\n\n\nUser-agent: Yandex\n\n";
			$db_robots .= $db_basic_rules . $db_basic_rules_yandex;
			if ( !empty ( $db_robots_custom_yandex = get_option('db_robots_custom_yandex') ) )
				$db_robots .= preg_replace('/&amp;/', '&', $db_robots_custom_yandex ) . "\n";
		}

		// Other
		if ( !empty ( $db_robots_custom_other = get_option('db_robots_custom_other') ) )
			$db_robots .= "\n\n\n" . preg_replace('/&amp;/', '&', $db_robots_custom_other ) . "\n";



		// SITEMAP

		$sitemap = '';
		$db_check_files = array(
			'sitemap.xml',
			'sitemap_index.xml',
			'sitemap.xml.gz',
			'wp-sitemap.xml'
			);

		foreach( $db_check_files as $db_file ) {

			$url = esc_url( get_site_url() ) . '/' . $db_file;

			$response = wp_remote_get( $url, [
				'timeout'     => 15,
				'sslverify'   => false,
				'redirection' => 5,
				'headers'     => [ 'Accept' => 'application/xml' ],
			]);

			if ( is_array( $response ) && !is_wp_error( $response ) ) :

				$status_code = wp_remote_retrieve_response_code( $response );

				if ( $status_code === 200 ) :

					$sitemap .= '\n' . 'Sitemap: ' . $url;

				endif;

			endif;


		}

		if ( !empty( $sitemap ) )
			$db_robots .= "\n\n\n" . $sitemap;


		$db_robots = stripcslashes( $db_robots );

		header( 'Status: 200 OK', true, 200 );
		header( 'Content-type: text/plain; charset=' . get_bloginfo( 'charset' ) );
		echo sanitize_textarea_field( $db_robots );
		exit;

	}

	$args = array( $if_multi_subcat );
	remove_action( 'do_robots', 'do_robots' );
	add_action( 'do_robots', function() use ( $args ) { 
               publish_robots_txt( $args ); } );



	function db_robots_admin() {

		if ( function_exists( 'add_options_page' ) )
		{

			add_options_page(
				esc_html__( 'DB Robots.txt Settings', 'db-robotstxt' ),
				esc_html__( 'DB Robots.txt', 'db-robotstxt' ),
				'manage_options',
				DB_PLUGIN_ROBOTSTXT_DIR,
				'db_robotstxt_admin_settings'
			);

		}

	}

	add_action( 'admin_menu', 'db_robots_admin' );



	function db_robotstxt_admin_settings() {

		require_once( 'inc/settings.php' );

	}



	add_filter( 'plugin_action_links_' . DB_PLUGIN_ROBOTSTXT_DIR . '/bisteinoff-robots-txt.php', 'db_settings_link' );

	function db_settings_link( $links )	{

		$url = esc_url( add_query_arg(
			'page',
			DB_PLUGIN_ROBOTSTXT_DIR,
			get_admin_url() . 'options-general.php'
		) );

		$settings_link = "<a href='$url'>" . __( 'Settings', 'db-robotstxt' ) . '</a>';

		array_push(
			$links,
			$settings_link
		);

		return $links;

	}

endif;
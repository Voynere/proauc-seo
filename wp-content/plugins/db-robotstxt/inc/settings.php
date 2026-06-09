<?php // THE SETTINGS PAGE

	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	$d = DB_PLUGIN_ROBOTSTXT_DIR;

	$db_self = get_admin_url() . 'options-general.php?page=' . $d;
	$db_link = ABSPATH . 'robots.txt';

	$nonce = wp_create_nonce( $d );

	if ( !empty( $nonce ) )
		$nonce_GET = '&' . $d . '_nonce=' . $nonce;

	if ( isset( $_GET[ 'action' ] ) && 
		 isset( $_GET[ $d . '_nonce' ] ) &&
		 wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ $d . '_nonce' ] ) ), sanitize_text_field( $d ) ) ) :

		if ( function_exists( 'current_user_can' ) &&
			 !current_user_can( 'manage_options' ) )
				die( esc_html_e( 'Error: You do not have the permission to update robots.txt file', 'db-robotstxt' ) );

		$db_deleted = false;
		$db_renamed = false;
		$db_action = esc_html( sanitize_text_field( wp_unslash( $_GET[ 'action' ] ) ) );

		switch ( $db_action ) :

			case 'rename':

				if ( !function_exists( 'get_filesystem_method' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}

				$method = get_filesystem_method();
				if ( 'direct' === $method ) {
					WP_Filesystem();
				}

				if ( !is_wp_error( $GLOBALS['wp_filesystem']->move( $db_link, ABSPATH . 'robots_old.txt' ) ) ) {
					wp_redirect( $db_self . '&action=renamed' . $nonce_GET );
				} else {
					wp_redirect( $db_self );
				}

				break;

			case 'renamed':

				$db_renamed = true;

				break;

			case 'delete':

				wp_delete_file( $db_link );
				wp_redirect( $db_self . '&action=deleted' . $nonce_GET );

				break;

			case 'deleted':

				$db_deleted = true;

				break;

			default:

				wp_redirect( $db_self );

				break;

		endswitch;

	endif;

	$custom_rules        = get_option( 'db_robots_custom'        );
	$custom_rules_google = get_option( 'db_robots_custom_google' );
	$if_yandex           = get_option( 'db_robots_if_yandex'     );
	$custom_rules_yandex = get_option( 'db_robots_custom_yandex' );
	$custom_rules_other  = get_option( 'db_robots_custom_other'  );

	if ( isset( $_POST[ 'submit' ] ) && 
		 isset( $_POST[ $d . '_nonce' ] ) &&
		 wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $d . '_nonce' ] ) ), sanitize_text_field( $d ) ) ) :

		if ( function_exists( 'current_user_can' ) &&
			 !current_user_can( 'manage_options' ) )
				die( esc_html_e( 'Error: You do not have the permission to update robots.txt file', 'db-robotstxt' ) );

		if ( isset( $_POST[ 'custom_rules' ] ) ) :
			$custom_rules = esc_html( sanitize_textarea_field( wp_unslash( $_POST[ 'custom_rules' ] ) ) );
			update_option( 'db_robots_custom', $custom_rules );
		endif;

		if ( isset( $_POST[ 'custom_rules_google' ] ) ) :
			$custom_rules_google = esc_html( sanitize_textarea_field( wp_unslash( $_POST[ 'custom_rules_google' ] ) ) );
			update_option( 'db_robots_custom_google', $custom_rules_google );
		endif;

		if ( isset( $_POST[ 'if_yandex' ] ) ) :
			$if_yandex = esc_html( sanitize_text_field( wp_unslash( $_POST[ 'if_yandex' ] ) ) );
			update_option( 'db_robots_if_yandex', $if_yandex );
		endif;

		if ( isset( $_POST[ 'custom_rules_yandex' ] ) ) :
			$custom_rules_yandex = esc_html( sanitize_textarea_field( wp_unslash( $_POST[ 'custom_rules_yandex' ] ) ) );
			update_option( 'db_robots_custom_yandex', $custom_rules_yandex );
		endif;

		if ( isset( $_POST[ 'custom_rules_other' ] ) ) :
			$custom_rules_other = esc_html( sanitize_textarea_field( wp_unslash( $_POST[ 'custom_rules_other' ] ) ) );
			update_option( 'db_robots_custom_other',  $custom_rules_other  );
		endif;


	endif;

?>

<div class="wrap db-rbt-admin">

	<h1><?php esc_html_e( 'DB Robots.txt', 'db-robotstxt' ); ?></h1>

	<div class="db-rbt-header">

		<div class="db-rbt-header-col">

			<div class="db-rbt-header-col-inner">

				<a href="https://bisteinoff.com/" target="_blank"><img class="db-logo" src="<?php echo esc_url( plugin_dir_url(__FILE__) ) ?>../img/logo.png" width="200" height="42" alt="<?php esc_html_e( "Bisteinoff Web Agency", 'db-robotstxt' ) ?>" title="<?php esc_html_e( "Bisteinoff Web Agency", 'db-robotstxt' ) ?>"></a>

				<h2><?php esc_html_e( 'About', 'db-robotstxt' ); ?></h2>

				<div class="db-rbt-description">
					<p><?php esc_html_e( "DB Robots.txt automatically creates a virtual file robots.txt including special rules for Google and Yandex.", 'db-robotstxt' ) ?></p>
					<p><?php esc_html_e( "You can also add custom rules for Google, Yandex and any other robots or disable Yandex if you don't need it for search engines optimisation.", 'db-robotstxt' ) ?></p>
					<p class="db-copyright">&copy; <?php echo esc_html( gmdate( "Y" ) ) ?> <a href="https://bisteinoff.com/" target="_blank"><?php esc_html_e( "Bisteinoff Web Agency", 'db-robotstxt' ) ?></a></p>
				</div>

			</div>

		</div>

		<div class="db-rbt-header-col">

			<div class="db-rbt-header-col-inner">

				<h2><?php esc_html_e( 'Link', 'db-robotstxt' ); ?></h2>

				<div class="db-rbt-link">
					<p><?php esc_html_e( 'You will find the file here:', 'db-robotstxt' ); ?></p>
					<p><a class="db-rbt-button" href="/robots.txt" target="_blank" title="robots.txt"><?php echo esc_url( sanitize_text_field( site_url() ) ) ?>/robots.txt</a></p>
				</div>

			</div>

		</div>

	</div>

	<div class="db-rbt-body">

		<h2><?php esc_html_e( 'Settings', 'db-robotstxt' ); ?></h2>

		<form name="db-robotstxt" method="post" action="?page=<?php echo esc_html( sanitize_text_field( $d ) ) ?>&amp;updated=true">

			<table class="form-table">
				<?php
					if ( file_exists ( $db_link ) ) :
			?>

				<tr valign="top">
					<th scope="row" class="db-rbt-error">
						<p><?php esc_html_e( 'Attention!', 'db-robotstxt' ) ?> <?php esc_html_e( 'File robots.txt already exists', 'db-robotstxt' ) ?></p>
						<p class="td-rbt-field-description"><?php esc_html_e( 'If you want to replace it with the virtual file, you need to rename or delete the existing one', 'db-robotstxt' ) ?></p>
					</th>
					<td class="db-rbt-error">
						<a class="db-rbt-button" href="<?php echo esc_html( sanitize_text_field ( $db_self ) ) ?>&action=rename<?php echo esc_html( sanitize_text_field( $nonce_GET ) ) ?>"><?php esc_html_e( 'Rename to', 'db-robotstxt' ) ?> robots_old.txt</a>
						<a class="db-rbt-button" href="<?php echo esc_html( sanitize_text_field ( $db_self ) ) ?>&action=delete<?php echo esc_html( sanitize_text_field( $nonce_GET ) ) ?>"><?php esc_html_e( 'Delete old', 'db-robotstxt' ) ?> robots.txt</a>
					</td>
				</tr>
				<?php

					else:

						if ( $db_renamed ) :
				?>
				<tr valign="top">
					<th colspan="2" scope="row" class="db-rbt-success">
						<p>
							<?php esc_html_e( 'File robots.txt has been sucessfully renamed. Now the virtual robots.txt works fine.', 'db-robotstxt' ) ?>
							<?php esc_html_e( 'You can find the old robots.txt', 'db-robotstxt' ) ?>
							<a href="/robots_old.txt" target="_blank"><?php esc_html_e( 'here', 'db-robotstxt' ) ?></a>
						</p>
					</th>
				</tr>
				<?php
						endif;

						if ( $db_deleted ) :
				?>
				<tr valign="top">
					<th colspan="2" scope="row" class="db-rbt-success">
						<p><?php esc_html_e( 'File robots.txt has been sucessfully deleted. Now the virtual robots.txt works fine.', 'db-robotstxt' ) ?></p>
					</th>
				</tr>
				<?php
						endif;

					endif;
				?>
				<tr valign="top">
					<th scope="row" class="db-rbt-custom">
						<p><?php esc_html_e( 'Regular basic rules', 'db-robotstxt' ) ?> <?php esc_html_e( 'for all search engines', 'db-robotstxt' ) ?></p>
						<p class="td-rbt-field-description"><?php esc_html_e( 'These directives will appear in robots.txt automatically for all User-agents', 'db-robotstxt' ) ?></p>
					</th>
					<td>
						Disallow: /cgi-bin<br />
						Disallow: /?<br />
						Disallow: /wp-<br />
						Disallow: /wp/<br />
						Disallow: */admin/<br />
						Disallow: */login/<br />
						Disallow: *?p=<br />
						Disallow: *&p=<br />
						Disallow: *?s=<br />
						Disallow: *&s=<br />
						Disallow: /search/<br />
						Disallow: /trackback/<br />
						Disallow: */feed/<br />
						Disallow: */rss/<br />
						Disallow: */embed/<br />
						Disallow: */wlwmanifest.xml<br />
						Disallow: /xmlrpc.php<br />
						Disallow: *?*utm*=<br />
						Disallow: *?*openstat=<br />
						Disallow: *?*from=<br />
						Disallow: *?*yclid=<br />
						Disallow: *?*ymclid=<br />
						Disallow: *?*gclid=<br />
						<?php
							if ( defined( 'WC_VERSION' ) && class_exists( 'WooCommerce' ) ) :
						?>
						Disallow: *?*added=<br />
						Disallow: *?*add-to-cart=<br />
						<?php
							endif;
						?>
						<br />
						Allow: /wp-*/uploads/<br />
						Allow: /wp-*.webp<br />
						Allow: /wp-*.avif<br />
						Allow: /wp-*.jpg<br />
						Allow: /wp-*.jpeg<br />
						Allow: /wp-*.gif<br />
						Allow: /wp-*.png<br />
						Allow: /wp-*.svg<br />
						Allow: /wp-*.js<br />
						Allow: /wp-*.css<br />
						Allow: /wp-*.doc<br />
						Allow: /wp-*.docx<br />
						Allow: /wp-*.xls<br />
						Allow: /wp-*.xlsx<br />
						Allow: /wp-*.ppt<br />
						Allow: /wp-*.ppts<br />
						Allow: /wp-*.pptx<br />
						Allow: /wp-*.pdf<br />
						Allow: /wp-*.txt<br />
						Allow: /wp-admin/admin-ajax.php
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="db-rbt-custom">
						<p><?php esc_html_e( 'Custom rules', 'db-robotstxt' ) ?></p>
						<p class="td-rbt-field-description"><?php esc_html_e( 'Add custom rules into your file robots.txt. They will appear in the block "User-agent: *" after the basic directives Disallow and Allow.', 'db-robotstxt' ) ?></p>
					</th>
					<td>
						<textarea id="custom_rules" name="custom_rules" rows="13" cols="100"><?php echo esc_html( sanitize_textarea_field( $custom_rules ) ); ?></textarea>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="db-rbt-custom">
						<p><?php esc_html_e( 'Regular rules', 'db-robotstxt' ) ?> <?php esc_html_e( 'for Google only', 'db-robotstxt' ) ?></p>
						<p class="td-rbt-field-description"><?php esc_html_e( 'These directives will appear in robots.txt automatically in the block "User-agent: Googlebot" after the basic rules', 'db-robotstxt' ) ?></p>
					</th>
					<td>
						Allow: */amp<br />
						Disallow: /feed/turbo<br />
						Disallow: /feed/zen
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="db-rbt-custom">
						<p><?php esc_html_e( 'Custom rules', 'db-robotstxt' ) ?> <?php esc_html_e( 'for Google only', 'db-robotstxt' ) ?></p>
						<p class="td-rbt-field-description"><?php esc_html_e( 'Add custom rules into your file robots.txt. They will appear in the block "User-agent: Googlebot" after the basic directives Disallow and Allow.', 'db-robotstxt' ) ?></p>
					</th>
					<td>
						<textarea id="custom_rules_google" name="custom_rules_google" rows="13" cols="100"><?php echo esc_html( sanitize_textarea_field( $custom_rules_google ) ); ?></textarea>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="db-rbt-custom">
						<p><?php esc_html_e( 'Enable special rules for Yandex', 'db-robotstxt' ) ?></p>
						<p class="td-rbt-field-description"><?php esc_html_e( 'You can disable the rules if the region of your website is not Russia, Belorussia, Kazakhstan or Ukraine where many people use Yandex as a search engine', 'db-robotstxt' ) ?></p>
					</th>
					<td>
						<input type="checkbox" id="if_yandex" name="if_yandex" <?php if ( $if_yandex === 'on') { ?>checked<?php } ?> >
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="db-rbt-custom">
						<p><?php esc_html_e( 'Regular rules', 'db-robotstxt' ) ?> <?php esc_html_e( 'for Yandex only', 'db-robotstxt' ) ?></p>
						<p class="td-rbt-field-description"><?php esc_html_e( 'These directives will appear in robots.txt automatically in the block "User-agent: Yandex" after the basic rules', 'db-robotstxt' ) ?></p>
					</th>
					<td>
						Disallow: */amp<br />
						Allow: /feed/turbo<br />
						Allow: /feed/zen
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="db-rbt-custom">
						<p><?php esc_html_e( 'Custom rules', 'db-robotstxt' ) ?> <?php esc_html_e( 'for Yandex only', 'db-robotstxt' ) ?></p>
						<p class="td-rbt-field-description"><?php esc_html_e( 'Add custom rules into your file robots.txt. They will appear in the block "User-agent: Yandex" after the basic directives Disallow and Allow.', 'db-robotstxt' ) ?></p>
					</th>
					<td>
						<textarea id="custom_rules_yandex" name="custom_rules_yandex" rows="13" cols="100"><?php echo esc_html( sanitize_textarea_field( $custom_rules_yandex ) ); ?></textarea>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="db-rbt-custom">
						<p><?php esc_html_e( 'Other custom rules', 'db-robotstxt' ) ?></p>
						<p class="td-rbt-field-description"><?php esc_html_e( 'Add custom rules into your file robots.txt. They will appear at the end of the file before Sitemap. Here you can add other necessary directives.', 'db-robotstxt' ) ?></p>
					</th>
					<td>
						<textarea id="custom_rules_other" name="custom_rules_other" rows="13" cols="100"><?php echo esc_html( sanitize_textarea_field( $custom_rules_other ) ); ?></textarea>
					</td>
				</tr>
			</table>

			<input type="hidden" name="action" value="update" />

			<input type="hidden" name="<?php echo esc_html( sanitize_text_field( $d ) ) ?>_nonce" value="<?php echo esc_html( sanitize_text_field( $nonce ) ) ?>" />

			<?php submit_button(); ?>

		</form>

	</div>

</div>
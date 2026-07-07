<?php
/**
 * Индексация: IndexNow (Rank Math), панель в админке, ping при публикации.
 *
 * @package proautospec
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'proauc_maybe_setup_indexnow', 5 );

/**
 * Включить модуль Rank Math Instant Indexing и ключ API (один раз).
 */
function proauc_maybe_setup_indexnow() {
	if ( get_option( 'proauc_indexnow_setup_v1' ) ) {
		return;
	}

	$modules = get_option( 'rank_math_modules', array() );
	if ( ! in_array( 'instant-indexing', (array) $modules, true ) ) {
		$modules[] = 'instant-indexing';
		update_option( 'rank_math_modules', $modules );
	}

	$settings = get_option( 'rank-math-options-instant_indexing', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	if ( empty( $settings['bing_post_types'] ) ) {
		$settings['bing_post_types'] = array( 'post', 'page' );
	}
	update_option( 'rank-math-options-instant_indexing', $settings );

	if ( class_exists( '\RankMath\Instant_Indexing\Api' ) ) {
		$api = \RankMath\Instant_Indexing\Api::get();
		if ( ! $api->get_key() ) {
			$api->reset_key();
		}
	}

	update_option( 'proauc_indexnow_setup_v1', 1 );
}

/**
 * @return string[]
 */
function proauc_get_indexable_blog_urls() {
	$urls = array( trailingslashit( home_url( '/blog/' ) ) );

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( $posts as $post_id ) {
		$url = get_permalink( $post_id );
		if ( $url ) {
			$urls[] = $url;
		}
	}

	return array_values( array_unique( $urls ) );
}

/**
 * @param string[] $urls
 * @return array{ok:bool,message:string,code?:int}
 */
function proauc_submit_urls_to_indexnow( array $urls ) {
	$urls = array_values(
		array_filter(
			array_unique( $urls ),
			static function ( $url ) {
				return is_string( $url ) && '' !== $url && str_starts_with( $url, home_url() );
			}
		)
	);

	if ( empty( $urls ) ) {
		return array(
			'ok'      => false,
			'message' => 'Нет URL для отправки.',
		);
	}

	if ( class_exists( '\RankMath\Instant_Indexing\Api' ) ) {
		$api = \RankMath\Instant_Indexing\Api::get();
		if ( ! $api->get_key() ) {
			$api->reset_key();
		}
		$ok = $api->submit( $urls, true );
		return array(
			'ok'      => (bool) $ok,
			'message' => $ok
				? 'IndexNow: отправлено ' . count( $urls ) . ' URL (HTTP ' . $api->get_response_code() . ').'
				: 'IndexNow: ' . $api->get_error(),
			'code'    => $api->get_response_code(),
		);
	}

	return array(
		'ok'      => false,
		'message' => 'Модуль Rank Math Instant Indexing недоступен.',
	);
}

/**
 * Ping IndexNow при публикации статьи блога.
 */
add_action(
	'transition_post_status',
	static function ( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}
		if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
			return;
		}

		$url = get_permalink( $post );
		if ( ! $url ) {
			return;
		}

		proauc_submit_urls_to_indexnow( array( $url ) );
	},
	20,
	3
);

add_action( 'wp_ajax_proauc_submit_blog_indexnow', 'proauc_ajax_submit_blog_indexnow' );

function proauc_ajax_submit_blog_indexnow() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Недостаточно прав.' ), 403 );
	}
	check_ajax_referer( 'proauc_metrika_admin_nonce', 'nonce' );

	$result = proauc_submit_urls_to_indexnow( proauc_get_indexable_blog_urls() );
	if ( ! empty( $result['ok'] ) ) {
		wp_send_json_success( $result );
	}
	wp_send_json_error( $result, 400 );
}

add_action( 'admin_footer', 'proauc_indexing_admin_panel_html', 25 );

function proauc_indexing_admin_panel_html() {
	static $done = false;
	if ( $done ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'toplevel_page_' . PROAUC_METRIKA_MENU_SLUG !== $screen->id ) {
		return;
	}

	$done = true;

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'future' ),
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$key_url = '';
	if ( class_exists( '\RankMath\Instant_Indexing\Api' ) ) {
		$key_url = \RankMath\Instant_Indexing\Api::get()->get_key_location();
	}
	?>
	<script>
	(function($){
		var idxNonce = <?php echo wp_json_encode( wp_create_nonce( 'proauc_metrika_admin_nonce' ) ); ?>;

		function esc(s) {
			return $('<div/>').text(s == null ? '' : s).html();
		}

		$(function(){
			var $panel = $('#proauc-metrika-panel-wrap .inside');
			if (!$panel.length) return;

			var html = '<div id="proauc-indexing-wrap" style="margin-top:16px;padding-top:16px;border-top:1px solid #dcdcde;">'
				+ '<h3 style="margin:0 0 8px;">Индексация блога</h3>'
				+ '<p style="margin:0 0 12px;">IndexNow уведомляет Яндекс и Bing о новых URL. При публикации статьи URL уходит автоматически.</p>'
				+ '<p style="margin:0 0 12px;">'
				+ '<strong>Карты:</strong> <a href="<?php echo esc_url( home_url( '/post-sitemap.xml' ) ); ?>" target="_blank" rel="noopener">post-sitemap.xml</a>'
				+ ' · <a href="<?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?>" target="_blank" rel="noopener">sitemap_index.xml</a>'
				<?php if ( $key_url ) : ?>
				+ '<br><strong>IndexNow key:</strong> <code><?php echo esc_js( $key_url ); ?></code>'
				<?php endif; ?>
				+ '</p>'
				+ '<p style="margin:0 0 12px;">'
				+ '<button type="button" class="button button-primary" id="proauc-indexnow-submit-all">Отправить все статьи в IndexNow</button> '
				+ '<span id="proauc-indexnow-status" style="margin-left:8px;"></span>'
				+ '</p>'
				+ '<p style="margin:0 0 8px;color:#50575e;"><strong>Вебмастер:</strong> переобход <code>post-sitemap.xml</code> и отдельных URL из таблицы ниже.</p>'
				+ '<table class="widefat striped" style="margin-top:8px;"><thead><tr><th>Статус</th><th>Дата</th><th>URL</th></tr></thead><tbody>';

			<?php foreach ( $posts as $p ) : ?>
			html += '<tr><td><?php echo esc_js( $p->post_status ); ?></td><td><?php echo esc_js( substr( $p->post_date, 0, 10 ) ); ?></td><td><code><?php echo esc_js( trailingslashit( home_url( '/' . $p->post_name ) ) ); ?></code></td></tr>';
			<?php endforeach; ?>

			html += '</tbody></table></div>';
			$panel.append(html);

			$('#proauc-indexnow-submit-all').on('click', function(){
				var $btn = $(this);
				$btn.prop('disabled', true);
				$('#proauc-indexnow-status').text('Отправка…');
				$.post(ajaxurl, {
					action: 'proauc_submit_blog_indexnow',
					nonce: idxNonce
				}).done(function(res){
					$('#proauc-indexnow-status').text(res.data && res.data.message ? res.data.message : 'Готово');
				}).fail(function(xhr){
					var data = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : { message: 'Ошибка' };
					$('#proauc-indexnow-status').text(data.message || 'Ошибка');
				}).always(function(){
					$btn.prop('disabled', false);
				});
			});
		});
	})(jQuery);
	</script>
	<?php
}

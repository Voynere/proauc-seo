<?php
/**
 * Яндекс.Метрика: SEO-дашборд (Stat API) — блог, каталог, Дзен.
 *
 * @package proautospec
 */

defined( 'ABSPATH' ) || exit;

define( 'PROAUC_METRIKA_DASHBOARD_CACHE_TTL', HOUR_IN_SECONDS );

/**
 * Посадочные каталога для отчёта «статья → каталог».
 */
function proauc_metrika_catalog_paths() {
	return array(
		'/avto-iz-yaponii/',
		'/avto-iz-korei/',
		'/avto-iz-kitaya/',
		'/spectehnika/',
		'/mototsiklyi/',
		'/kontaktyi/',
	);
}

/**
 * Slug'и опубликованных статей блога (корневые URL).
 */
function proauc_metrika_blog_slugs() {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$slugs = array();
	foreach ( $posts as $post_id ) {
		$slug = get_post_field( 'post_name', $post_id );
		if ( is_string( $slug ) && '' !== $slug ) {
			$slugs[] = $slug;
		}
	}

	return $slugs;
}

/**
 * @param array<string, mixed> $params Query params for Stat API.
 */
function proauc_metrika_stat_request( array $params ) {
	$token   = proauc_get_metrika_oauth_token();
	$counter = proauc_get_metrika_counter_id();

	if ( '' === $token ) {
		return new WP_Error( 'proauc_metrika_no_token', 'OAuth-токен не задан.' );
	}

	$params['ids'] = (string) $counter;

	$url = add_query_arg( $params, 'https://api-metrika.yandex.net/stat/v1/data' );

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 25,
			'headers' => array(
				'Authorization' => 'OAuth ' . $token,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		$message = is_array( $body ) && ! empty( $body['message'] ) ? $body['message'] : 'HTTP ' . $code;
		return new WP_Error( 'proauc_metrika_api', $message, array( 'status' => $code ) );
	}

	return is_array( $body ) ? $body : array();
}

/**
 * Разбор строк Stat API в map path => metrics.
 *
 * @param array<string, mixed> $body
 * @return array<string, array{pageviews:int,users:int}>
 */
function proauc_metrika_parse_path_rows( array $body ) {
	$out = array();

	if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
		return $out;
	}

	foreach ( $body['data'] as $row ) {
		if ( ! is_array( $row ) || empty( $row['dimensions'] ) || ! is_array( $row['dimensions'] ) ) {
			continue;
		}
		$dim = $row['dimensions'][0];
		$path = '';
		if ( is_array( $dim ) && ! empty( $dim['name'] ) ) {
			$path = (string) $dim['name'];
		} elseif ( is_string( $dim ) ) {
			$path = $dim;
		}
		if ( '' === $path ) {
			continue;
		}
		$metrics = isset( $row['metrics'] ) && is_array( $row['metrics'] ) ? $row['metrics'] : array();
		$out[ $path ] = array(
			'pageviews' => isset( $metrics[0] ) ? (int) round( (float) $metrics[0] ) : 0,
			'users'     => isset( $metrics[1] ) ? (int) round( (float) $metrics[1] ) : 0,
		);
	}

	return $out;
}

/**
 * Сумма метрик по списку путей (точное совпадение или вхождение slug).
 *
 * @param array<string, array{pageviews:int,users:int}> $paths_map
 * @param string[]                                      $needles Slug или path prefix.
 */
function proauc_metrika_sum_paths( array $paths_map, array $needles ) {
	$pageviews = 0;
	$users     = 0;
	$rows      = array();

	foreach ( $paths_map as $path => $metrics ) {
		foreach ( $needles as $needle ) {
			$hit = ( $path === $needle )
				|| ( str_contains( $path, '/' . trim( $needle, '/' ) . '/' ) )
				|| ( str_contains( $path, trim( $needle, '/' ) ) );
			if ( ! $hit ) {
				continue;
			}
			$pageviews += $metrics['pageviews'];
			$users     += $metrics['users'];
			$rows[ $path ] = $metrics;
			break;
		}
	}

	return array(
		'pageviews' => $pageviews,
		'users'     => $users,
		'rows'      => $rows,
	);
}

/**
 * @param bool $force Skip transient cache.
 * @return array<string, mixed>
 */
function proauc_metrika_get_dashboard_data( $force = false ) {
	if ( ! proauc_metrika_is_configured() ) {
		return array(
			'ok'      => false,
			'message' => 'OAuth-токен не задан. Заполните поле на этой странице и сохраните.',
		);
	}

	$cache_key = 'proauc_metrika_dashboard_v1';
	if ( ! $force ) {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			$cached['cached'] = true;
			return $cached;
		}
	}

	$blog_slugs     = proauc_metrika_blog_slugs();
	$catalog_paths  = proauc_metrika_catalog_paths();
	$blog_needles   = array_merge( array( '/blog/' ), $blog_slugs );
	$catalog_needles = $catalog_paths;

	$common = array(
		'metrics'    => 'ym:s:pageviews,ym:s:users',
		'dimensions' => 'ym:s:URLPath',
		'sort'       => '-ym:s:pageviews',
		'limit'      => 200,
	);

	$week  = proauc_metrika_stat_request( array_merge( $common, array( 'date1' => '7daysAgo', 'date2' => 'today' ) ) );
	$month = proauc_metrika_stat_request( array_merge( $common, array( 'date1' => '30daysAgo', 'date2' => 'today' ) ) );

	if ( is_wp_error( $week ) ) {
		return array(
			'ok'      => false,
			'message' => $week->get_error_message(),
		);
	}
	if ( is_wp_error( $month ) ) {
		return array(
			'ok'      => false,
			'message' => $month->get_error_message(),
		);
	}

	$week_map  = proauc_metrika_parse_path_rows( $week );
	$month_map = proauc_metrika_parse_path_rows( $month );

	$blog_week    = proauc_metrika_sum_paths( $week_map, $blog_needles );
	$blog_month   = proauc_metrika_sum_paths( $month_map, $blog_needles );
	$catalog_week = proauc_metrika_sum_paths( $week_map, $catalog_needles );
	$catalog_month = proauc_metrika_sum_paths( $month_map, $catalog_needles );

	$dzen_week = proauc_metrika_stat_request(
		array(
			'metrics'    => 'ym:s:pageviews,ym:s:users',
			'dimensions' => 'ym:s:UTMSource',
			'date1'      => '30daysAgo',
			'date2'      => 'today',
			'filters'    => "ym:s:UTMSource=='dzen'",
			'limit'      => 5,
		)
	);

	$dzen = array( 'pageviews' => 0, 'users' => 0 );
	if ( ! is_wp_error( $dzen_week ) && ! empty( $dzen_week['totals'] ) && is_array( $dzen_week['totals'] ) ) {
		$dzen['pageviews'] = isset( $dzen_week['totals'][0] ) ? (int) round( (float) $dzen_week['totals'][0] ) : 0;
		$dzen['users']     = isset( $dzen_week['totals'][1] ) ? (int) round( (float) $dzen_week['totals'][1] ) : 0;
	}

	$organic_week = proauc_metrika_stat_request(
		array(
			'metrics'    => 'ym:s:pageviews,ym:s:users',
			'dimensions' => 'ym:s:URLPath',
			'date1'      => '7daysAgo',
			'date2'      => 'today',
			'filters'    => "ym:s:trafficSource=='organic'",
			'sort'       => '-ym:s:pageviews',
			'limit'      => 200,
		)
	);

	$organic_blog_week = array( 'pageviews' => 0, 'users' => 0, 'rows' => array() );
	if ( ! is_wp_error( $organic_week ) ) {
		$organic_blog_week = proauc_metrika_sum_paths( proauc_metrika_parse_path_rows( $organic_week ), $blog_needles );
	}

	$top_blog = array();
	foreach ( $blog_month['rows'] as $path => $metrics ) {
		$top_blog[] = array(
			'path'      => $path,
			'pageviews' => $metrics['pageviews'],
			'users'     => $metrics['users'],
		);
	}
	usort(
		$top_blog,
		static function ( $a, $b ) {
			return $b['pageviews'] <=> $a['pageviews'];
		}
	);
	$top_blog = array_slice( $top_blog, 0, 15 );

	$top_catalog = array();
	foreach ( $catalog_month['rows'] as $path => $metrics ) {
		$top_catalog[] = array(
			'path'      => $path,
			'pageviews' => $metrics['pageviews'],
			'users'     => $metrics['users'],
		);
	}
	usort(
		$top_catalog,
		static function ( $a, $b ) {
			return $b['pageviews'] <=> $a['pageviews'];
		}
	);

	$data = array(
		'ok'              => true,
		'generated_at'    => current_time( 'mysql' ),
		'counter_id'      => proauc_get_metrika_counter_id(),
		'blog_posts_live' => count( $blog_slugs ),
		'summary'         => array(
			'blog_7d'     => array(
				'pageviews' => $blog_week['pageviews'],
				'users'     => $blog_week['users'],
			),
			'blog_30d'    => array(
				'pageviews' => $blog_month['pageviews'],
				'users'     => $blog_month['users'],
			),
			'catalog_7d'  => array(
				'pageviews' => $catalog_week['pageviews'],
				'users'     => $catalog_week['users'],
			),
			'catalog_30d' => array(
				'pageviews' => $catalog_month['pageviews'],
				'users'     => $catalog_month['users'],
			),
			'organic_blog_7d' => array(
				'pageviews' => $organic_blog_week['pageviews'],
				'users'     => $organic_blog_week['users'],
			),
			'dzen_30d'    => $dzen,
		),
		'top_blog'        => $top_blog,
		'top_catalog'     => $top_catalog,
		'cached'          => false,
	);

	set_transient( $cache_key, $data, PROAUC_METRIKA_DASHBOARD_CACHE_TTL );

	return $data;
}

add_action( 'wp_ajax_proauc_metrika_dashboard', 'proauc_metrika_ajax_dashboard' );

function proauc_metrika_ajax_dashboard() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Недостаточно прав.' ), 403 );
	}
	check_ajax_referer( 'proauc_metrika_admin_nonce', 'nonce' );

	$force = ! empty( $_POST['refresh'] );
	$data  = proauc_metrika_get_dashboard_data( $force );

	if ( empty( $data['ok'] ) ) {
		wp_send_json_error( $data, 400 );
	}

	wp_send_json_success( $data );
}

add_action( 'admin_footer', 'proauc_metrika_dashboard_panel_html', 20 );

function proauc_metrika_dashboard_panel_html() {
	static $done = false;
	if ( $done ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'toplevel_page_' . PROAUC_METRIKA_MENU_SLUG !== $screen->id ) {
		return;
	}

	$done = true;
	?>
	<script>
	(function($){
		var dashNonce = <?php echo wp_json_encode( wp_create_nonce( 'proauc_metrika_admin_nonce' ) ); ?>;

		function esc(s) {
			return $('<div/>').text(s == null ? '' : s).html();
		}

		function metricCard(title, block) {
			block = block || {};
			return '<div style="flex:1;min-width:130px;padding:12px;background:#f0f6fc;border-radius:6px;">'
				+ '<strong>' + esc(title) + '</strong><br>'
				+ esc(block.pageviews || 0) + ' просм. · ' + esc(block.users || 0) + ' польз.'
				+ '</div>';
		}

		function pathTable(rows, title) {
			if (!rows || !rows.length) {
				return '<h3 style="margin:16px 0 8px;">' + esc(title) + '</h3><p style="color:#666;">Нет данных за период.</p>';
			}
			var t = '<h3 style="margin:16px 0 8px;">' + esc(title) + '</h3>';
			t += '<table class="widefat striped"><thead><tr><th>URL</th><th>Просмотры</th><th>Пользователи</th></tr></thead><tbody>';
			for (var i = 0; i < rows.length; i++) {
				var r = rows[i];
				t += '<tr><td><code>' + esc(r.path) + '</code></td><td>' + esc(r.pageviews) + '</td><td>' + esc(r.users) + '</td></tr>';
			}
			t += '</tbody></table>';
			return t;
		}

		function renderDashboard(d) {
			var $box = $('#proauc-metrika-dashboard');
			if (!d || !d.ok) {
				$box.html('<p style="color:#b32d2e;">' + esc(d && d.message ? d.message : 'Нет данных') + '</p>');
				return;
			}
			var s = d.summary || {};
			var html = '<p style="margin:0 0 12px;color:#50575e;">Счётчик #' + esc(d.counter_id) + ' · статей в индексе WP: ' + esc(d.blog_posts_live) + (d.cached ? ' · <em>из кэша (1 ч)</em>' : '') + '</p>';
			html += '<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;">';
			html += metricCard('Блог (7 дней)', s.blog_7d);
			html += metricCard('Блог (30 дней)', s.blog_30d);
			html += metricCard('Каталог (7 дней)', s.catalog_7d);
			html += metricCard('Каталог (30 дней)', s.catalog_30d);
			html += metricCard('Органика → блог (7 д)', s.organic_blog_7d);
			html += metricCard('Дзен utm (30 д)', s.dzen_30d);
			html += '</div>';
			html += pathTable(d.top_blog, 'Статьи блога — топ за 30 дней');
			html += pathTable(d.top_catalog, 'Посадочные каталога — 30 дней');
			$box.html(html);
		}

		function loadDashboard(refresh) {
			$('#proauc-metrika-dash-status').text('Загрузка…');
			$.post(ajaxurl, {
				action: 'proauc_metrika_dashboard',
				nonce: dashNonce,
				refresh: refresh ? 1 : 0
			}).done(function(res){
				renderDashboard(res.data || {});
				$('#proauc-metrika-dash-status').text('Обновлено ' + (res.data && res.data.generated_at ? res.data.generated_at : ''));
			}).fail(function(xhr){
				var data = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : { message: 'Ошибка запроса' };
				renderDashboard(data);
				$('#proauc-metrika-dash-status').text('');
			});
		}

		$(function(){
			var $panel = $('#proauc-metrika-panel-wrap .inside');
			if (!$panel.length) return;
			$panel.prepend(
				'<div id="proauc-metrika-dashboard-wrap" style="margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #dcdcde;">'
				+ '<h3 style="margin:0 0 8px;">SEO-дашборд</h3>'
				+ '<p style="margin:0 0 12px;">Просмотры статей блога и посадочных каталога (Stat API). Переходы статья → каталог — по сумме URL каталога vs блога.</p>'
				+ '<p style="margin:0 0 12px;">'
				+ '<button type="button" class="button button-primary" id="proauc-metrika-load-dashboard">Загрузить дашборд</button> '
				+ '<button type="button" class="button" id="proauc-metrika-refresh-dashboard">Обновить (сброс кэша)</button> '
				+ '<span id="proauc-metrika-dash-status" style="margin-left:8px;"></span>'
				+ '</p>'
				+ '<div id="proauc-metrika-dashboard"></div>'
				+ '</div>'
			);
			$('#proauc-metrika-load-dashboard').on('click', function(){ loadDashboard(false); });
			$('#proauc-metrika-refresh-dashboard').on('click', function(){ loadDashboard(true); });
		});
	})(jQuery);
	</script>
	<?php
}

<?php
/**
 * Яндекс.Метрика: пункт меню админки (ACF) и OAuth-токен API.
 *
 * @package proautospec
 */

defined( 'ABSPATH' ) || exit;

define( 'PROAUC_METRIKA_MENU_SLUG', 'yandex-metrika-settings' );
define( 'PROAUC_METRIKA_OPTION_TOKEN', 'proauc_metrika_oauth_token' );
define( 'PROAUC_METRIKA_OPTION_COUNTER', 'proauc_metrika_counter_id' );

add_action( 'acf/init', 'proauc_register_metrika_options_page' );
add_action( 'acf/init', 'proauc_register_metrika_fields' );

/**
 * Пункт меню в админке (как «Яндекс Директ» на ferma-dv).
 */
function proauc_register_metrika_options_page() {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title' => 'Яндекс Метрика',
			'menu_title' => 'Яндекс Метрика',
			'menu_slug'  => PROAUC_METRIKA_MENU_SLUG,
			'capability' => 'manage_options',
			'redirect'   => false,
			'icon_url'   => 'dashicons-chart-area',
			'position'   => 58,
		)
	);
}

/**
 * ACF-поля: OAuth-токен и ID счётчика.
 */
function proauc_register_metrika_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_proauc_yandex_metrika',
			'title'                 => 'Яндекс Метрика — API',
			'fields'                => array(
				array(
					'key'          => 'field_proauc_metrika_oauth_token',
					'label'        => 'OAuth-токен',
					'name'         => 'metrika_oauth_token',
					'type'         => 'password',
					'required'     => 0,
					'instructions' => 'Токен доступа к API Метрики (Management / Stat). Права: metrika:read. Fallback: константа PROAUC_METRIKA_OAUTH_TOKEN в wp-config.php.',
				),
				array(
					'key'           => 'field_proauc_metrika_counter_id',
					'label'         => 'ID счётчика',
					'name'          => 'metrika_counter_id',
					'type'          => 'text',
					'required'      => 0,
					'default_value' => '98962652',
					'instructions'  => 'Номер счётчика на сайте (в коде footer: 98962652). Fallback: PROAUC_METRIKA_COUNTER_ID.',
					'wrapper'       => array( 'width' => '50' ),
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => PROAUC_METRIKA_MENU_SLUG,
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		)
	);
}

/**
 * Миграция токена из старой страницы «Настройки → Proauc SEO» (если был сохранён).
 */
add_action( 'acf/init', 'proauc_migrate_legacy_metrika_options', 20 );

function proauc_migrate_legacy_metrika_options() {
	if ( ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
		return;
	}
	if ( get_option( 'proauc_metrika_migrated_v1' ) ) {
		return;
	}

	$legacy_token = get_option( PROAUC_METRIKA_OPTION_TOKEN, '' );
	if ( is_string( $legacy_token ) && '' !== trim( $legacy_token ) && '' === proauc_get_metrika_option_string( 'metrika_oauth_token', '' ) ) {
		update_field( 'metrika_oauth_token', trim( $legacy_token ), 'option' );
	}

	$legacy_counter = get_option( PROAUC_METRIKA_OPTION_COUNTER, '' );
	if ( '' !== $legacy_counter && '' === proauc_get_metrika_option_string( 'metrika_counter_id', '' ) ) {
		update_field( 'metrika_counter_id', (string) $legacy_counter, 'option' );
	}

	update_option( 'proauc_metrika_migrated_v1', 1 );
}

/**
 * @param string $acf_name  Имя ACF-поля.
 * @param string $constant  Имя константы wp-config (без define).
 */
function proauc_get_metrika_option_string( $acf_name, $constant = '' ) {
	static $cache = array();
	$key = $acf_name . '|' . $constant;
	if ( array_key_exists( $key, $cache ) ) {
		return $cache[ $key ];
	}

	$value = '';
	if ( function_exists( 'get_field' ) ) {
		$val = get_field( $acf_name, 'option' );
		if ( is_string( $val ) && '' !== trim( $val ) ) {
			$value = trim( $val );
		} elseif ( is_numeric( $val ) ) {
			$value = (string) $val;
		}
	}

	if ( '' === $value && $constant && defined( $constant ) ) {
		$const_val = constant( $constant );
		if ( is_string( $const_val ) && '' !== trim( $const_val ) ) {
			$value = trim( $const_val );
		} elseif ( is_numeric( $const_val ) ) {
			$value = (string) $const_val;
		}
	}

	$cache[ $key ] = $value;
	return $value;
}

function proauc_get_metrika_oauth_token() {
	return proauc_get_metrika_option_string( 'metrika_oauth_token', 'PROAUC_METRIKA_OAUTH_TOKEN' );
}

function proauc_get_metrika_counter_id() {
	$raw = proauc_get_metrika_option_string( 'metrika_counter_id', 'PROAUC_METRIKA_COUNTER_ID' );
	if ( '' !== $raw && ctype_digit( $raw ) ) {
		return (int) $raw;
	}
	return 98962652;
}

function proauc_metrika_is_configured() {
	return '' !== proauc_get_metrika_oauth_token();
}

/**
 * Проверка токена: запрос списка счётчиков Management API.
 */
function proauc_metrika_health_check() {
	$token = proauc_get_metrika_oauth_token();
	if ( '' === $token ) {
		return array(
			'ok'      => false,
			'message' => 'OAuth-токен не задан. Заполните поле выше и нажмите «Обновить».',
		);
	}

	$response = wp_remote_get(
		'https://api-metrika.yandex.net/management/v1/counters',
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'OAuth ' . $token,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return array(
			'ok'      => false,
			'message' => 'Ошибка HTTP: ' . $response->get_error_message(),
		);
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		$err = is_array( $body ) && ! empty( $body['message'] ) ? $body['message'] : 'HTTP ' . $code;
		return array(
			'ok'      => false,
			'message' => 'API Метрики: ' . $err,
		);
	}

	$counters = isset( $body['counters'] ) && is_array( $body['counters'] ) ? $body['counters'] : array();
	$target   = proauc_get_metrika_counter_id();
	$found    = false;
	$names    = array();

	foreach ( $counters as $counter ) {
		if ( ! is_array( $counter ) ) {
			continue;
		}
		$id = isset( $counter['id'] ) ? (int) $counter['id'] : 0;
		if ( $id === $target ) {
			$found = true;
		}
		if ( $id && ! empty( $counter['name'] ) ) {
			$names[] = $id . ' — ' . $counter['name'];
		}
	}

	$msg = 'Подключение успешно. Доступно счётчиков: ' . count( $counters ) . '.';
	if ( $found ) {
		$msg .= ' Счётчик #' . $target . ' найден в аккаунте.';
	} else {
		$msg .= ' Счётчик #' . $target . ' не найден среди доступных — проверьте ID.';
	}

	return array(
		'ok'       => true,
		'message'  => $msg,
		'counters' => array_slice( $names, 0, 10 ),
	);
}

add_action( 'wp_ajax_proauc_metrika_test_connection', 'proauc_metrika_ajax_test_connection' );

function proauc_metrika_ajax_test_connection() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Недостаточно прав.' ), 403 );
	}
	check_ajax_referer( 'proauc_metrika_admin_nonce', 'nonce' );

	$result = proauc_metrika_health_check();
	if ( ! empty( $result['ok'] ) ) {
		wp_send_json_success( $result );
	}
	wp_send_json_error( $result, 400 );
}

add_action( 'admin_footer', 'proauc_metrika_admin_panel_html' );

function proauc_metrika_admin_panel_html() {
	static $done = false;
	if ( $done ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'toplevel_page_' . PROAUC_METRIKA_MENU_SLUG !== $screen->id ) {
		return;
	}

	$done  = true;
	$nonce = wp_create_nonce( 'proauc_metrika_admin_nonce' );
	?>
	<div id="proauc-metrika-panel-wrap" style="display:none;max-width:100%;box-sizing:border-box;">
		<div id="proauc-metrika-panel" class="postbox" style="margin-top:12px;">
			<div class="postbox-header"><h2 class="hndle">Проверка API</h2></div>
			<div class="inside" style="padding:12px;">
				<p style="margin:0 0 12px;">
					После сохранения токена нажмите «Проверить API» — запрос к Management API (список счётчиков).
				</p>
				<p style="margin:0 0 12px;">
					<button type="button" class="button button-primary" id="proauc-metrika-test-btn">Проверить API</button>
					<span id="proauc-metrika-test-status" style="margin-left:8px;"></span>
				</p>
				<div id="proauc-metrika-test-result"></div>
			</div>
		</div>
	</div>
	<script>
	(function($){
		var nonce = <?php echo wp_json_encode( $nonce ); ?>;

		function esc(s) {
			return $('<div/>').text(s == null ? '' : s).html();
		}

		function renderResult(data, isError) {
			var $r = $('#proauc-metrika-test-result');
			if (!data) { $r.empty(); return; }
			var html = '<div style="padding:10px;border-radius:6px;border:1px solid ' + (isError ? '#f5c2c7' : '#badbcc') + ';background:' + (isError ? '#f8d7da' : '#d1e7dd') + ';">';
			html += '<p style="margin:0 0 8px;"><strong>' + esc(data.message || '') + '</strong></p>';
			if (data.counters && data.counters.length) {
				html += '<ul style="margin:0;padding-left:18px;">';
				for (var i = 0; i < data.counters.length; i++) {
					html += '<li>' + esc(data.counters[i]) + '</li>';
				}
				html += '</ul>';
			}
			html += '</div>';
			$r.html(html);
		}

		$('#proauc-metrika-test-btn').on('click', function(){
			var $btn = $(this);
			$btn.prop('disabled', true);
			$('#proauc-metrika-test-status').text('Запрос…');
			$.post(ajaxurl, {
				action: 'proauc_metrika_test_connection',
				nonce: nonce
			}).done(function(res){
				renderResult(res.data || {}, false);
				$('#proauc-metrika-test-status').text('Готово');
			}).fail(function(xhr){
				var data = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : { message: 'Ошибка запроса' };
				renderResult(data, true);
				$('#proauc-metrika-test-status').text('');
			}).always(function(){
				$btn.prop('disabled', false);
			});
		});

		// ACF рендерит форму после загрузки — панель вставляем под неё.
		var $acf = $('.acf-settings-wrap, #acf-form');
		if ($acf.length) {
			$('#proauc-metrika-panel-wrap').insertAfter($acf.first()).show();
		} else {
			$('#proauc-metrika-panel-wrap').show();
		}
	})(jQuery);
	</script>
	<?php
}

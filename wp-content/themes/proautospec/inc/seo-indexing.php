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

add_action( 'init', 'proauc_maybe_seed_indexing_checklist', 6 );

/**
 * Начальные статусы чеклиста (07.07.2026).
 */
function proauc_maybe_seed_indexing_checklist() {
	if ( get_option( 'proauc_indexing_checklist_seed_v1' ) ) {
		return;
	}

	$checked = '2026-07-07';
	$rows    = array(
		'blog' => array(
			'yandex'    => '',
			'google'    => '',
			'webmaster' => 'wait',
			'note'      => 'IndexNow 07.07',
			'checked'   => $checked,
		),
		'obzor-byd-seal-iz-kitaya' => array(
			'yandex'    => 'wait',
			'google'    => '',
			'webmaster' => 'wait',
			'note'      => 'IndexNow 07.07; в post-sitemap',
			'checked'   => $checked,
		),
	);

	$future_slugs = array(
		'kejs-pokupka-kia-sorento-iz-korei',
		'obzor-komatsu-pc200-iz-yaponii',
		'dostavka-avto-v-regiony-dalnego-vostoka',
		'obzor-toyota-alphard-iz-yaponii',
		'obzor-nissan-x-trail-iz-yaponii',
		'obzor-honda-vezel-iz-yaponii',
		'obzor-kia-carnival-iz-korei',
		'byd-seal-i-zeekr-001-sravnenie',
	);

	foreach ( $future_slugs as $slug ) {
		$rows[ $slug ] = array(
			'yandex'    => 'scheduled',
			'google'    => 'scheduled',
			'webmaster' => 'scheduled',
			'note'      => '',
			'checked'   => '',
		);
	}

	update_option( 'proauc_indexing_checklist', $rows, false );
	update_option( 'proauc_indexing_checklist_seed_v1', 1 );
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

/**
 * @return array<string, array<string, string>>
 */
function proauc_get_indexing_checklist_saved() {
	$saved = get_option( 'proauc_indexing_checklist', array() );
	return is_array( $saved ) ? $saved : array();
}

/**
 * @return array<int, array<string, string>>
 */
function proauc_get_indexing_checklist_rows() {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'future' ),
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'ASC',
		)
	);

	$saved = proauc_get_indexing_checklist_saved();
	$rows  = array();

	$hub_slug = 'blog';
	$hub      = array_merge(
		array(
			'slug'      => $hub_slug,
			'title'     => 'Хаб /blog/',
			'url'       => trailingslashit( home_url( '/blog/' ) ),
			'wp_status' => 'publish',
			'date'      => '—',
		),
		array(
			'yandex'    => '',
			'google'    => '',
			'webmaster' => '',
			'note'      => '',
			'checked'   => '',
		),
		$saved[ $hub_slug ] ?? array()
	);
	$rows[] = $hub;

	foreach ( $posts as $post ) {
		$slug = $post->post_name;
		$rows[] = array_merge(
			array(
				'slug'      => $slug,
				'title'     => get_the_title( $post ),
				'url'       => trailingslashit( home_url( '/' . $slug ) ),
				'wp_status' => $post->post_status,
				'date'      => substr( $post->post_date, 0, 10 ),
			),
			array(
				'yandex'    => '',
				'google'    => '',
				'webmaster' => '',
				'note'      => '',
				'checked'   => '',
			),
			$saved[ $slug ] ?? array()
		);
	}

	return $rows;
}

/**
 * @param array<string, string> $row
 */
function proauc_save_indexing_checklist_row( $slug, array $row ) {
	$slug = sanitize_title( $slug );
	if ( '' === $slug ) {
		return false;
	}

	$allowed = array( '', 'yes', 'wait', 'no', 'scheduled', 'na' );
	$data    = array(
		'yandex'    => in_array( $row['yandex'] ?? '', $allowed, true ) ? $row['yandex'] : '',
		'google'    => in_array( $row['google'] ?? '', $allowed, true ) ? $row['google'] : '',
		'webmaster' => in_array( $row['webmaster'] ?? '', $allowed, true ) ? $row['webmaster'] : '',
		'note'      => sanitize_text_field( $row['note'] ?? '' ),
		'checked'   => sanitize_text_field( $row['checked'] ?? current_time( 'Y-m-d' ) ),
	);

	$saved         = proauc_get_indexing_checklist_saved();
	$saved[ $slug ] = $data;
	update_option( 'proauc_indexing_checklist', $saved, false );

	return true;
}

/**
 * @return array{indexed:int,pending:int,scheduled:int,total:int}
 */
function proauc_indexing_checklist_summary() {
	$rows    = proauc_get_indexing_checklist_rows();
	$indexed = 0;
	$pending = 0;
	$sched   = 0;

	foreach ( $rows as $row ) {
		if ( 'future' === ( $row['wp_status'] ?? '' ) ) {
			++$sched;
			continue;
		}
		if ( 'yes' === ( $row['yandex'] ?? '' ) ) {
			++$indexed;
		} elseif ( in_array( $row['yandex'] ?? '', array( 'wait', '' ), true ) ) {
			++$pending;
		}
	}

	return array(
		'indexed'   => $indexed,
		'pending'   => $pending,
		'scheduled' => $sched,
		'total'     => count( $rows ),
	);
}

add_action( 'wp_ajax_proauc_save_indexing_checklist', 'proauc_ajax_save_indexing_checklist' );

function proauc_ajax_save_indexing_checklist() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Недостаточно прав.' ), 403 );
	}
	check_ajax_referer( 'proauc_metrika_admin_nonce', 'nonce' );

	$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
	if ( '' === $slug ) {
		wp_send_json_error( array( 'message' => 'Пустой slug.' ), 400 );
	}

	$row = array(
		'yandex'    => isset( $_POST['yandex'] ) ? sanitize_key( wp_unslash( $_POST['yandex'] ) ) : '',
		'google'    => isset( $_POST['google'] ) ? sanitize_key( wp_unslash( $_POST['google'] ) ) : '',
		'webmaster' => isset( $_POST['webmaster'] ) ? sanitize_key( wp_unslash( $_POST['webmaster'] ) ) : '',
		'note'      => isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '',
		'checked'   => current_time( 'Y-m-d' ),
	);

	proauc_save_indexing_checklist_row( $slug, $row );

	wp_send_json_success(
		array(
			'message' => 'Сохранено.',
			'summary' => proauc_indexing_checklist_summary(),
			'checked' => $row['checked'],
		)
	);
}

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

function proauc_indexing_status_options() {
	return array(
		''          => '—',
		'yes'       => '✅ В индексе',
		'wait'      => '⏳ Ожидает',
		'no'        => '❌ Нет',
		'scheduled' => '📅 Не опублик.',
		'na'        => 'н/д',
	);
}

function proauc_indexing_admin_panel_html() {
	static $done = false;
	if ( $done ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'toplevel_page_' . PROAUC_METRIKA_MENU_SLUG !== $screen->id ) {
		return;
	}

	$done    = true;
	$rows    = proauc_get_indexing_checklist_rows();
	$summary = proauc_indexing_checklist_summary();
	$nonce   = wp_create_nonce( 'proauc_metrika_admin_nonce' );
	$opts    = proauc_indexing_status_options();

	$key_url = '';
	if ( class_exists( '\RankMath\Instant_Indexing\Api' ) ) {
		$key_url = \RankMath\Instant_Indexing\Api::get()->get_key_location();
	}
	?>
	<div id="proauc-indexing-wrap" style="display:none;max-width:100%;box-sizing:border-box;margin-top:12px;">
		<div class="postbox">
			<div class="postbox-header"><h2 class="hndle">Чеклист индексации блога</h2></div>
			<div class="inside" style="padding:12px;">
				<p style="margin:0 0 12px;">
					Статусы сохраняются в WordPress. Локальная копия для отчёта: <code>seov/indexing-checklist.md</code>.
				</p>
				<p style="margin:0 0 12px;" id="proauc-indexing-summary">
					<strong>KPI:</strong>
					в индексе Яндекс — <span id="proauc-idx-count"><?php echo (int) $summary['indexed']; ?></span>;
					ожидают — <span id="proauc-idx-pending"><?php echo (int) $summary['pending']; ?></span>;
					по расписанию — <span id="proauc-idx-sched"><?php echo (int) $summary['scheduled']; ?></span>;
					всего строк — <?php echo (int) $summary['total']; ?>.
				</p>
				<p style="margin:0 0 12px;">
					<strong>Карты:</strong>
					<a href="<?php echo esc_url( home_url( '/post-sitemap.xml' ) ); ?>" target="_blank" rel="noopener">post-sitemap.xml</a>
					· <a href="<?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?>" target="_blank" rel="noopener">sitemap_index.xml</a>
					<?php if ( $key_url ) : ?>
						<br><strong>IndexNow:</strong> <code><?php echo esc_html( $key_url ); ?></code>
					<?php endif; ?>
				</p>
				<p style="margin:0 0 12px;">
					<button type="button" class="button button-primary" id="proauc-indexnow-submit-all">Отправить опубликованные в IndexNow</button>
					<span id="proauc-indexnow-status" style="margin-left:8px;"></span>
				</p>
				<div style="overflow-x:auto;">
					<table class="widefat striped" id="proauc-indexing-table">
						<thead>
							<tr>
								<th>Дата</th>
								<th>Статья</th>
								<th>Яндекс</th>
								<th>Google</th>
								<th>Вебмастер</th>
								<th>Проверка</th>
								<th>Примечание</th>
								<th>Поиск</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rows as $row ) : ?>
								<tr data-slug="<?php echo esc_attr( $row['slug'] ); ?>">
									<td><?php echo esc_html( $row['date'] ); ?></td>
									<td>
										<a href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $row['title'] ); ?></a>
										<?php if ( 'future' === $row['wp_status'] ) : ?>
											<br><small>future</small>
										<?php endif; ?>
									</td>
									<?php foreach ( array( 'yandex', 'google', 'webmaster' ) as $field ) : ?>
										<td>
											<select class="proauc-idx-field" data-field="<?php echo esc_attr( $field ); ?>" style="max-width:130px;">
												<?php foreach ( $opts as $val => $label ) : ?>
													<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $row[ $field ] ?? '', $val ); ?>><?php echo esc_html( $label ); ?></option>
												<?php endforeach; ?>
											</select>
										</td>
									<?php endforeach; ?>
									<td class="proauc-idx-checked"><?php echo esc_html( $row['checked'] ?? '' ); ?></td>
									<td>
										<input type="text" class="proauc-idx-note regular-text" style="width:100%;min-width:120px;" value="<?php echo esc_attr( $row['note'] ?? '' ); ?>" placeholder="заметка">
									</td>
									<td style="white-space:nowrap;">
										<a href="<?php echo esc_url( 'https://yandex.ru/search/?text=' . rawurlencode( 'site:' . $row['url'] ) ); ?>" target="_blank" rel="noopener">Я</a>
										·
										<a href="<?php echo esc_url( 'https://www.google.com/search?q=' . rawurlencode( 'site:' . $row['url'] ) ); ?>" target="_blank" rel="noopener">G</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<p id="proauc-indexing-save-status" style="margin:12px 0 0;color:#50575e;"></p>
			</div>
		</div>
	</div>
	<script>
	(function($){
		var idxNonce = <?php echo wp_json_encode( $nonce ); ?>;

		function updateSummary(s) {
			if (!s) return;
			$('#proauc-idx-count').text(s.indexed);
			$('#proauc-idx-pending').text(s.pending);
			$('#proauc-idx-sched').text(s.scheduled);
		}

		function saveRow($tr) {
			var slug = $tr.data('slug');
			$('#proauc-indexing-save-status').text('Сохранение ' + slug + '…');
			$.post(ajaxurl, {
				action: 'proauc_save_indexing_checklist',
				nonce: idxNonce,
				slug: slug,
				yandex: $tr.find('[data-field=yandex]').val(),
				google: $tr.find('[data-field=google]').val(),
				webmaster: $tr.find('[data-field=webmaster]').val(),
				note: $tr.find('.proauc-idx-note').val()
			}).done(function(res){
				if (res.data && res.data.checked) {
					$tr.find('.proauc-idx-checked').text(res.data.checked);
				}
				updateSummary(res.data && res.data.summary);
				$('#proauc-indexing-save-status').text('Сохранено: ' + slug);
			}).fail(function(){
				$('#proauc-indexing-save-status').text('Ошибка сохранения: ' + slug);
			});
		}

		var saveTimer;
		function scheduleSave($tr) {
			clearTimeout(saveTimer);
			saveTimer = setTimeout(function(){ saveRow($tr); }, 400);
		}

		$(function(){
			var $wrap = $('#proauc-indexing-wrap');
			var $acf = $('.acf-settings-wrap, #acf-form');
			if ($acf.length) {
				$wrap.insertAfter($acf.first()).show();
			} else {
				$wrap.show();
			}

			$('#proauc-indexing-table').on('change', '.proauc-idx-field', function(){
				scheduleSave($(this).closest('tr'));
			});
			$('#proauc-indexing-table').on('blur', '.proauc-idx-note', function(){
				scheduleSave($(this).closest('tr'));
			});

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

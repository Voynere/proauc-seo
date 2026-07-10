<?php
/**
 * Mark / model / year filter for /avtodoma/.
 *
 * Expects $facets and $filters in scope.
 *
 * @package proautospec
 */

defined( 'ABSPATH' ) || exit;

$template_args = isset( $args ) && is_array( $args ) ? $args : array();
$facets        = ! empty( $template_args['facets'] ) ? $template_args['facets'] : proautospec_avtodoma_get_facets();
$filters       = ! empty( $template_args['filters'] ) ? $template_args['filters'] : proautospec_avtodoma_sanitize_filters( $_GET );

$selected_mark = $filters['mark'] ?? '';
$selected_model = $filters['model'] ?? '';
$selected_year = ! empty( $filters['year'] ) ? (int) $filters['year'] : 0;
?>
<div class="row form-row">
	<div class="col-lg-4">
		<label for="avtodoma-mark">Марка</label>
		<select name="mark" id="avtodoma-mark">
			<option value="">Все марки</option>
			<?php foreach ( $facets['marks'] as $mark ) : ?>
				<option value="<?php echo esc_attr( $mark['id'] ); ?>" <?php selected( $selected_mark, $mark['id'] ); ?>>
					<?php echo esc_html( $mark['text'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="col-lg-4">
		<label for="avtodoma-model">Модель</label>
		<select name="model" id="avtodoma-model">
			<option value="">Все модели</option>
			<?php
			if ( $selected_mark && ! empty( $facets['models'][ $selected_mark ] ) ) {
				foreach ( $facets['models'][ $selected_mark ] as $model ) {
					?>
					<option value="<?php echo esc_attr( $model['id'] ); ?>" <?php selected( $selected_model, $model['id'] ); ?>>
						<?php echo esc_html( $model['text'] ); ?>
					</option>
					<?php
				}
			}
			?>
		</select>
	</div>
	<div class="col-lg-4">
		<label for="avtodoma-year">Год выпуска</label>
		<select name="car-year" id="avtodoma-year">
			<option value="">Любой год</option>
			<?php foreach ( $facets['years'] as $year ) : ?>
				<option value="<?php echo esc_attr( (string) $year ); ?>" <?php selected( $selected_year, $year ); ?>>
					<?php echo esc_html( (string) $year ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
<div class="row form-row">
	<div class="col-lg-4 d-flex">
		<button type="submit" class="btn btn-submit">Подобрать</button>
	</div>
</div>
<script type="application/json" id="avtodoma-filter-models"><?php echo wp_json_encode( $facets['models'], JSON_UNESCAPED_UNICODE ); ?></script>

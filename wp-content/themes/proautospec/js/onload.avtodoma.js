(function ($) {
	'use strict';

	function readModelsByMark() {
		var modelsNode = document.getElementById('avtodoma-filter-models');
		if (!modelsNode) {
			return {};
		}

		try {
			return JSON.parse(modelsNode.textContent || '{}');
		} catch (error) {
			return {};
		}
	}

	function fillModels($markSelect, $modelSelect, modelsByMark, selectedModel) {
		var markSlug = $markSelect.val();

		$modelSelect.empty();
		$modelSelect.append(
			$('<option>', {
				value: '',
				text: 'Все модели',
			})
		);

		(modelsByMark[markSlug] || []).forEach(function (item) {
			$modelSelect.append(
				$('<option>', {
					value: item.id,
					text: item.text,
					selected: selectedModel === item.id,
				})
			);
		});

		if ($modelSelect.data('select2')) {
			$modelSelect.trigger('change.select2');
		} else {
			$modelSelect.trigger('change');
		}
	}

	$(function () {
		var $markSelect = $('#avtodoma-mark');
		var $modelSelect = $('#avtodoma-model');

		if (!$markSelect.length || !$modelSelect.length) {
			return;
		}

		var modelsByMark = readModelsByMark();

		$markSelect.on('change', function () {
			fillModels($markSelect, $modelSelect, modelsByMark, '');
		});

		fillModels($markSelect, $modelSelect, modelsByMark, $modelSelect.val());
	});
})(jQuery);

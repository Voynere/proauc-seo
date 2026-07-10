(function () {
	'use strict';

	var markSelect = document.getElementById('avtodoma-mark');
	var modelSelect = document.getElementById('avtodoma-model');
	var modelsNode = document.getElementById('avtodoma-filter-models');

	if (!markSelect || !modelSelect || !modelsNode) {
		return;
	}

	var modelsByMark = {};
	try {
		modelsByMark = JSON.parse(modelsNode.textContent || '{}');
	} catch (error) {
		modelsByMark = {};
	}

	function fillModels(markSlug, selectedModel) {
		while (modelSelect.options.length > 1) {
			modelSelect.remove(1);
		}

		var models = modelsByMark[markSlug] || [];
		models.forEach(function (item) {
			var option = document.createElement('option');
			option.value = item.id;
			option.textContent = item.text;
			if (selectedModel && selectedModel === item.id) {
				option.selected = true;
			}
			modelSelect.appendChild(option);
		});
	}

	markSelect.addEventListener('change', function () {
		fillModels(markSelect.value, '');
	});

	fillModels(markSelect.value, modelSelect.value);
})();

	ymaps.ready(function () {
		const map = new ymaps.Map('YMap', {
			center: [43.226016, 131.995502],
			zoom: 15,
			theme: "dark"

		});
		var placemark = new ymaps.Placemark(
				[43.226016, 131.995502],
				{},
				{
					preset: 'islands#darkOrangeIcon'
				}
		);
		map.geoObjects.add(placemark);

	});

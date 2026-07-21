
	function renderListing(data){
		console.log(data);
		
		jQuery('#cars-listing .car-loaded').remove();


		for (var i = 0; i < data.length; i++) {
			car = data[i];
			
			let itemHtml = jQuery('#car-item.d-none').clone().removeClass('d-none').removeAttr('id').addClass('car-loaded');
			let name = nameInUrl = car.marka_name + ' ' + car.model_name;
			if (car.grade) nameInUrl = nameInUrl + '-' + car.grade.replace(/&#(\d+);/g, function(match, number){ return String.fromCharCode(number); });
			nameInUrl = nameInUrl
				.replace(/[^a-zA-Z0-9\s-]/g,'')
				.trim()
				.replace(/\s+/g, '-')
				.replace(/-+/g, '-')
				.toLowerCase();
			
			let itemDetailedUrl = '/avto-iz-korei/'+ car.lot + '-' + nameInUrl + '/';
			
			
			itemHtml.find('.car-model-name').html(name);
			let itemTitleFull = name;
			itemHtml.find('.car-link').attr({'href': itemDetailedUrl});
			itemHtml.find('.car-img').attr('alt', name);
			if (car.grade){
				itemHtml.find('.car-model-specification').html(car.grade);
				itemTitleFull += ' ' + car.grade.replace(/&#(\d+);/g, function(match, number){  return String.fromCharCode(number); });
			}
			itemHtml.find('h3').attr('title', itemTitleFull);
			
			if (car.year)
				itemHtml.find('.car-item__params').append('<dt>Год</dt><dd>' + car.year + '</dd>');

			
			if (car.kpp)
				itemHtml.find('.car-item__params').append('<dt>Привод</dt><dd>' + car.kpp + '</dd>');

			if (car.eng_v)
				itemHtml.find('.car-item__params').append('<dt>Объём</dt><dd>' + car.eng_v + ' см<sup>3</sup></su></dd>');

			
			if (car.mileage)
				itemHtml.find('.car-item__params').append('<dt>Пробег</dt><dd>' + number_format(car.mileage, 0) + ' км</dd>');

			
			/*if (car.mileage)
				itemHtml.find('.car-item__params').append('<dt>Пробег</dt><dd>' + number_format(car.mileage, 0) + ' км</dd>');
			*/


			//itemHtml.find('.car-price-value').html('~ '+number_format(car.finish, 0) + ' ₩')
			if (car.finish > 0){
				itemHtml.find('.car-price-value').html('~ '+number_format(car.finish * course, 0) + ' ₽')
			}else{
				itemHtml.find('.car-price-value').html('по запросу')
			}
			


			images = car.images.split('#');
			images = [...new Set(images)];
			if(images.length > 1){
				
				let $imgHtml = jQuery(itemHtml).find('.car-img');
				jQuery(itemHtml).find('.car-img').first().remove();
				for (let j=0; j < ( 5 < images.length ? 5 : images.length); j++){
					let $curItem = $imgHtml.clone();
					$curItem.attr({'src': images[j].replace('http://', 'https://').replace('&h=50', '').replace('8.ajes.com', '7.ajes.com') + "&w=320"});
					$curItem.attr({'onerror': "this.src='/images/no-photo.png'"});
					
					
					jQuery(itemHtml).find('.car-item__pic .car-link').append($curItem);
				}
				//console.log($div.prop('outerHTML'));
				
				
				//jQuery(itemHtml.find('.cart-img-2')[0]).css({'background-image': 'url("'+images[1].replace('&h=50', '')+'")'});
			}else{
				jQuery(itemHtml.find('.car-img')).attr({'src': images[0].replace('&h=50', '' + "&w=320")});
				
				//jQuery(cart.find('.card-line__img')[1]).remove();
			}
			
			
			let $newEl = jQuery('#cars-listing .row').first().append(itemHtml);



		}
		jQuery('.car-loaded .car-item__pic  .car-link').HoverMouseCarousel();

	}

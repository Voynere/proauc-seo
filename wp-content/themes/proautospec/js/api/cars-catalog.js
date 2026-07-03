


var heading_h1;

Promise.all([domLoaded]).then((results) => {


	let apiParams = new URLSearchParams();
	
	if ( (window.location.href.indexOf("catalog") == -1) && (window.location.href.indexOf("statistika") == -1) && (window.location.href.indexOf("gruzoviki") == -1)) {
		apiParams.append( 'distinct', 1);
	}
	if(window.location.href.indexOf("statistika") != -1) {
		statistics = 1;
	}	
	if(urlParams.has('mark-id')){
		apiParams.append( 'marka_id', urlParams.get('mark-id'));
		
	}
	if(urlParams.has('model-id')){
		apiParams.append( 'model_id', urlParams.get('model-id'));
		
	}
	
	
	if (routerParams.hasOwnProperty('mark')){

		apiParams.delete( 'marka_id');
		apiParams.append( 'marka_name', routerParams.mark.replace('-', '+') );
	}
	
	if (routerParams.hasOwnProperty('model')){

		apiParams.delete( 'model_id');
		
		let model  = routerParams.model;
		//model  = routerParams.model.replaceAll('-', ' ');

		apiParams.append( 'model_name', model );
	}	

	if(urlParams.has('year-start')){
		apiParams.append( 'year_start', urlParams.get('year-start'));
	}
	if(urlParams.has('year-end')){
		apiParams.append( 'year_end', urlParams.get('year-end'));
	}
	if(urlParams.has('mileage-start')){
		apiParams.append( 'mileage_start', urlParams.get('mileage-start'));
	}
	if(urlParams.has('mileage-end')){
		apiParams.append( 'mileage_end', urlParams.get('mileage-end'));
	}
	if(urlParams.has('pn')){
		apiParams.append( 'pn', urlParams.get('pn'));
	}

	if (typeof (statistics) !== 'undefined'  && statistics == 1){
		apiParams.append( 'stat', 1);
	}

	console.log ('initial API request... ');
	loadData(apiParams.toString(), 1);
	
	
	function loadData(params, pageNumber){

		
		console.log('api request: ' + API_URL + '?' + params);
		jQuery.ajax({
			url: API_URL + '?'+params,
			method: 'get',
			dataType: 'json',
			success: function(data){


				console.log('rendering');
				
				renderListing(data.autos);
				
				//jQuery('.cars-listing__total').html( 'Всего найдено автомобилей марки ' + data.autos[0].marka_name + ': <var>' + data.count + '</var>');
				jQuery('.cars-listing__total').html( 'Всего по запросу найдено <var>' + data.count +'</var> автомобилей: ');
				jQuery('h1').html(heading_h1);

				
				
				if (jQuery('#car-listing-pagination').length > 0 ){
					jQuery('#car-listing-pagination').empty();
					renderPagination(data.count, 20);
					
					
					jQuery('html, body').animate({
						scrollTop: jQuery(".b-cars-catalog-filter").first().offset().top - 500
					}, 10);				

				}
				
				//if (pageNumber != 1) { pagination.goToPage(pageNumber);}





			},
			error: function(){
				
			}
		});



	};

	jQuery('.b-cars-catalog-filter:not(.b-cars-catalog-filter--goto)').off('submit');
	jQuery('.b-cars-catalog-filter:not(.b-cars-catalog-filter--goto) .btn').on('click', function(e){
		
		
		jQuery('#car-listing-pagination').empty();

		var params = parseFilterParams();
		var newBaseUrl = baseUrl;
		params.apiParams.delete('pn');
		params.filterParams.delete('pn');
		heading_h1 = 'Каталог автомобилей ' + routerParams.countryLabel;

		loadData( params.apiParams.toString(),1  );

		if (params.filterParams.has('mark')){
			
			let markSlug = params.filterParams.get('mark').toLowerCase().replace(/\s+/g, '-');
			heading_h1 = params.filterParams.get('mark');
			
			params.filterParams.delete('mark');
			newBaseUrl = baseUrl + markSlug + '/' ;
			
			
			
			if (params.filterParams.has('model')){

				let modelSlug = params.filterParams.get('model').toLowerCase().replace(/\s+/g, '-');
				heading_h1 =  heading_h1 + ' ' + params.filterParams.get('model');
				params.filterParams.delete('model');
				newBaseUrl = newBaseUrl + modelSlug + '/' ;
				
			}			
			heading_h1 = heading_h1 + ' ' + routerParams.countryLabel;
		}
		
		
		
		
		//should add new params to url
		if (params.filterParams.toString() == '')
			window.history.pushState({href: newBaseUrl }, '', newBaseUrl );
		else
			window.history.pushState({href: newBaseUrl + '?' + params.filterParams.toString() }, '', newBaseUrl + '?' + params.filterParams.toString());
		e.preventDefault();
	});
	
	

	function parseFilterParams(){

		let filterParams = new URLSearchParams();
		let apiParams = new URLSearchParams();

		if(jQuery('#mark-id').val() && typeof(jQuery('#mark-id').val()) !== "undefined"){
			//filterParams.append ('mark-id', jQuery('#mark-id').val());
			filterParams.append ('mark', jQuery('#mark-id').select2('data')[0]['text']);
			apiParams.append ('marka_name', jQuery('#mark-id').select2('data')[0]['text']);
			//apiParams.append ('marka_id', jQuery('#mark-id').val());
		}
		if(jQuery('#model-id').val() && typeof(jQuery('#model-id').val()) !== "undefined"){
			//filterParams.append ('model-id', jQuery('#model-id').val());
			filterParams.append ('model', jQuery('#model-id').select2('data')[0]['text']);
			apiParams.append ('model_name', jQuery('#model-id').select2('data')[0]['text']);
			//apiParams.append ('model_id', jQuery('#model-id').val());
		}
		if(jQuery('#mileage-start').val() && typeof(jQuery('#mileage-start').val()) !== "undefined"){
			filterParams.append ('mileage-start', jQuery('#mileage-start').val());
			apiParams.append ('mileage_start', jQuery('#mileage-start').val());

		}
		if(jQuery('#mileage-end').val() && typeof(jQuery('#mileage-end').val()) !== "undefined"){
			filterParams.append ('mileage-end', jQuery('#mileage-end').val());
			apiParams.append ('mileage_end', jQuery('#mileage-end').val());
		}
		if(jQuery('#year-start').val() && typeof(jQuery('#year-start').val()) !== "undefined"){
			filterParams.append ('year-start', jQuery('#year-start').val());
			apiParams.append ('year_start', jQuery('#year-start').val());
		}
		if(jQuery('#year-end').val() && typeof(jQuery('#year-end').val()) !== "undefined"){
			filterParams.append ('year-end', jQuery('#year-end').val());
			apiParams.append ('year_end', jQuery('#year-end').val());
		}
		let curUrlParams = new URLSearchParams(window.location.search);
		if(curUrlParams.has('pn')){
			console.log('pn found while parsing in url:' + curUrlParams.get('pn'));

			filterParams.append( 'pn', curUrlParams.get('pn'));
			apiParams.append( 'pn', curUrlParams.get('pn'));
		}
		
		
		
		return { apiParams: apiParams, filterParams: filterParams };
	}







	let pagination; let params;
	function renderPagination(count, page_size){

		var itemsCount = count;
		var itemsOnPage = page_size;
		params = parseFilterParams();


		var currentPage = 1;
		if(params.filterParams.has('pn')){
			currentPage = params.filterParams.get('pn');
			params.filterParams.delete("pn");
			params.filterParams.set("pn", currentPage);
			console.log('and current page is:' + currentPage);
		}else{
			params.filterParams.set("pn", 1);
			console.log('and current page is unset, so:' + currentPage);
		}
		var tmpPath = params.filterParams;
		var tmpBaseUrl = baseUrl;
	 	tmpPath.delete("pn");

		if (tmpPath.has('mark')){
			
			let markSlug = tmpPath.get('mark').toLowerCase().replace(/\s+/g, '-');
			tmpPath.delete('mark');
			tmpBaseUrl = tmpBaseUrl + markSlug + '/' ;
			
			if (tmpPath.has('model')){

				let modelSlug = tmpPath.get('model').toLowerCase().replace(/\s+/g, '-');
				tmpPath.delete('model');
				tmpBaseUrl = tmpBaseUrl + modelSlug + '/' ;
			}			
		}
		
		
		tmpPath = tmpPath.toString();
		//

		pagination = new Pagination({
			container: jQuery('#car-listing-pagination'),
			pageClickUrl: tmpBaseUrl + '?' + ( tmpPath ? tmpPath + "&" : "") + "pn={{page}}",
			callPageClickCallbackOnInit: true,
			pageClickCallback: function (pageNumber, event) {
				try{
					event.preventDefault();
					console.log('new page number is:' + pageNumber);
					params.filterParams.set('pn', pageNumber);
					params.apiParams.set('pn', pageNumber);
					console.log('adding to history:' + tmpBaseUrl + '?' + params.filterParams.toString());

					window.history.pushState({href: tmpBaseUrl + params.filterParams.toString()}, '', tmpBaseUrl + '?' + params.filterParams.toString());


					loadData(params.apiParams.toString(), pageNumber);
				}catch (e) {

				}

			}
		});
		pagination.make(itemsCount, itemsOnPage, currentPage);
	}



});
window.addEventListener('popstate', function(event) {
	location.reload();
}, false);




let API_URL = '';
let baseUrl = '/spectehnika/';


var models;

const domLoaded = new Promise((resolve) => {
	document.addEventListener('DOMContentLoaded', resolve);
});


var routerParams =  {};
let urlParams = new URLSearchParams(window.location.search);
let apiParams = new URLSearchParams();
let keysForDel = [];
urlParams.forEach((value, key) => {
	if ( (value == '') || (value == 'undefined') ) {
		keysForDel.push(key);
	}
});
keysForDel.forEach(key => {
	urlParams.delete(key);
});
console.log('params cleared:' + urlParams.toString());


function getGroupBySlug(slug) {
	
	return models.filter(
		function(data){ 
			return data.slug == slug }
	);
}
function getGroupById(id) {
	
	return models.filter(
		function(data){ 
			return data.id == id }
	);
}

function getTypeIdBySlug(slug, groupTypes){
	return groupTypes.filter(
		function(data){ return data.slug == slug }
	);
}

function getTypeById(id, groupTypes){
	return groupTypes.filter(
		function(data){ return data.id == id }
	);
}

	
const url = new URL(window.location.href);

let path = url.pathname.split('/').join('');
	


Promise.all([domLoaded]).then((results) => {
	
	var router = new PathParser(routerParams);
	
	

	jQuery.fn.select2.defaults.set("theme", "bootstrap5-dark");
	jQuery.extend(jQuery.fn.select2.defaults.defaults.language, {
		noResults: function() { return 'Не найдено'; }
	});
	var course = 1;

	models = jQuery.parseJSON( models_hdm );

	//models.forEach(function(v){ delete v.types });

	
	router.add (baseUrl + ':hdm_group/', function () {});
	router.add (baseUrl + ':hdm_group/:hdm_type/', function () {});	
	router.run(url.pathname);
	
	
	if ( (routerParams.hdm_group != null) && (routerParams.hdm_group != 'catalog') ){
		let hdm_group;
		try {
			hdm_group = getGroupBySlug(routerParams.hdm_group);
			urlParams.set('group-id', hdm_group[0].id);
		}	catch (error) { 
			urlParams.delete('group-id');
			window.location.replace(baseUrl);
		}
		if (routerParams.hdm_type != null){
			try {
				let type_id = getTypeIdBySlug(routerParams.hdm_type, hdm_group[0].types);
				//console.log('model id from url:');
				//console.log(modelId[0].id);
				urlParams.delete('type-id');
				urlParams.set('type-id', type_id[0].id);
			} catch (error) { 
				//console.log(error);
				urlParams.delete('type-id');
				window.location.replace(baseUrl + routerParams.hdm_group + '/');
		
			}
		}		
	}
	console.log(urlParams);
	

	
	
	
	jQuery("#group-id").empty().select2({  placeholder: '', data:[{'id': '', 'text': 'Выберите группу', 'slug': ''}] });
	
	jQuery('#group-id').select2({data: Object.values(models)});
	
	console.log(Object.values(models));
	//console.log(Object.values(getGroupById(1)[0].types));
	
	jQuery("#type-id").empty().select2({   placeholder:'Выберите тип', data:[]});
	

	jQuery('#group-id').on('select2:select', function(e){
		console.log('group id select2:select triggered')
		console.log(e);
		console.log(e.params.data);
		
		
		
		if (jQuery(this).val() !=""){
			let val = jQuery(this).val();
		
			jQuery("#type-id").empty().select2({ data:[{'id': '', 'text': 'Выберите тип'}] });
		
			jQuery("#type-id").select2({ data: Object.values(getGroupById(val)[0].types) });
		}else{
			jQuery("#type-id").empty().select2({   placeholder:'Выберите группу', data:[{
					'id': '', 'text': 'Выберите группу',selected:'selected'
				}] });
		}
		

	});
	jQuery('#type-id').on('select2:select', function(e){
		console.log(e);
        console.log(e.params.data);
    });
									
										
										
										
	//loadData(apiParams.toString(), 1);
	
	function loadData(params, pageNumber){


		console.log('api request: ' + '/api/get-hdm-categories.php?'+params);
		jQuery.ajax({
			url: API_URL + '/api/get-hdm-categories.php?'+params,
			method: 'get',
			dataType: 'json',
			success: function(data){


				console.log('rendering');
				console.log(data.cats);
				//renderListing(data.autos);
				jQuery('#category1').select2({data: Object.values(data.cats)});
				
				if(urlParams.get('category1') != null){
					jQuery('#category1').val(urlParams.get('category1')).trigger("change.select2");
				}		


			},
			error: function(){
				jQuery('.cart_new').remove();
				jQuery('.k_auto_content').append('<div class="cart_new cart_not-found"><p class="cart_not-found_title">ÐÐµ Ð½Ð°Ð¹Ð´ÐµÐ½Ð¾</p></div>');
			}
		});



	};
		
	


	let years = [];
	for ( i= new Date().getFullYear(); i>=2000; i--){
		years.push({'id':i, 'text':i});
	}
	
	yearsReverse = [...years].reverse();
	years.unshift({'id': ' ', 'text': 'любой'}); years.unshift({'id': '', 'text': ''});
	yearsReverse.unshift({'id': ' ', 'text': 'любой'}); yearsReverse.unshift({'id': '', 'text': ''});
	
	jQuery('#year-start').select2({
	  tags: true,	  
	  data: yearsReverse,
	  placeholder: '2000',
	  escapeMarkup: function(markup) {return markup; }
	}).on("select2:select", function (e) { 
		if (e.params.data['text'] == 'любой'){
			jQuery(this).val(null).trigger("change.select2");
		}
	});
	
	jQuery('#year-end').select2({
	  tags: true,	  
	  data: years,
	  placeholder:  new Date().getFullYear().toString(),
	  escapeMarkup: function(markup) {return markup; }
	}).on("select2:select", function (e) { 
		if (e.params.data['text'] == 'любой'){
			jQuery(this).val(null).trigger("change.select2");
		}
	});	



	let prices = [], pricesFixed= ['250 000', '500 000', '750 000', '1 000 000', '1 500 000', '2 000 000', '3 000 000', '4 000 000', '5 000 000'];
	for (i in pricesFixed){
		prices.push({'id':pricesFixed[i].replace(/ /g, ''), 'text':pricesFixed[i]});
	}
	pricesReverse = [...prices].reverse();
	prices.unshift({'id': ' ', 'text': 'любая'}); prices.unshift({'id': '', 'text': ''});
	pricesReverse.unshift({'id': ' ', 'text': 'любая'}); pricesReverse.unshift({'id': '', 'text': ''});
	
	jQuery('#price-start').select2({
	  tags: true,	  
	  data: prices,
	  placeholder: '100 000',
	  escapeMarkup: function(markup) {return markup; }
	}).on("select2:select", function (e) { 
		if (e.params.data['text'] == 'любая'){
			jQuery(this).val(null).trigger("change.select2");
		}
	});
	
	jQuery('#price-end').select2({
	  tags: true,	  
	  data: pricesReverse,
	  placeholder: '10 000 000',
	  escapeMarkup: function(markup) {return markup; }
	}).on("select2:select", function (e) { 
		if (e.params.data['text'] == 'любая'){
			jQuery(this).val(null).trigger("change.select2");
		}
	});	

	let mileage = [], mileageFixed= ['100', '1 000', '10 000', '30 000', '50 000', '100 000', '500 000'];
	for (i in mileageFixed){
		mileage.push({'id':mileageFixed[i].replace(/ /g, ''), 'text':mileageFixed[i]});
	}
	mileageReverse = [...mileage].reverse();
	mileage.unshift({'id': ' ', 'text': 'любой'}); mileage.unshift({'id': '', 'text': ''});
	mileageReverse.unshift({'id': ' ', 'text': 'любой'}); mileageReverse.unshift({'id': '', 'text': ''});
	
	jQuery('#mileage-start').select2({
	  tags: true,	  
	  data: mileage,
	  placeholder: '100',
	  escapeMarkup: function(markup) {return markup; }
	}).on("select2:select", function (e) { 
		if (e.params.data['text'] == 'любой'){
			jQuery(this).val(null).trigger("change.select2");
		}
	});
	
	jQuery('#mileage-end').select2({
	  tags: true,	  
	  data: mileageReverse,
	  placeholder: '500 000',
	  escapeMarkup: function(markup) {return markup; }
	}).on("select2:select", function (e) { 
		if (e.params.data['text'] == 'любой'){
			jQuery(this).val(null).trigger("change.select2");
		}
	});

	if(urlParams.get('mark-id') != null){
		jQuery('#mark-id').val(urlParams.get('mark-id')).trigger("change.select2");

		jQuery("#model-id").empty().select2({ data:[{'id': '', 'text': 'Выберите модель'}] });
		jQuery("#model-id").select2({ data: Object.values(getMarkaById(urlParams.get('mark-id'))[0].models) });

		if(urlParams.get('model-id') != null){

			jQuery('#model-id').val(urlParams.get('model-id')).trigger("change.select2");
		}
	}

	if(urlParams.get('year-start') != null){
		jQuery('#year-start').val(urlParams.get('year-start')).trigger("change.select2");
	}
	if(urlParams.get('year-end') != null){
		jQuery('#year-end').val(urlParams.get('year-end')).trigger("change.select2");
	}

	if(urlParams.get('price-start') != null){
		jQuery('#price-start').val(urlParams.get('price-start')).trigger("change.select2");
	}

	if(urlParams.get('price-end') != null){
		jQuery('#price-end').val(urlParams.get('price-end')).trigger("change.select2");
	}

	if(urlParams.get('mileage-start') != null){
		jQuery('#mileage-start').val(urlParams.get('mileage-start')).trigger("change.select2");
	}
	if(urlParams.get('mileage-end') != null){
		jQuery('#mileage-end').val(urlParams.get('mileage-end')).trigger("change.select2");
	}

	if(urlParams.get('group-id') != null){
		jQuery('#group-id').val(urlParams.get('group-id')).trigger('change').trigger("change.select2");
		
		
		

		jQuery("#type-id").empty().select2({ data:[{'id': '', 'text': 'Выберите тип'}] });
		
		jQuery("#type-id").select2({ data: Object.values(getGroupById(urlParams.get('group-id'))[0].types) });
		console.log(getGroupById(urlParams.get('group-id'))[0].types);

		
		console.log('initial fill ended');
		if(urlParams.get('type-id') != null){

			jQuery('#type-id').val(urlParams.get('type-id')).trigger('change').trigger("change.select2");
		}
	}
	
	
	
	jQuery('.b-cars-catalog-filter--goto form').on('submit', function(e){
	
		//jQuery('.b-cars-catalog-filter--goto form').find('select').filter(function() { return !this.value; }).attr('disabled', 'disabled');
		
		var submitUrl = baseUrl;

		if (jQuery('#group-id').val() && typeof(jQuery('#group-id').val()) !== undefined) {
			var group = getGroupById(jQuery('#group-id').select2('data')[0].id)[0];
			var groupSlug = group.slug;
			submitUrl = submitUrl + groupSlug + '/';
			console.log(submitUrl);
			jQuery('#group-id').attr('disabled', 'disabled');
			if(jQuery('#type-id').val() && typeof(jQuery('#type-id').val()) !== undefined){
				//filterParams.append ('mark-id', jQuery('#mark-id').val());
				var typeSlug = jQuery('#type-id').select2('data')[0]['slug'];
				console.log(typeSlug);
				if (typeSlug === undefined){
					//!!!!!!!!!!!!!!!!!
					//select2 bug: not getting all of the data
					//console.log(getTypeById( jQuery('#type-id').val(), group.types));
					console.log('getting from array()');
					typeSlug = getTypeById( jQuery('#type-id').val(), group.types)[0].slug;
					
				}
				
				submitUrl = submitUrl + typeSlug + '/';	
			}
			jQuery('#type-id').attr('disabled', 'disabled');
		}else{
			submitUrl =  submitUrl + 'catalog' + '/';	
				
		}
		//return false;
		jQuery('.b-cars-catalog-filter--goto form').attr('action', submitUrl);

		
	});

	jQuery('.car-loaded .car-link').HoverMouseCarousel();


});


function number_format( number, decimals, dec_point, thousands_sep ) {  // Format a number with grouped thousands
	var i, j, kw, kd, km;

	if( isNaN(decimals = Math.abs(decimals)) ){
		decimals = 2;
	}
	if( dec_point == undefined ){
		dec_point = ",";
	}
	if( thousands_sep == undefined ){
		thousands_sep = " ";
	}

	i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

	if( (j = i.length) > 3 ){
		j = j % 3;
	} else{
		j = 0;
	}

	km = (j ? i.substr(0, j) + thousands_sep : "");
	kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
	kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");

	return km + kw + kd;
}
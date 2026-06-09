jQuery(document).ready(function (e) {
	(function($) {
		jQuery('.single-auto__slider-gallery').imagesLoaded( function() {


		
			let gallerySwiper = new Swiper(".single-auto__slider-gallery", {
				slidesPerView: 1,
				spaceBetween: 15,
				autoHeight: true,
				loop: false,
				navigation: {
					nextEl: ".swiper-button-next",
					prevEl: ".swiper-button-prev",
				},

			});

			$('.single-auto__slider-nav a').on('click',function(e){
				e.preventDefault();
				var slide = $(this).data('slide');
				gallerySwiper.slideTo(slide - 1);
				console.log(gallerySwiper.realIndex);
				//$(".swiper-slide-active").click(); 
			});
			let galleryLg = $('.single-auto__slider-gallery .swiper-wrapper').lightGallery({
				thumbnail: false,
				counter: false,
				selector: '.swiper-slide',
				download: false,
				share: false,
				fullScreen: false,
				autoplay: false,
				autoplayControls: false
			});
		});
		
	}(jQuery));
});


function image_nofoto(el) {
	if( (el.naturalWidth==319||el.naturalWidth==1) && /\.ajes\.com/.test(el.src)){
		el.parentElement.style.display="none";
		el.parentElement.classList.remove("swiper-slide");   
	}
}
function image_error(el){
	el.parentElement.style.display="none";
	el.parentElement.classList.remove("swiper-slide");  
}
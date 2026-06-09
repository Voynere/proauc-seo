jQuery(document).ready(function (e) {
	(function($) {
	
        $(".navbar-toggler").on("click", function () {
            $(".masthead").toggleClass("mega-opened");
			$("#mega-menu").toggleClass("show");
            $(this).toggleClass("active");
        });
		$('.accordion-button').on("click", function () { 
			$(this).toggleClass('active');
			$(this).next().collapse('toggle');
		});
		
        $('select').select2({
            theme: "bootstrap5-dark",
            minimumResultsForSearch: -1
        });
		
		
		
		
		
        //$('input[type="tel"]').mask('0 (000) 000-0000', {placeholder: "7 (___) ___-____"});
        $('input[type="tel"]').on('focus',function(){
            //if ($(this).val())
            $(this).attr('placeholder', "7 (___) ___-____");
            $(this).mask('0 (000) 000-0000', {placeholder: "7 (___) ___-____"});
            //else
            //    $(this).attr('placeholder','Ваш телефон');
        }).on('focusout', function(){
            console.log($(this).val());
            if (!$(this).val() )
                $(this).attr('placeholder','Ваш телефон');
        });
		

		$('#order-dialog').on('show.bs.modal', function (event) {
			
			var autoModel = $(event.relatedTarget).data('model');
			var separateTitle = $(event.relatedTarget).data('title');
			$(".wpcf7-not-valid-tip", this).remove();
			$(".wpcf7-response-output", this).empty();
			$(".wpcf7-not-valid").removeClass("wpcf7-not-valid");
			if (autoModel) {
				
				$(this).find(".modal-title").text("Заказать " + autoModel);
				$(this).find("form input[name='model']").val(autoModel);
			}else{
				if (separateTitle){
					$(this).find(".modal-title").text(separateTitle);
				}else{
					$(this).find(".modal-title").text("Оставить заявку");
				}
				$(this).find("form input[name='model']").val("")
			}
			var formTitle = $(this).find(".modal-title").text();
			$(this).find("form input[name='form-title']").val(formTitle);
			
		});	

		/*var recentlyBoughtSwiper = new Swiper(".recently-bought-slider", {
			slidesPerView: 1.2,
			spaceBetween: 15,
			navigation: {
				nextEl: ".recently-bought-slider__wrapper .swiper-button-next",
				prevEl: ".recently-bought-slider__wrapper .swiper-button-prev",
			},
			observer: true,
			breakpoints: {

				1024: {
					loop: true,
					slidesPerView: 4,
					spaceBetween: 24,
				},
			}
		});
		*/

		const recentlyBoughtSwiper = ()=>{
		  let largeSliders = document.querySelectorAll('.recently-bought-slider')
		  let prevArrow = document.querySelectorAll('.recently-bought-slider__wrapper .swiper-button-prev')
		  let nextArrow = document.querySelectorAll('.recently-bought-slider__wrapper .swiper-button-next')
		  largeSliders.forEach((slider, index)=>{
			let sliderLength = slider.children[0].children.length
			let result = (sliderLength > 1) ? true : false
			const swiper = new Swiper(slider, {
				direction: 'horizontal',
				slidesPerView: 1.2,
				spaceBetween: 15,
				loop: result,
				navigation: {
					nextEl: nextArrow[index],
					prevEl: prevArrow[index],
				},
				breakpoints: {

					1024: {
						loop: true,
						slidesPerView: 4,
						spaceBetween: 24,
					},
				}				
				
			}); 
		  })
		}
		recentlyBoughtSwiper();



		var teamSwiper = new Swiper(".team-slider", {
			slidesPerView: 1.2,
			spaceBetween: 15,

			navigation: {
				nextEl: ".team-slider__wrapper .swiper-button-next",
				prevEl: ".team-slider__wrapper .swiper-button-prev",
			},

			observer: true,
			breakpoints: {

				1024: {
					slidesPerView: 4,
					spaceBetween: 24,
				},
			}
		});		
		document.addEventListener( 'wpcf7mailsent', function( e ) {
			let h = $('#order-dialog .container').outerHeight();
			
			$('#order-dialog .container').html('<p style="font-size:2rem;line-height:1;text-transform:uppercase;font-weight:500;font-family:\'Stapel\', sans-serif">Спасибо, Ваша заявка принята!</p><p style="font-size:1.25rem;margin-top:2rem">В ближайшее время мы свяжемся с вами для согласования всех деталей.</p>').css({'height': h+'px', 'display': 'flex', 'justify-content':'center', 'flex-direction':'column'});
			$('#order-dialog .modal-title').addClass('invisible');
		});		 
	})(jQuery);
});	
(function($) {
	"use strict"

	// Mobile Nav toggle
	$(document).on('click', '.menu-toggle > a', function (e) {
		e.preventDefault();
		var nav = $('#responsive-nav');
		var isOpen = nav.toggleClass('active').hasClass('active');
		$(this).attr('aria-expanded', isOpen ? 'true' : 'false');
		$('body').toggleClass('mobile-nav-open', isOpen);
	})

	$(document).on('click', '#responsive-nav a', function () {
		$('#responsive-nav').removeClass('active');
		$('.menu-toggle > a').attr('aria-expanded', 'false');
		$('body').removeClass('mobile-nav-open');
	});

	$(document).on('click', function (e) {
		if ($('body').hasClass('mobile-nav-open') &&
			!$(e.target).closest('#responsive-nav, .menu-toggle').length) {
			$('#responsive-nav').removeClass('active');
			$('.menu-toggle > a').attr('aria-expanded', 'false');
			$('body').removeClass('mobile-nav-open');
		}
	});

	$(document).on('keydown', function (e) {
		if (e.key === 'Escape') {
			$('#responsive-nav').removeClass('active');
			$('.menu-toggle > a').attr('aria-expanded', 'false');
			$('body').removeClass('mobile-nav-open');
		}
	});

	// Fix cart dropdown from closing
	$('.cart-dropdown').on('click', function (e) {
		e.stopPropagation();
	});
	
	
	/////////////////////////////////////////
	

	// Products Slick
	$('.products-slick').each(function() {
		var $this = $(this),
				$nav = $this.attr('data-nav');

		$this.slick({
			slidesToShow: 4,
			slidesToScroll: 1,
			autoplay: true,
			infinite: true,
			speed: 300,
			dots: false,
			arrows: true,
			appendArrows: $nav ? $nav : false,
			responsive: [{
	        breakpoint: 1199,
	        settings: {
	          slidesToShow: 3,
	          slidesToScroll: 1,
	        }
	      },
	      {
	        breakpoint: 991,
	        settings: {
	          slidesToShow: 2,
	          slidesToScroll: 1,
	        }
	      },
	      {
	        breakpoint: 480,
	        settings: {
	          slidesToShow: 1,
	          slidesToScroll: 1,
	        }
	      },
	    ]
		});
	});

	// Products Widget Slick
	$('.products-widget-slick').each(function() {
		var $this = $(this),
				$nav = $this.attr('data-nav');

		$this.slick({
			infinite: true,
			autoplay: true,
			speed: 300,
			dots: false,
			arrows: true,
			appendArrows: $nav ? $nav : false,
		});
	});

	/////////////////////////////////////////

	// Product Main img Slick
	$('#product-main-img').slick({
    infinite: true,
    speed: 300,
    dots: false,
    arrows: true,
    fade: true,
    asNavFor: '#product-imgs',
  });

	// Product imgs Slick
  $('#product-imgs').slick({
    slidesToShow: 3,
    slidesToScroll: 1,
    arrows: true,
    centerMode: true,
    focusOnSelect: true,
		centerPadding: 0,
		vertical: true,
    asNavFor: '#product-main-img',
		responsive: [{
        breakpoint: 991,
        settings: {
					vertical: false,
					arrows: false,
					dots: true,
        }
      },
    ]
  });

	// Product img zoom
	var zoomMainProduct = document.getElementById('product-main-img');
	if (zoomMainProduct) {
		$('#product-main-img .product-preview').zoom();
	}


	
	/////////////////////////////////////////
	
	// Input number
	$('.input-number').each(function() {
		var $this = $(this),
		$input = $this.find('input[type="number"]'),
		up = $this.find('.qty-up'),
		down = $this.find('.qty-down');

		down.on('click', function () {
			var value = (parseInt($input.val(), 10) || 1) - 1;
			value = value < 1 ? 1 : value;
			$input.val(value);
			$input.change();
			updatePriceSlider($this , value)
		})

		up.on('click', function () {
			var value = (parseInt($input.val(), 10) || 1) + 1;
			var maximum = parseInt($input.attr('max'), 10);
			if (!isNaN(maximum)) {
				value = Math.min(value, maximum);
			}
			$input.val(value);
			$input.change();
			updatePriceSlider($this , value)
		})

		$input.on('change', function () {
			var value = parseInt($input.val(), 10);
			var minimum = parseInt($input.attr('min'), 10) || 1;
			var maximum = parseInt($input.attr('max'), 10);
			if (isNaN(value)) {
				value = minimum;
			}
			value = Math.max(minimum, value);
			if (!isNaN(maximum)) {
				value = Math.min(value, maximum);
			}
			$input.val(value);
		});
	});

	var priceInputMax = document.getElementById('price-max'),
			priceInputMin = document.getElementById('price-min');

	if (priceInputMax) {
		priceInputMax.addEventListener('change', function(){
			updatePriceSlider($(this).parent() , this.value)
		});
	}

	if (priceInputMin) {
		priceInputMin.addEventListener('change', function(){
			updatePriceSlider($(this).parent() , this.value)
		});
	}

	function updatePriceSlider(elem , value) {
		if ( elem.hasClass('price-min') ) {
			console.log('min')
			priceSlider.noUiSlider.set([value, null]);
		} else if ( elem.hasClass('price-max')) {
			console.log('max')
			priceSlider.noUiSlider.set([null, value]);
		}
	}

	// Price Slider
	var priceSlider = document.getElementById('price-slider');
	if (priceSlider) {
		noUiSlider.create(priceSlider, {
			start: [1, 999],
			connect: true,
			step: 1,
			range: {
				'min': 1,
				'max': 999
			}
		});

		priceSlider.noUiSlider.on('update', function( values, handle ) {
			var value = values[handle];
			handle ? priceInputMax.value = value : priceInputMin.value = value
		});
	}

	

})(jQuery);

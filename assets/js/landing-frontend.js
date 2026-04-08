/**
 * Flavor Landing Frontend Scripts
 *
 * Scripts para las landing pages en el frontend
 * Incluye Swiper para carruseles/sliders
 */
(function () {
	'use strict';

	// Inicializar cuando el DOM esté listo
	document.addEventListener('DOMContentLoaded', function () {
		initFaqToggles();
		initScrollAnimations();
		initCounterAnimations();
		initSwiperSliders();
		initLightbox();
	});

	/**
     * FAQ Toggle functionality
     */
	function initFaqToggles() {
		var faqQuestions = document.querySelectorAll('.flavor-faq__question');

		faqQuestions.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var expanded = this.getAttribute('aria-expanded') === 'true';
				this.setAttribute('aria-expanded', !expanded);
				var answerId = this.getAttribute('aria-controls');
				var answer = document.getElementById(answerId);
				if (answer) {
					answer.hidden = expanded;
				}
			});
		});
	}

	/**
     * Scroll animations using Intersection Observer
     */
	function initScrollAnimations() {
		// Check for reduced motion preference
		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			document.querySelectorAll('.flavor-animate').forEach(function (el) {
				el.classList.add('flavor-animated');
			});
			return;
		}

		if (!('IntersectionObserver' in window)) {
			document.querySelectorAll('.flavor-animate').forEach(function (el) {
				el.classList.add('flavor-animated');
			});
			return;
		}

		var animationObserver = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					var delay = entry.target.dataset.animationDelay || 0;
					setTimeout(function () {
						entry.target.classList.add('flavor-animated');
					}, delay);
					animationObserver.unobserve(entry.target);
				}
			});
		}, {
			threshold: 0.1,
			rootMargin: '0px 0px -50px 0px'
		});

		document.querySelectorAll('.flavor-animate').forEach(function (el) {
			animationObserver.observe(el);
		});
	}

	/**
     * Counter animation for stats
     */
	function initCounterAnimations() {
		var counters = document.querySelectorAll('.flavor-stat-card__value[data-value]');

		if (!counters.length) {return;}

		if (!('IntersectionObserver' in window)) {
			counters.forEach(function (counter) {
				counter.textContent = counter.dataset.value;
			});
			return;
		}

		var counterObserver = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					animateCounter(entry.target);
					counterObserver.unobserve(entry.target);
				}
			});
		}, { threshold: 0.5 });

		counters.forEach(function (counter) {
			counterObserver.observe(counter);
		});
	}

	/**
     * Animate a counter from 0 to target value
     */
	function animateCounter(element) {
		var targetValue = parseFloat(element.dataset.value);
		var startValue = 0;
		var duration = 2000;
		var startTime = null;
		var hasDecimal = targetValue % 1 !== 0;

		function updateCounter(timestamp) {
			if (!startTime) {startTime = timestamp;}
			var elapsed = timestamp - startTime;
			var progress = Math.min(elapsed / duration, 1);
			var easedProgress = 1 - Math.pow(1 - progress, 3);
			var currentValue = startValue + (targetValue - startValue) * easedProgress;

			element.textContent = hasDecimal ? currentValue.toFixed(1) : Math.floor(currentValue);

			if (progress < 1) {
				requestAnimationFrame(updateCounter);
			} else {
				element.textContent = hasDecimal ? targetValue.toFixed(1) : targetValue;
			}
		}

		requestAnimationFrame(updateCounter);
	}

	/**
     * Initialize Swiper sliders
     */
	function initSwiperSliders() {
		// Check if Swiper is available
		if (typeof Swiper === 'undefined') {
			console.warn('Swiper not loaded');
			return;
		}

		// Testimonios Carrusel
		document.querySelectorAll('.flavor-testimonios--carrusel .flavor-testimonios__grid').forEach(function (el) {
			convertToSwiper(el, {
				slidesPerView: 1,
				spaceBetween: 30,
				loop: true,
				autoplay: {
					delay: 5000,
					disableOnInteraction: false,
				},
				pagination: {
					el: '.swiper-pagination',
					clickable: true,
				},
				navigation: {
					nextEl: '.swiper-button-next',
					prevEl: '.swiper-button-prev',
				},
				breakpoints: {
					768: {
						slidesPerView: 2,
					},
					1024: {
						slidesPerView: 3,
					},
				},
			});
		});

		// Galeria Carrusel
		document.querySelectorAll('.flavor-galeria--carrusel .flavor-galeria__grid').forEach(function (el) {
			convertToSwiper(el, {
				slidesPerView: 1,
				spaceBetween: 20,
				loop: true,
				autoplay: {
					delay: 4000,
					disableOnInteraction: false,
				},
				pagination: {
					el: '.swiper-pagination',
					clickable: true,
				},
				navigation: {
					nextEl: '.swiper-button-next',
					prevEl: '.swiper-button-prev',
				},
				breakpoints: {
					480: {
						slidesPerView: 2,
					},
					768: {
						slidesPerView: 3,
					},
					1024: {
						slidesPerView: 4,
					},
				},
			});
		});

		// Equipo Carrusel
		document.querySelectorAll('.flavor-equipo--carrusel .flavor-equipo__grid').forEach(function (el) {
			convertToSwiper(el, {
				slidesPerView: 1,
				spaceBetween: 30,
				loop: true,
				autoplay: {
					delay: 5000,
					disableOnInteraction: false,
				},
				pagination: {
					el: '.swiper-pagination',
					clickable: true,
				},
				navigation: {
					nextEl: '.swiper-button-next',
					prevEl: '.swiper-button-prev',
				},
				breakpoints: {
					640: {
						slidesPerView: 2,
					},
					1024: {
						slidesPerView: 3,
					},
					1280: {
						slidesPerView: 4,
					},
				},
			});
		});

		// Logos Carrusel
		document.querySelectorAll('.flavor-logos--carrusel .flavor-logos__grid').forEach(function (el) {
			convertToSwiper(el, {
				slidesPerView: 2,
				spaceBetween: 30,
				loop: true,
				autoplay: {
					delay: 3000,
					disableOnInteraction: false,
				},
				breakpoints: {
					480: {
						slidesPerView: 3,
					},
					768: {
						slidesPerView: 4,
					},
					1024: {
						slidesPerView: 5,
					},
					1280: {
						slidesPerView: 6,
					},
				},
			});
		});

		// Logos Scroll Infinito
		document.querySelectorAll('.flavor-logos--scroll_infinito .flavor-logos__grid').forEach(function (el) {
			convertToSwiper(el, {
				slidesPerView: 'auto',
				spaceBetween: 40,
				loop: true,
				freeMode: true,
				speed: 5000,
				autoplay: {
					delay: 0,
					disableOnInteraction: false,
				},
				allowTouchMove: false,
			});
		});
	}

	/**
     * Convert grid element to Swiper
     */
	function convertToSwiper(gridElement, config) {
		// Get parent section for navigation/pagination
		var section = gridElement.closest('.flavor-section') || gridElement.parentElement;

		// Create wrapper structure
		var wrapper = document.createElement('div');
		wrapper.className = 'swiper flavor-swiper';

		var swiperWrapper = document.createElement('div');
		swiperWrapper.className = 'swiper-wrapper';

		// Move children to swiper-wrapper
		var children = Array.from(gridElement.children);
		children.forEach(function (child) {
			child.classList.add('swiper-slide');
			swiperWrapper.appendChild(child);
		});

		wrapper.appendChild(swiperWrapper);

		// Add navigation if configured
		if (config.navigation) {
			var prevBtn = document.createElement('div');
			prevBtn.className = 'swiper-button-prev';
			wrapper.appendChild(prevBtn);

			var nextBtn = document.createElement('div');
			nextBtn.className = 'swiper-button-next';
			wrapper.appendChild(nextBtn);

			config.navigation.nextEl = nextBtn;
			config.navigation.prevEl = prevBtn;
		}

		// Add pagination if configured
		if (config.pagination) {
			var pagination = document.createElement('div');
			pagination.className = 'swiper-pagination';
			wrapper.appendChild(pagination);

			config.pagination.el = pagination;
		}

		// Replace original grid with swiper
		gridElement.parentNode.replaceChild(wrapper, gridElement);

		// Initialize Swiper
		new Swiper(wrapper, config);
	}

	/**
     * Initialize Lightbox for galleries
     */
	function initLightbox() {
		var lightboxGalleries = document.querySelectorAll('.flavor-galeria--lightbox');

		if (!lightboxGalleries.length) {return;}

		// Create lightbox container
		var lightbox = document.createElement('div');
		lightbox.className = 'flavor-lightbox';
		lightbox.innerHTML = '<div class="flavor-lightbox__overlay"></div>' +
            '<div class="flavor-lightbox__content">' +
            '<button class="flavor-lightbox__close" aria-label="Cerrar">&times;</button>' +
            '<button class="flavor-lightbox__prev" aria-label="Anterior">&lsaquo;</button>' +
            '<button class="flavor-lightbox__next" aria-label="Siguiente">&rsaquo;</button>' +
            '<img class="flavor-lightbox__image" src="" alt="">' +
            '<div class="flavor-lightbox__counter"></div>' +
            '</div>';
		document.body.appendChild(lightbox);

		var currentGallery = null;
		var currentIndex = 0;

		lightboxGalleries.forEach(function (gallery) {
			var images = gallery.querySelectorAll('.flavor-galeria__item img');

			images.forEach(function (img, index) {
				img.style.cursor = 'pointer';
				img.addEventListener('click', function () {
					currentGallery = images;
					currentIndex = index;
					openLightbox(img.src);
				});
			});
		});

		function openLightbox(src) {
			lightbox.querySelector('.flavor-lightbox__image').src = src;
			updateCounter();
			lightbox.classList.add('active');
			document.body.style.overflow = 'hidden';
		}

		function closeLightbox() {
			lightbox.classList.remove('active');
			document.body.style.overflow = '';
		}

		function showPrev() {
			if (!currentGallery) {return;}
			currentIndex = (currentIndex - 1 + currentGallery.length) % currentGallery.length;
			lightbox.querySelector('.flavor-lightbox__image').src = currentGallery[currentIndex].src;
			updateCounter();
		}

		function showNext() {
			if (!currentGallery) {return;}
			currentIndex = (currentIndex + 1) % currentGallery.length;
			lightbox.querySelector('.flavor-lightbox__image').src = currentGallery[currentIndex].src;
			updateCounter();
		}

		function updateCounter() {
			if (!currentGallery) {return;}
			lightbox.querySelector('.flavor-lightbox__counter').textContent =
                (currentIndex + 1) + ' / ' + currentGallery.length;
		}

		// Event listeners
		lightbox.querySelector('.flavor-lightbox__overlay').addEventListener('click', closeLightbox);
		lightbox.querySelector('.flavor-lightbox__close').addEventListener('click', closeLightbox);
		lightbox.querySelector('.flavor-lightbox__prev').addEventListener('click', showPrev);
		lightbox.querySelector('.flavor-lightbox__next').addEventListener('click', showNext);

		// Keyboard navigation
		document.addEventListener('keydown', function (e) {
			if (!lightbox.classList.contains('active')) {return;}

			if (e.key === 'Escape') {closeLightbox();}
			if (e.key === 'ArrowLeft') {showPrev();}
			if (e.key === 'ArrowRight') {showNext();}
		});
	}

	/**
     * Smooth scroll for anchor links
     */
	document.addEventListener('click', function (e) {
		var target = e.target.closest('a[href^="#"]');
		if (!target) {return;}

		var hash = target.getAttribute('href');
		if (hash === '#') {return;}

		var targetElement = document.querySelector(hash);
		if (!targetElement) {return;}

		e.preventDefault();

		targetElement.scrollIntoView({
			behavior: 'smooth',
			block: 'start'
		});

		if (history.pushState) {
			history.pushState(null, null, hash);
		}
	});

})();

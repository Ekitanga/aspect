(() => {
  'use strict';

  const slider = document.querySelector('.aspect-hero-slider');
  if (!slider) {
    return;
  }

  const slides = [...slider.querySelectorAll('.aspect-hero-slide')];
  const indicators = [...slider.querySelectorAll('.aspect-hero-slider__indicator')];
  const previous = slider.querySelector('.aspect-hero-slider__btn--prev');
  const next = slider.querySelector('.aspect-hero-slider__btn--next');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let activeIndex = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-active')));
  let timer = null;
  let touchStartX = 0;

  const showSlide = (index, moveFocus = false) => {
    activeIndex = (index + slides.length) % slides.length;

    slides.forEach((slide, slideIndex) => {
      const active = slideIndex === activeIndex;
      slide.classList.toggle('is-active', active);
      slide.setAttribute('aria-hidden', active ? 'false' : 'true');
      slide.querySelectorAll('a, button, input, select, textarea').forEach((control) => {
        control.tabIndex = active ? 0 : -1;
      });
    });

    indicators.forEach((indicator, indicatorIndex) => {
      const active = indicatorIndex === activeIndex;
      indicator.classList.toggle('is-active', active);
      indicator.setAttribute('aria-selected', active ? 'true' : 'false');
      indicator.tabIndex = active ? 0 : -1;
    });

    if (moveFocus && indicators[activeIndex]) {
      indicators[activeIndex].focus();
    }
  };

  const stop = () => {
    if (timer) {
      window.clearInterval(timer);
      timer = null;
    }
  };

  const start = () => {
    stop();
    if (!reducedMotion.matches && !document.hidden && slides.length > 1) {
      timer = window.setInterval(() => showSlide(activeIndex + 1), 7000);
    }
  };

  previous?.addEventListener('click', () => {
    showSlide(activeIndex - 1);
    start();
  });
  next?.addEventListener('click', () => {
    showSlide(activeIndex + 1);
    start();
  });

  indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', () => {
      showSlide(index);
      start();
    });
    indicator.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
        event.preventDefault();
        showSlide(index + (event.key === 'ArrowRight' ? 1 : -1), true);
        start();
      }
    });
  });

  slider.addEventListener('mouseenter', stop);
  slider.addEventListener('mouseleave', start);
  slider.addEventListener('focusin', stop);
  slider.addEventListener('focusout', (event) => {
    if (!slider.contains(event.relatedTarget)) {
      start();
    }
  });
  slider.addEventListener('touchstart', (event) => {
    touchStartX = event.changedTouches[0].clientX;
    stop();
  }, { passive: true });
  slider.addEventListener('touchend', (event) => {
    const distance = event.changedTouches[0].clientX - touchStartX;
    if (Math.abs(distance) > 48) {
      showSlide(activeIndex + (distance < 0 ? 1 : -1));
    }
    start();
  }, { passive: true });
  document.addEventListener('visibilitychange', start);
  reducedMotion.addEventListener?.('change', start);

  showSlide(activeIndex);
  start();
})();

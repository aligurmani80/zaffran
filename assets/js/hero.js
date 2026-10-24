/**
 * Zaffran Hero Collection Slider
 * Autoplay with progress, arrows, dots, touch swipe, hover/focus pause,
 * tab-visibility pause and full reduced-motion support.
 */

(function () {
  'use strict';

  function initHeroSlider() {
    var slider = document.getElementById('hero-slider');
    if (!slider) return;

    var slides = Array.prototype.slice.call(slider.querySelectorAll('.hero-slide'));
    var dots = Array.prototype.slice.call(slider.querySelectorAll('.hero-dot'));
    var prevBtn = slider.querySelector('.js-hero-prev');
    var nextBtn = slider.querySelector('.js-hero-next');
    if (slides.length < 2) return;

    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var duration = parseInt(slider.getAttribute('data-autoplay'), 10) || 6500;
    var current = 0;
    var timer = null;
    var paused = false;

    slider.style.setProperty('--hero-duration', duration + 'ms');

    function animateProgress() {
      slider.classList.remove('is-playing');
      // Force the CSS animation to restart on every slide change.
      void slider.offsetWidth;
      if (!prefersReduced && !paused) {
        slider.classList.add('is-playing');
      }
    }

    function goTo(index, userInitiated) {
      current = (index + slides.length) % slides.length;

      slides.forEach(function (slide, i) {
        var active = i === current;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', active ? 'false' : 'true');
      });

      dots.forEach(function (dot, i) {
        var active = i === current;
        dot.classList.toggle('is-active', active);
        dot.setAttribute('aria-selected', active ? 'true' : 'false');
      });

      animateProgress();
      schedule();

      if (userInitiated) {
        // Restart the visual timer after manual navigation.
        if (timer) {
          window.clearTimeout(timer);
          timer = null;
        }
        schedule();
      }
    }

    function schedule() {
      if (timer) {
        window.clearTimeout(timer);
        timer = null;
      }
      if (prefersReduced || paused || document.hidden) return;
      timer = window.setTimeout(function () {
        goTo(current + 1);
      }, duration);
    }

    function stop() {
      paused = true;
      if (timer) {
        window.clearTimeout(timer);
        timer = null;
      }
      slider.classList.remove('is-playing');
    }

    function start() {
      paused = false;
      animateProgress();
      schedule();
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', function () { goTo(current - 1, true); });
    }
    if (nextBtn) {
      nextBtn.addEventListener('click', function () { goTo(current + 1, true); });
    }

    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        goTo(parseInt(dot.getAttribute('data-goto'), 10) || 0, true);
      });
    });

    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    slider.addEventListener('focusin', stop);
    slider.addEventListener('focusout', function (event) {
      if (!slider.contains(event.relatedTarget)) {
        start();
      }
    });

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        stop();
      } else if (!paused) {
        start();
      }
    });

    // Touch swipe
    var touchStartX = 0;
    var touchStartY = 0;
    var touchDeltaX = 0;
    var touching = false;

    slider.addEventListener('touchstart', function (event) {
      if (event.touches.length !== 1) return;
      touching = true;
      touchStartX = event.touches[0].clientX;
      touchStartY = event.touches[0].clientY;
      touchDeltaX = 0;
      stop();
    }, { passive: true });

    slider.addEventListener('touchmove', function (event) {
      if (!touching || event.touches.length !== 1) return;
      touchDeltaX = event.touches[0].clientX - touchStartX;
    }, { passive: true });

    slider.addEventListener('touchend', function (event) {
      if (!touching) return;
      touching = false;
      var deltaY = 0;
      if (event.changedTouches && event.changedTouches.length) {
        deltaY = event.changedTouches[0].clientY - touchStartY;
      }
      if (Math.abs(touchDeltaX) > 45 && Math.abs(touchDeltaX) > Math.abs(deltaY)) {
        goTo(touchDeltaX < 0 ? current + 1 : current - 1, true);
        start();
      } else {
        start();
      }
    }, { passive: true });

    // Keyboard support when focus is inside the hero.
    slider.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') {
        goTo(current - 1, true);
      } else if (event.key === 'ArrowRight') {
        goTo(current + 1, true);
      }
    });

    goTo(0);
    start();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHeroSlider);
  } else {
    initHeroSlider();
  }
})();

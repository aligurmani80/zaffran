/**
 * Zaffran Homepage
 * Horizontal review/proof rails and the genuine-proof lightbox trigger.
 * The hero slider lives in hero.js and is reused unchanged.
 */

(function () {
  'use strict';

  function initScrollRail(trackId, prevSelector, nextSelector) {
    var track = document.getElementById(trackId);
    var prev = document.querySelector(prevSelector);
    var next = document.querySelector(nextSelector);
    if (!track || !prev || !next) return;

    function stepSize() {
      var firstCard = track.firstElementChild;
      if (!firstCard) return track.clientWidth * 0.8;

      var styles = window.getComputedStyle(track);
      var gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;
      return firstCard.getBoundingClientRect().width + gap;
    }

    function updateControls() {
      var maxScroll = track.scrollWidth - track.clientWidth;
      var noOverflow = maxScroll <= 2;
      prev.disabled = noOverflow || track.scrollLeft <= 2;
      next.disabled = noOverflow || track.scrollLeft >= maxScroll - 2;
    }

    prev.addEventListener('click', function () {
      track.scrollBy({ left: -stepSize(), behavior: 'smooth' });
    });

    next.addEventListener('click', function () {
      track.scrollBy({ left: stepSize(), behavior: 'smooth' });
    });

    track.addEventListener('scroll', updateControls, { passive: true });
    window.addEventListener('resize', updateControls);
    updateControls();
  }

  function initProofLightbox() {
    var proofButtons = document.querySelectorAll('.js-home-proof');
    if (!proofButtons.length || typeof window.openPublicLightbox !== 'function') return;

    proofButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        window.openPublicLightbox(
          button.getAttribute('data-proof-img') || '',
          button.getAttribute('data-proof-name') || 'Zaffran customer',
          button.getAttribute('data-proof-caption') || ''
        );
      });
    });
  }

  function init() {
    initScrollRail('homeReviewsTrack', '.home-reviews-prev', '.home-reviews-next');
    initScrollRail('homeProofsTrack', '.home-proofs-prev', '.home-proofs-next');
    initProofLightbox();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

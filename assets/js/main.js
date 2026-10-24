/**
 * Zaffran Main Vanilla JavaScript - Phase 3 Enhanced
 * Announcement bar, Header scroll, Mobile Drawer, Interactive Search Dialog,
 * Server-Side Cart Operations (Add, Update, Remove), Quantity Stepper, Checkout Idempotency, and Toast Feedback.
 */

(function () {
  'use strict';

  // Helper to read CSRF token from meta tag
  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  // 1. Announcement Bar Rotator
  function initAnnouncementBar() {
    const bar = document.querySelector('.announcement-bar');
    if (!bar) return;

    const items = bar.querySelectorAll('.announcement-item');
    if (items.length <= 1) return;

    let currentIndex = 0;
    const prevBtn = bar.querySelector('.announcement-prev');
    const nextBtn = bar.querySelector('.announcement-next');
    let autoInterval = null;

    function showSlide(index) {
      items.forEach((item, i) => {
        item.classList.toggle('active', i === index);
      });
      currentIndex = index;
    }

    function nextSlide() {
      const next = (currentIndex + 1) % items.length;
      showSlide(next);
    }

    function prevSlide() {
      const prev = (currentIndex - 1 + items.length) % items.length;
      showSlide(prev);
    }

    function startAuto() {
      stopAuto();
      autoInterval = setInterval(nextSlide, 5000);
    }

    function stopAuto() {
      if (autoInterval) {
        clearInterval(autoInterval);
        autoInterval = null;
      }
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        nextSlide();
        startAuto();
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        prevSlide();
        startAuto();
      });
    }

    bar.addEventListener('mouseenter', stopAuto);
    bar.addEventListener('mouseleave', startAuto);

    startAuto();
  }

  // 2. Sticky Header Elevation on Scroll
  function initHeaderScroll() {
    const header = document.querySelector('.site-header');
    if (!header) return;

    function checkScroll() {
      if (window.scrollY > 20) {
        header.classList.add('is-scrolled');
      } else {
        header.classList.remove('is-scrolled');
      }
    }

    window.addEventListener('scroll', checkScroll, { passive: true });
    checkScroll();
  }

  // 3. Mobile Navigation Drawer (Focus Trapped & Restored, Escape & Click Outside Handled)
  function initMobileDrawer() {
    const toggleBtn = document.querySelector('.mobile-nav-toggle');
    const drawer = document.querySelector('.mobile-drawer');
    const backdrop = document.querySelector('.mobile-drawer-backdrop');
    const closeBtn = document.querySelector('.drawer-close-btn');

    if (!toggleBtn || !drawer || !backdrop) return;

    let previousActiveElement = null;

    function getFocusableElements() {
      return Array.from(
        drawer.querySelectorAll(
          'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )
      );
    }

    function openDrawer() {
      previousActiveElement = document.activeElement;
      drawer.classList.add('open');
      backdrop.classList.add('open');
      document.body.style.overflow = 'hidden';
      toggleBtn.setAttribute('aria-expanded', 'true');

      // Move focus inside drawer
      setTimeout(() => {
        if (closeBtn) {
          closeBtn.focus();
        } else {
          const firstFocusable = getFocusableElements()[0];
          if (firstFocusable) firstFocusable.focus();
        }
      }, 50);
    }

    function closeDrawer() {
      drawer.classList.remove('open');
      backdrop.classList.remove('open');
      document.body.style.overflow = '';
      toggleBtn.setAttribute('aria-expanded', 'false');

      // Restore focus
      if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
        previousActiveElement.focus();
      } else {
        toggleBtn.focus();
      }
    }

    toggleBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (drawer.classList.contains('open')) {
        closeDrawer();
      } else {
        openDrawer();
      }
    });

    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);

    // Trap focus and handle escape key
    document.addEventListener('keydown', (e) => {
      if (!drawer.classList.contains('open')) return;

      if (e.key === 'Escape') {
        e.preventDefault();
        closeDrawer();
        return;
      }

      if (e.key === 'Tab') {
        const focusable = getFocusableElements();
        if (focusable.length === 0) {
          e.preventDefault();
          return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (e.shiftKey) {
          if (document.activeElement === first) {
            e.preventDefault();
            last.focus();
          }
        } else {
          if (document.activeElement === last) {
            e.preventDefault();
            first.focus();
          }
        }
      }
    });

    const drawerLinks = drawer.querySelectorAll('a');
    drawerLinks.forEach((link) => {
      link.addEventListener('click', closeDrawer);
    });
  }

  // 4. Interactive Search Dialog
  function initSearchModal() {
    const triggers = document.querySelectorAll('.js-search-trigger');
    const overlay = document.getElementById('search-overlay');
    if (!overlay) return;

    const closeBtn = overlay.querySelector('.js-search-close');
    const inputField = overlay.querySelector('.search-input-field');

    function openSearch() {
      overlay.classList.add('open');
      overlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      setTimeout(() => {
        if (inputField) inputField.focus();
      }, 100);
    }

    function closeSearch() {
      overlay.classList.remove('open');
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    triggers.forEach((trigger) => {
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        openSearch();
      });
    });

    if (closeBtn) {
      closeBtn.addEventListener('click', closeSearch);
    }

    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        closeSearch();
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && overlay.classList.contains('open')) {
        closeSearch();
      }
    });
  }

  // 5. Toast Notification System
  function showToast(message, isError = false) {
    let toast = document.querySelector('.zaffran-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'zaffran-toast';
      document.body.appendChild(toast);
    }
    
    const icon = isError
      ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`
      : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#deb564" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>`;

    toast.innerHTML = `${icon}<span>${message}</span>`;
    toast.classList.add('show');
    
    setTimeout(() => {
      toast.classList.remove('show');
    }, 3400);
  }

  // Helper to sync header cart badge
  function updateHeaderCartBadge(count) {
    const badges = document.querySelectorAll('.cart-count-badge');
    badges.forEach((b) => {
      b.textContent = count;
      b.style.display = count > 0 ? 'flex' : 'none';
    });
  }

  // 6. Server-Side Add-to-Cart Operation
  async function performServerAddToCart(productId, quantity = 1, buttonEl = null) {
    const csrfToken = getCsrfToken();
    if (buttonEl) {
      buttonEl.disabled = true;
      buttonEl.style.opacity = '0.7';
    }

    try {
      const response = await fetch('cart_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
          action: 'add',
          product_id: parseInt(productId, 10),
          quantity: parseInt(quantity, 10) || 1,
          csrf_token: csrfToken
        })
      });

      const data = await response.json();

      if (response.ok && data.success) {
        updateHeaderCartBadge(data.cart_count);
        showToast(data.message || 'Product added to shopping bag.');
      } else {
        showToast(data.message || 'Could not add item to bag.', true);
      }
    } catch (err) {
      console.error('Cart operation failed:', err);
      showToast('Network error while updating your bag.', true);
    } finally {
      if (buttonEl) {
        buttonEl.disabled = false;
        buttonEl.style.opacity = '1';
      }
    }
  }

  // 7. Cart Page Live Quantity & Remove AJAX Handlers
  function initCartPageInteractions() {
    const cartContainer = document.getElementById('cart-items-container');
    if (!cartContainer) return;

    let isUpdating = false;

    // Quantity buttons in cart table
    cartContainer.addEventListener('click', async (e) => {
      const btn = e.target.closest('.js-cart-qty-btn');
      if (!btn || isUpdating) return;

      const productId = btn.getAttribute('data-id');
      const action = btn.getAttribute('data-action');
      const input = cartContainer.querySelector(`.js-cart-qty-input[data-id="${productId}"]`);
      if (!input) return;

      let currentQty = parseInt(input.value, 10) || 1;
      let newQty = action === 'increase' ? currentQty + 1 : currentQty - 1;

      if (newQty < 1) newQty = 1;
      if (newQty > 50) newQty = 50;
      if (newQty === currentQty) return;

      isUpdating = true;
      btn.style.opacity = '0.5';

      try {
        const csrfToken = getCsrfToken();
        const res = await fetch('cart_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({
            action: 'update',
            product_id: parseInt(productId, 10),
            quantity: newQty,
            csrf_token: csrfToken
          })
        });

        const data = await res.json();
        if (res.ok && data.success) {
          input.value = newQty;
          updateHeaderCartBadge(data.cart_count);

          // Update row total
          const matchingItem = data.cart.items.find(i => i.product_id == productId);
          if (matchingItem) {
            const rowTotalEl = document.getElementById(`row-total-${productId}`);
            if (rowTotalEl) rowTotalEl.textContent = matchingItem.formatted_total_price;
          }

          // Update sidebar summaries
          const subtotalEl = document.getElementById('summary-subtotal-val');
          const grandTotalEl = document.getElementById('summary-grand-total-val');
          const countEl = document.getElementById('summary-items-count');
          const shippingEl = document.getElementById('summary-shipping-val');

          if (subtotalEl) subtotalEl.textContent = data.cart.formatted_subtotal;
          if (grandTotalEl) grandTotalEl.textContent = data.cart.formatted_grand_total;
          if (countEl) countEl.textContent = data.cart.total_count;
          if (shippingEl) shippingEl.textContent = data.cart.shipping.formatted_fee;

          showToast('Shopping bag updated.');
        } else {
          showToast(data.message || 'Could not update quantity.', true);
        }
      } catch (err) {
        showToast('Network error while updating quantity.', true);
      } finally {
        isUpdating = false;
        btn.style.opacity = '1';
      }
    });

    // Remove Item button in cart table
    cartContainer.addEventListener('click', async (e) => {
      const removeBtn = e.target.closest('.js-cart-remove-btn');
      if (!removeBtn || isUpdating) return;

      const productId = removeBtn.getAttribute('data-id');
      const productName = removeBtn.getAttribute('data-name') || 'Item';
      const row = document.getElementById(`cart-row-${productId}`);

      isUpdating = true;
      if (row) row.style.opacity = '0.4';

      try {
        const csrfToken = getCsrfToken();
        const res = await fetch('cart_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({
            action: 'remove',
            product_id: parseInt(productId, 10),
            csrf_token: csrfToken
          })
        });

        const data = await res.json();
        if (res.ok && data.success) {
          if (row) row.remove();
          updateHeaderCartBadge(data.cart_count);

          if (data.cart.items.length === 0) {
            window.location.reload(); // Render empty cart view
            return;
          }

          // Update sidebar summaries
          const subtotalEl = document.getElementById('summary-subtotal-val');
          const grandTotalEl = document.getElementById('summary-grand-total-val');
          const countEl = document.getElementById('summary-items-count');
          const shippingEl = document.getElementById('summary-shipping-val');

          if (subtotalEl) subtotalEl.textContent = data.cart.formatted_subtotal;
          if (grandTotalEl) grandTotalEl.textContent = data.cart.formatted_grand_total;
          if (countEl) countEl.textContent = data.cart.total_count;
          if (shippingEl) shippingEl.textContent = data.cart.shipping.formatted_fee;

          showToast(`${productName} removed from bag.`);
        } else {
          showToast(data.message || 'Could not remove item.', true);
          if (row) row.style.opacity = '1';
        }
      } catch (err) {
        showToast('Network error while removing item.', true);
        if (row) row.style.opacity = '1';
      } finally {
        isUpdating = false;
      }
    });
  }

  // 8. Prevent Duplicate Submissions on Checkout
  function initCheckoutForm() {
    const checkoutForm = document.getElementById('checkout-form');
    if (!checkoutForm) return;

    checkoutForm.addEventListener('submit', () => {
      const submitBtn = document.getElementById('place-order-submit-btn');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Processing Order...';
        submitBtn.style.opacity = '0.7';
      }
    });
  }

  // 9. Attach Global Add-to-Cart Buttons
  function initCartButtons() {
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.js-add-to-cart');
      if (!btn) return;
      e.preventDefault();

      const productId = btn.getAttribute('data-id');
      if (productId) {
        performServerAddToCart(productId, 1, btn);
      }
    });

    // Product Detail Page CTA
    const detailBtn = document.querySelector('.js-detail-add-to-cart');
    if (detailBtn) {
      detailBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const productId = detailBtn.getAttribute('data-id');
        const qtyInput = document.getElementById('detail-qty-input');
        const quantity = qtyInput ? parseInt(qtyInput.value, 10) : 1;
        if (productId) {
          performServerAddToCart(productId, quantity, detailBtn);
        }
      });
    }
  }

  // 10. Quantity Stepper for Product Details View
  function initQuantityControls() {
    const decBtn = document.getElementById('qty-decrease-btn');
    const incBtn = document.getElementById('qty-increase-btn');
    const qtyInput = document.getElementById('detail-qty-input');

    if (!qtyInput || !decBtn || !incBtn) return;

    decBtn.addEventListener('click', () => {
      let current = parseInt(qtyInput.value, 10) || 1;
      const min = parseInt(qtyInput.getAttribute('min'), 10) || 1;
      if (current > min) {
        qtyInput.value = current - 1;
      }
    });

    incBtn.addEventListener('click', () => {
      let current = parseInt(qtyInput.value, 10) || 1;
      const max = parseInt(qtyInput.getAttribute('max'), 10) || 50;
      if (current < max) {
        qtyInput.value = current + 1;
      }
    });
  }

  // 11. Accessible FAQ Accordion
  function initFaqAccordion() {
    const faqItems = document.querySelectorAll('.faq-item');
    if (!faqItems.length) return;

    faqItems.forEach((item) => {
      const headerBtn = item.querySelector('.faq-header-btn');
      const bodyPanel = item.querySelector('.faq-body-panel');
      if (!headerBtn || !bodyPanel) return;

      headerBtn.addEventListener('click', () => {
        const isOpen = item.classList.contains('active');

        // Close other items for a clean accordion experience
        faqItems.forEach((other) => {
          if (other !== item) {
            other.classList.remove('active');
            const otherBtn = other.querySelector('.faq-header-btn');
            const otherPanel = other.querySelector('.faq-body-panel');
            if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
            if (otherPanel) otherPanel.style.maxHeight = null;
          }
        });

        if (isOpen) {
          item.classList.remove('active');
          headerBtn.setAttribute('aria-expanded', 'false');
          bodyPanel.style.maxHeight = null;
        } else {
          item.classList.add('active');
          headerBtn.setAttribute('aria-expanded', 'true');
          bodyPanel.style.maxHeight = bodyPanel.scrollHeight + 'px';
        }
      });
    });
  }

  // 12. Perspective 3D Hover Tilt (Desktop Only - Disabled on Touch / Reduced Motion)
  function initCard3DTilt() {
    // Respect reduced-motion
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    // Strict Touchscreen Detection: Disable cursor-based 3D tilt on touch devices
    const isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || window.matchMedia('(pointer: coarse)').matches;
    if (isTouch) return;

    const tiltTargets = document.querySelectorAll('.js-tilt-card, .category-circle-card');
    if (!tiltTargets.length) return;

    tiltTargets.forEach((card) => {
      let isHovered = false;
      const avatarOrMedia = card.querySelector('.category-circle-avatar') || card.querySelector('.product-media') || card;

      card.setAttribute('data-tilt-active', 'true');

      function handleMouseMove(e) {
        if (!isHovered) return;
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left; // x position within card
        const y = e.clientY - rect.top;  // y position within card

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        // Subtle angles: restrained 3-6 degrees max
        const rotateX = ((y - centerY) / centerY) * -5.5;
        const rotateY = ((x - centerX) / centerX) * 5.5;

        // Apply smooth 3D transform
        if (card.classList.contains('category-circle-card')) {
          if (avatarOrMedia) {
            avatarOrMedia.style.transform = `perspective(800px) translateY(-8px) scale(1.04) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg)`;
          }
        } else {
          card.style.transform = `perspective(1000px) translateY(-7px) scale(1.015) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg)`;
        }
      }

      function handleMouseEnter() {
        isHovered = true;
        if (card.classList.contains('category-circle-card')) {
          if (avatarOrMedia) avatarOrMedia.style.transition = 'transform 0.1s ease-out, box-shadow 0.3s ease, outline-color 0.3s ease';
        } else {
          card.style.transition = 'transform 0.1s ease-out, box-shadow 0.3s ease, border-color 0.3s ease';
        }
      }

      function handleMouseLeave() {
        isHovered = false;
        if (card.classList.contains('category-circle-card')) {
          if (avatarOrMedia) {
            avatarOrMedia.style.transition = 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1), box-shadow 0.4s ease, outline-color 0.3s ease';
            avatarOrMedia.style.transform = 'perspective(800px) translateY(0) scale(1) rotateX(0deg) rotateY(0deg)';
          }
        } else {
          card.style.transition = 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1), box-shadow 0.4s ease, border-color 0.3s ease';
          card.style.transform = 'perspective(1000px) translateY(0) scale(1) rotateX(0deg) rotateY(0deg)';
        }
      }

      card.addEventListener('mouseenter', handleMouseEnter);
      card.addEventListener('mousemove', handleMouseMove);
      card.addEventListener('mouseleave', handleMouseLeave);
    });
  }

  // 13. Customer Reviews & WhatsApp Social Proof Screenshot Gallery
  function initReviewsSlider() {
    // WhatsApp Proofs Carousel Controls
    const proofsContainer = document.getElementById('proofsScrollContainer');
    const prevBtn = document.querySelector('.proofs-prev-btn');
    const nextBtn = document.querySelector('.proofs-next-btn');

    if (proofsContainer) {
      if (prevBtn) {
        prevBtn.addEventListener('click', () => {
          proofsContainer.scrollBy({ left: -320, behavior: 'smooth' });
        });
      }
      if (nextBtn) {
        nextBtn.addEventListener('click', () => {
          proofsContainer.scrollBy({ left: 320, behavior: 'smooth' });
        });
      }

      // Mouse drag scrolling on desktop
      let isDown = false;
      let startX = 0;
      let scrollLeft = 0;

      proofsContainer.addEventListener('mousedown', (e) => {
        isDown = true;
        startX = e.pageX - proofsContainer.offsetLeft;
        scrollLeft = proofsContainer.scrollLeft;
        proofsContainer.style.cursor = 'grabbing';
      });

      window.addEventListener('mouseup', () => {
        isDown = false;
        if (proofsContainer) proofsContainer.style.cursor = '';
      });

      proofsContainer.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - proofsContainer.offsetLeft;
        const walk = (x - startX) * 1.5;
        proofsContainer.scrollLeft = scrollLeft - walk;
      });
    }

    // Traditional Text Reviews fallback container
    const container = document.querySelector('.reviews-scroll-container');
    if (!container) return;

    const cards = container.querySelectorAll('.review-card');
    if (cards.length <= 1) return;

    let isDown = false;
    let startX = 0;
    let scrollLeft = 0;
    let autoScrollTimer = null;
    let isPaused = false;

    // Mouse Dragging Support for Desktop
    container.addEventListener('mousedown', (e) => {
      isDown = true;
      startX = e.pageX - container.offsetLeft;
      scrollLeft = container.scrollLeft;
      container.style.scrollBehavior = 'auto';
    });

    window.addEventListener('mouseup', () => {
      isDown = false;
      container.style.scrollBehavior = '';
    });

    container.addEventListener('mousemove', (e) => {
      if (!isDown) return;
      e.preventDefault();
      const x = e.pageX - container.offsetLeft;
      const walk = (x - startX) * 1.6; // Scroll speed factor
      container.scrollLeft = scrollLeft - walk;
    });

    // Pause auto-scroll on hover or touch
    container.addEventListener('mouseenter', () => { isPaused = true; });
    container.addEventListener('mouseleave', () => { isPaused = false; });
    container.addEventListener('touchstart', () => { isPaused = true; }, { passive: true });
    container.addEventListener('touchend', () => { 
      setTimeout(() => { isPaused = false; }, 2500); 
    }, { passive: true });

    // Respect reduced motion
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      function stepAutoScroll() {
        if (!isPaused && !isDown) {
          const maxScroll = container.scrollWidth - container.clientWidth;
          if (container.scrollLeft >= maxScroll - 4) {
            container.scrollLeft = 0;
          } else {
            container.scrollLeft += 1;
          }
        }
      }
      autoScrollTimer = setInterval(stepAutoScroll, 35);
    }
  }

  // 14. Lightweight IntersectionObserver Scroll Entrance Reveals
  function initScrollReveals() {
    // Fallback if reduced motion is requested
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      document.querySelectorAll('.reveal-on-scroll, .reveal-stagger').forEach((el) => {
        el.classList.add('revealed');
      });
      return;
    }

    if (!('IntersectionObserver' in window)) {
      document.querySelectorAll('.reveal-on-scroll, .reveal-stagger').forEach((el) => {
        el.classList.add('revealed');
      });
      return;
    }

    const observer = new IntersectionObserver(
      (entries, obs) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('revealed');
            obs.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px'
      }
    );

    document.querySelectorAll('.reveal-on-scroll, .reveal-stagger').forEach((el) => {
      observer.observe(el);
    });
  }

  // Products dropdown — click/keyboard support alongside CSS hover
  function initNavDropdown() {
    const item = document.querySelector('.nav-item-has-dropdown');
    if (!item) return;
    const toggle = item.querySelector('.nav-dropdown-toggle');
    if (!toggle) return;

    function closeDropdown() {
      item.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const willOpen = !item.classList.contains('is-open');
      item.classList.toggle('is-open', willOpen);
      toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });

    item.addEventListener('focusout', (e) => {
      if (!item.contains(e.relatedTarget)) {
        closeDropdown();
      }
    });

    document.addEventListener('click', (e) => {
      if (!item.contains(e.target)) {
        closeDropdown();
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeDropdown();
      }
    });
  }

  // Initialize on DOM Ready
  document.addEventListener('DOMContentLoaded', () => {
    initAnnouncementBar();
    initNavDropdown();
    initHeaderScroll();
    initMobileDrawer();
    initSearchModal();
    initCartButtons();
    initCartPageInteractions();
    initCheckoutForm();
    initQuantityControls();
    initFaqAccordion();
    initCard3DTilt();
    initReviewsSlider();
    initScrollReveals();
  });

  // Global Lightbox helper for WhatsApp proof screenshots
  window.openPublicLightbox = function (imgSrc, customerName, caption) {
    const modal = document.getElementById('publicProofLightbox');
    const img = document.getElementById('publicLightboxImg');
    const custEl = document.getElementById('publicLightboxCustomer');
    const capEl = document.getElementById('publicLightboxCaption');

    if (!modal || !img) return;

    img.src = imgSrc;
    if (custEl) custEl.textContent = customerName || 'Verified WhatsApp Customer';
    if (capEl) capEl.textContent = caption ? '“' + caption + '”' : '';

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  };

  window.closePublicLightbox = function () {
    const modal = document.getElementById('publicProofLightbox');
    if (modal) {
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }
  };

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      window.closePublicLightbox();
    }
  });
})();

/* Thanh Thanh – JS công khai: menu di động, chế độ sáng/tối, sao chép link. Không phụ thuộc thư viện. */
(function () {
  'use strict';
  var root = document.documentElement;

  // ----- Menu di động -----
  var menu = document.getElementById('mobile-menu');
  var openBtn = document.querySelector('[data-menu-open]');
  var lastFocus = null;
  // Phần nền phía sau lớp phủ menu: đặt inert để Tab/đọc màn hình không đi ra ngoài menu (WCAG)
  var backdrop = ['.skip-link', '.site-header__bar', '#main', '.site-footer'];

  function setInert(on) {
    backdrop.forEach(function (sel) {
      var el = document.querySelector(sel);
      if (!el) return;
      if (on) el.setAttribute('inert', ''); else el.removeAttribute('inert');
    });
  }

  function openMenu() {
    if (!menu) return;
    lastFocus = document.activeElement;
    menu.hidden = false;
    setInert(true);
    document.body.classList.add('menu-open');
    openBtn && openBtn.setAttribute('aria-expanded', 'true');
    var first = menu.querySelector('a, button');
    first && first.focus();
  }
  function closeMenu() {
    if (!menu || menu.hidden) return;
    menu.hidden = true;
    setInert(false);
    document.body.classList.remove('menu-open');
    openBtn && openBtn.setAttribute('aria-expanded', 'false');
    lastFocus && lastFocus.focus();
  }
  openBtn && openBtn.addEventListener('click', openMenu);
  document.querySelectorAll('[data-menu-close]').forEach(function (b) { b.addEventListener('click', closeMenu); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });

  // ----- Sáng / tối (giá trị ban đầu đã được script inline trong <head> đặt) -----
  function isDark() {
    var t = root.getAttribute('data-theme');
    return t ? t === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
  }
  document.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
    b.setAttribute('aria-pressed', String(isDark()));
    b.addEventListener('click', function () {
      var next = isDark() ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('tt-theme', next); } catch (e) {}
      document.querySelectorAll('[data-theme-toggle]').forEach(function (x) { x.setAttribute('aria-pressed', String(next === 'dark')); });
    });
  });

  // ----- Sao chép link -----
  document.querySelectorAll('[data-copy-link]').forEach(function (b) {
    b.addEventListener('click', function () {
      var url = b.getAttribute('data-copy-link') || location.href;
      var done = function () { var t = b.textContent; b.textContent = 'Đã sao chép ✓'; setTimeout(function () { b.textContent = t; }, 2000); };
      if (navigator.clipboard) navigator.clipboard.writeText(url).then(done, function () {});
    });
  });
})();

/* Thanh Thanh – JS công khai: menu di động, sáng/tối, tab, cỡ chữ kịch bản, soạn email pitching,
   sao chép link, beacon thống kê. Không phụ thuộc thư viện; trang vẫn dùng được khi tắt JS. */
(function () {
  'use strict';
  var root = document.documentElement;
  function $$(sel, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(sel)); }

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
    if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
    var first = menu.querySelector('a, button');
    if (first) first.focus();
  }
  function closeMenu() {
    if (!menu || menu.hidden) return;
    menu.hidden = true;
    setInert(false);
    document.body.classList.remove('menu-open');
    if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
    if (lastFocus) lastFocus.focus();
  }
  if (openBtn) openBtn.addEventListener('click', openMenu);
  $$('[data-menu-close]').forEach(function (b) { b.addEventListener('click', closeMenu); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });

  // ----- Sáng / tối (giá trị đã lưu được script inline trong <head> đặt sẵn) -----
  function isDark() {
    var t = root.getAttribute('data-theme');
    return t ? t === 'dark' : !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
  }
  function syncToggle() {
    var dark = String(isDark());
    $$('[data-theme-toggle]').forEach(function (b) { b.setAttribute('aria-pressed', dark); });
  }
  $$('[data-theme-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      var next = isDark() ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('tt-theme', next); } catch (e) { /* bị chặn lưu: vẫn đổi trong phiên */ }
      syncToggle();
    });
  });
  syncToggle();

  // ----- Bóng mờ dưới header khi cuộn -----
  var header = document.querySelector('[data-header]');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // ----- Tab ARIA (Góc sáng tác). Không có JS: thanh tab ẩn, mọi phần đều hiện -----
  $$('[data-tabs]').forEach(function (list) {
    var tabs = $$('[role="tab"]', list);
    if (!tabs.length) return;
    function select(tab, focus) {
      tabs.forEach(function (t) {
        var on = t === tab;
        var panel = document.getElementById(t.getAttribute('aria-controls') || '');
        t.setAttribute('aria-selected', String(on));
        t.tabIndex = on ? 0 : -1;
        if (panel) panel.hidden = !on;
      });
      if (focus) tab.focus();
    }
    tabs.forEach(function (t) {
      var panel = document.getElementById(t.getAttribute('aria-controls') || '');
      if (panel) { panel.setAttribute('role', 'tabpanel'); panel.setAttribute('aria-labelledby', t.id); }
      t.addEventListener('click', function () { select(t, false); });
    });
    list.addEventListener('keydown', function (e) {
      var i = tabs.indexOf(document.activeElement);
      if (i < 0) return;
      var next = { ArrowRight: tabs[(i + 1) % tabs.length], ArrowLeft: tabs[(i - 1 + tabs.length) % tabs.length], Home: tabs[0], End: tabs[tabs.length - 1] }[e.key];
      if (next) { e.preventDefault(); select(next, true); }
    });
    var start = tabs.filter(function (t) { return t.getAttribute('data-tab') === list.getAttribute('data-active'); })[0] || tabs[0];
    list.hidden = false;
    select(start, false);
  });

  // ----- Cỡ chữ trang kịch bản -----
  $$('[data-font-ctrl]').forEach(function (ctrl) {
    var sheet = document.getElementById(ctrl.getAttribute('data-font-ctrl') || '');
    var out = ctrl.querySelector('output');
    var down = ctrl.querySelector('[data-font="-1"]');
    var up = ctrl.querySelector('[data-font="1"]');
    if (!sheet || !down || !up) return;
    var sizes = [13, 14, 15, 16, 17, 18], idx = 2;
    function apply() {
      sheet.style.setProperty('--script-fs', sizes[idx] + 'px');
      if (out) out.textContent = sizes[idx] + 'px';
      down.disabled = idx === 0;
      up.disabled = idx === sizes.length - 1;
    }
    down.addEventListener('click', function () { if (idx > 0) { idx--; apply(); } });
    up.addEventListener('click', function () { if (idx < sizes.length - 1) { idx++; apply(); } });
    ctrl.hidden = false;
    apply();
  });

  // ----- Mẫu kết nối pitching: kiểm tra rồi mở ứng dụng email soạn sẵn (không gửi lên máy chủ) -----
  $$('form[data-mailto]').forEach(function (form) {
    var status = form.querySelector('[data-form-status]');
    function setErr(field, msg) {
      var box = document.getElementById('e-' + field);
      var input = form.elements[field];
      if (box) box.textContent = msg || '';
      if (input && input.setAttribute && !input.length) {
        if (msg) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid');
      }
      return !msg;
    }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var name = (form.elements.name.value || '').trim();
      var email = (form.elements.email.value || '').trim();
      var msg = (form.elements.message.value || '').trim();
      var topic = form.querySelector('input[name="topic"]:checked');
      var ok = [
        setErr('name', name.length < 2 ? 'Vui lòng nhập họ tên.' : ''),
        setErr('email', /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email) ? '' : 'Email chưa đúng, ví dụ: ten@email.com'),
        setErr('topic', topic ? '' : 'Vui lòng chọn một chủ đề.'),
        setErr('message', msg.length < 20 ? 'Lời nhắn cần ít nhất 20 ký tự.' : '')
      ];
      var firstBad = ['name', 'email', 'topic', 'message'][ok.indexOf(false)];
      if (firstBad) {
        var el = firstBad === 'topic' ? form.querySelector('input[name="topic"]') : form.elements[firstBad];
        if (el) el.focus();
        return;
      }
      var label = topic.getAttribute('data-label') || topic.value;
      var subject = '[' + label + '] ' + name;
      var body = 'Họ tên: ' + name + '\nEmail: ' + email + '\nChủ đề: ' + label + '\n\n' + msg;
      window.location.href = 'mailto:' + form.getAttribute('data-mailto') + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
      if (status) { status.hidden = false; status.focus(); }
    });
  });

  // ----- Sao chép link -----
  $$('[data-copy-link]').forEach(function (b) {
    b.addEventListener('click', function () {
      var url = b.getAttribute('data-copy-link') || location.href;
      var label = b.getAttribute('aria-label');
      var done = function () { b.setAttribute('aria-label', 'Đã sao chép liên kết'); setTimeout(function () { b.setAttribute('aria-label', label); }, 2000); };
      if (navigator.clipboard) navigator.clipboard.writeText(url).then(done, function () {});
    });
  });

  // ----- Thống kê lượt xem: 1 beacon/lượt xem, không cookie (StatsController) -----
  var stats = document.querySelector('meta[name="tt-stats"]');
  if (stats && !navigator.webdriver) {
    var sent = false;
    var send = function () {
      if (sent || document.prerendering || document.visibilityState !== 'visible') return;
      sent = true;
      // URL chuẩn (canonical) để /kich-ban, /tu-sach, ?trang=2 gộp về đúng trang
      var c = document.querySelector('link[rel="canonical"]');
      var p = location.pathname;
      try { if (c) p = new URL(c.href).pathname; } catch (e) {}
      var body = JSON.stringify({ p: p, r: document.referrer, f: /[?&]fbclid=/.test(location.search) ? 1 : 0 });
      var url = stats.getAttribute('content');
      if (!(navigator.sendBeacon && navigator.sendBeacon(url, body)) && window.fetch) {
        fetch(url, { method: 'POST', body: body, keepalive: true, credentials: 'omit' }).catch(function () {});
      }
    };
    send();
    document.addEventListener('visibilitychange', send);
    document.addEventListener('prerenderingchange', send);
  }
})();

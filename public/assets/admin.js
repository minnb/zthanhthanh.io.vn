(function () {
  'use strict';
  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  var CSRF = csrfMeta ? csrfMeta.getAttribute('content') : '';

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function postJson(url, data) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify(data)
    }).then(function (r) {
      return r.json().catch(function () { return { ok: false, error: 'Phản hồi không hợp lệ (' + r.status + ').' }; })
        .then(function (j) { j.status = r.status; return j; });
    });
  }

  // ---------- Xác nhận trước khi xóa (admin.md) ----------
  $$('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // ---------- Toast tự ẩn ----------
  $$('.toast').forEach(function (t) {
    setTimeout(function () { t.classList.add('is-hidden'); }, 6000);
    setTimeout(function () { t.remove(); }, 6500);
  });

  // ---------- Bộ đếm ký tự (tiêu đề ≤ 60, mô tả ≤ 155) ----------
  function bindCounter(counter) {
    var input = document.getElementById(counter.getAttribute('data-counter-for'));
    var max = parseInt(counter.getAttribute('data-max'), 10);
    if (!input) return;
    function update() {
      var n = Array.from(input.value).length;
      counter.textContent = n + '/' + max;
      counter.classList.toggle('is-bad', n > max);
      counter.classList.toggle('is-warn', n <= max && n > max * 0.9);
    }
    input.addEventListener('input', update);
    update();
  }
  $$('[data-counter-for]').forEach(bindCounter);

  // ---------- Slug tiếng Việt (giống Core/Slug) ----------
  function slugify(text) {
    return text.toLowerCase().replace(/đ/g, 'd').normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 150);
  }

  // ---------- Điểm tâm ảnh: cắt theo khung (giống hệt Image::cropRect/framePosition bên PHP) ----------
  var SHARE_RATIO = 1200 / 630;
  function pct(v) { return (Math.round(Math.min(100, Math.max(0, v)) * 10) / 10) + '%'; }
  // Ảnh W×H, điểm tâm fx/fy (0..1), khung tỉ lệ ratio -> object-position giữ điểm tâm ở giữa khung, kẹp trong ảnh
  function framePosition(w, h, fx, fy, ratio) {
    if (!(w > 0 && h > 0 && ratio > 0)) return '50% 50%';
    fx = Math.min(1, Math.max(0, fx)); fy = Math.min(1, Math.max(0, fy));
    if (w / h > ratio) {
      var cw = h * ratio;
      var x = Math.min(Math.max(fx * w - cw / 2, 0), w - cw);
      return (w - cw > 0.5 ? pct(x / (w - cw) * 100) : '50%') + ' 50%';
    }
    var ch = w / ratio;
    var y = Math.min(Math.max(fy * h - ch / 2, 0), h - ch);
    return '50% ' + (h - ch > 0.5 ? pct(y / (h - ch) * 100) : '50%');
  }

  // Thư viện ảnh: bấm/chạm lên ảnh hoặc phím mũi tên để chọn điểm tâm; 3 khung xem trước cập nhật ngay
  $$('[data-focus-card]').forEach(function (card) {
    var picker = $('[data-focus-picker]', card);
    var dot = $('[data-focus-dot]', card);
    var inX = $('[data-focus-x]', card);
    var inY = $('[data-focus-y]', card);
    var status = $('[data-focus-status]', card);
    var w = parseInt(card.getAttribute('data-w'), 10);
    var h = parseInt(card.getAttribute('data-h'), 10);
    if (!picker || !dot || !inX || !inY) return;
    function set(fx, fy) {
      fx = Math.round(Math.min(100, Math.max(0, fx)));
      fy = Math.round(Math.min(100, Math.max(0, fy)));
      inX.value = fx; inY.value = fy;
      dot.style.left = fx + '%'; dot.style.top = fy + '%';
      $$('[data-frame-ratio]', card).forEach(function (img) {
        img.style.objectPosition = framePosition(w, h, fx / 100, fy / 100, parseFloat(img.getAttribute('data-frame-ratio')));
      });
      if (status) status.textContent = 'Điểm tâm: ' + fx + '% ngang, ' + fy + '% dọc – bấm Lưu để áp dụng';
    }
    picker.addEventListener('click', function (e) {
      var img = $('img', picker);
      var r = img.getBoundingClientRect();
      if (r.width < 1 || r.height < 1) return;
      set((e.clientX - r.left) / r.width * 100, (e.clientY - r.top) / r.height * 100);
    });
    picker.addEventListener('keydown', function (e) {
      var step = e.shiftKey ? 1 : 5;
      var d = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] }[e.key];
      if (!d) return;
      e.preventDefault();
      set(parseInt(inX.value, 10) + d[0], parseInt(inY.value, 10) + d[1]);
    });
  });

  // ---------- Hộp chọn ảnh ----------
  var picker = document.getElementById('media-picker');
  var pickerCallback = null;

  function renderPicker(items) {
    var grid = $('[data-picker-grid]', picker);
    grid.innerHTML = '';
    $('[data-picker-empty]', picker).hidden = items.length > 0;
    items.forEach(function (item) {
      var li = document.createElement('li');
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.setAttribute('aria-label', 'Chọn ảnh: ' + (item.alt || 'chưa có mô tả'));
      var img = document.createElement('img');
      img.src = item.thumb; img.alt = ''; img.loading = 'lazy'; img.width = 120; img.height = 100;
      btn.appendChild(img);
      btn.addEventListener('click', function () {
        if (pickerCallback) pickerCallback(item);
        picker.close();
      });
      li.appendChild(btn);
      grid.appendChild(li);
    });
  }

  function openPicker(callback) {
    if (!picker) return;
    pickerCallback = callback;
    $('[data-picker-status]', picker).textContent = 'Đang tải…';
    picker.showModal();
    fetch(picker.getAttribute('data-list-url'), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        $('[data-picker-status]', picker).textContent = j.ok ? '' : (j.error || 'Không tải được danh sách ảnh.');
        if (j.ok) renderPicker(j.data.items);
      })
      .catch(function () { $('[data-picker-status]', picker).textContent = 'Mất kết nối.'; });
  }

  if (picker) {
    $('[data-picker-close]', picker).addEventListener('click', function () { picker.close(); });
    $('[data-picker-upload]', picker).addEventListener('change', function (e) {
      var file = e.target.files && e.target.files[0];
      if (!file) return;
      var status = $('[data-picker-status]', picker);
      status.textContent = 'Đang tải lên…';
      var fd = new FormData();
      fd.append('file', file);
      fd.append('_csrf', CSRF);
      fetch(picker.getAttribute('data-upload-url'), { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          e.target.value = '';
          if (!j.ok) { status.textContent = j.error || 'Không tải lên được.'; return; }
          status.textContent = 'Đã tải lên. Chọn ảnh bên dưới.';
          openPicker(pickerCallback);
        })
        .catch(function () { status.textContent = 'Mất kết nối khi tải lên.'; });
    });
  }

  // Khối chọn ảnh bìa/poster: [data-cover] chứa [data-cover-id], [data-cover-img], [data-pick-cover], [data-clear-cover]
  $$('[data-cover]').forEach(function (box) {
    var idInput = $('[data-cover-id]', box);
    var img = $('[data-cover-img]', box);
    var clearBtn = $('[data-clear-cover]', box);
    var altInput = document.getElementById('cover_alt');
    $('[data-pick-cover]', box).addEventListener('click', function () {
      openPicker(function (item) {
        idInput.value = item.id;
        img.src = item.thumb; img.hidden = false;
        // Xem trước đúng khung trên web (ảnh bìa 16:9 / poster 2.39) + thẻ Facebook, theo điểm tâm của ảnh
        var fx = (item.focus_x == null ? 50 : item.focus_x) / 100;
        var fy = (item.focus_y == null ? 50 : item.focus_y) / 100;
        img.style.objectPosition = framePosition(item.width, item.height, fx, fy, parseFloat(img.getAttribute('data-frame-ratio')));
        img.setAttribute('data-share-pos', framePosition(item.width, item.height, fx, fy, SHARE_RATIO));
        clearBtn.hidden = false;
        if (altInput && !altInput.value) altInput.value = item.alt;
        box.dispatchEvent(new Event('input', { bubbles: true }));
      });
    });
    clearBtn.addEventListener('click', function () {
      idInput.value = ''; img.hidden = true; img.removeAttribute('src'); clearBtn.hidden = true;
      box.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });

  // ---------- Trình soạn bài ----------
  var form = $('form[data-editor]');
  if (!form) return;

  var body = $('#body', form);
  var title = $('#title', form);
  var slug = $('#slug', form);
  var preview = $('#body-preview', form);
  var saveState = $('[data-save-state]');
  var postId = form.getAttribute('data-post-id');
  var backupKey = 'tt-post-' + postId;
  var dirty = false;
  var submitting = false;
  var slugTouched = slug.value !== '' && slug.value.indexOf('nhap-') !== 0;

  function words() {
    var n = body.value.trim() ? body.value.trim().split(/\s+/).length : 0;
    $('[data-word-count]').textContent = n;
    return n;
  }

  // Thanh công cụ Markdown
  function wrap(before, after, placeholder) {
    var s = body.selectionStart, e = body.selectionEnd;
    var selected = body.value.slice(s, e) || placeholder;
    body.setRangeText(before + selected + after, s, e, 'end');
    body.focus();
    body.dispatchEvent(new Event('input', { bubbles: true }));
  }
  function linePrefix(prefix) {
    var s = body.selectionStart;
    var lineStart = body.value.lastIndexOf('\n', s - 1) + 1;
    body.setRangeText(prefix, lineStart, lineStart, 'end');
    body.focus();
    body.dispatchEvent(new Event('input', { bubbles: true }));
  }
  $$('[data-md]', form).forEach(function (btn) {
    btn.addEventListener('click', function () {
      switch (btn.getAttribute('data-md')) {
        case 'bold': wrap('**', '**', 'chữ đậm'); break;
        case 'italic': wrap('*', '*', 'chữ nghiêng'); break;
        case 'h2': linePrefix('## '); break;
        case 'quote': linePrefix('> '); break;
        case 'list': linePrefix('- '); break;
        case 'link':
          var href = window.prompt('Dán link (vd /phim/doi hoặc https://…):', '/');
          if (href) wrap('[', '](' + href + ')', 'chữ hiển thị');
          break;
        case 'image':
          openPicker(function (item) {
            var pos = body.selectionStart;
            // Escape [ ] trong alt để không làm hỏng cú pháp ảnh Markdown
            var alt = (item.alt || '').replace(/[\[\]]/g, '\\$&');
            body.setRangeText('\n\n![' + alt + '](' + item.url + ')\n\n', pos, pos, 'end');
            body.focus();
            body.dispatchEvent(new Event('input', { bubbles: true }));
          });
          break;
      }
    });
  });

  // Viết / Xem trước (render bằng Markdown của máy chủ – giống hệt trang thật)
  $$('[data-tab]', form).forEach(function (tab) {
    tab.addEventListener('click', function () {
      var showPreview = tab.getAttribute('data-tab') === 'preview';
      $$('[data-tab]', form).forEach(function (t) { t.setAttribute('aria-selected', String(t === tab)); });
      body.hidden = showPreview;
      preview.hidden = !showPreview;
      if (showPreview) {
        preview.innerHTML = '<p class="muted">Đang tạo bản xem trước…</p>';
        postJson(form.getAttribute('data-preview-url'), { body: body.value }).then(function (j) {
          if (j.ok) {
            // html do máy chủ render qua Parsedown safeMode (đã escape HTML thô)
            preview.innerHTML = j.data.html;
          } else {
            // Thông báo lỗi luôn gán bằng textContent (không bao giờ làm HTML)
            var err = document.createElement('p');
            err.className = 'field__error';
            err.textContent = j.error || 'Lỗi xem trước';
            preview.replaceChildren(err);
          }
        });
      }
    });
  });

  // Xem trước Google / Facebook + checklist SEO
  function selectedCategorySlug() {
    var opt = $('#category_id', form).selectedOptions[0];
    return opt && opt.getAttribute('data-slug') ? opt.getAttribute('data-slug') : '';
  }
  function plainText(md) {
    return md.replace(/!\[[^\]]*\]\([^)]*\)/g, '').replace(/\[([^\]]*)\]\([^)]*\)/g, '$1').replace(/[#>*_`-]+/g, ' ').replace(/\s+/g, ' ').trim();
  }
  function cut(text, max) {
    var chars = Array.from(text);
    if (chars.length <= max) return text;
    var s = chars.slice(0, max - 1).join('');
    var sp = s.lastIndexOf(' ');
    return (sp > max / 2 ? s.slice(0, sp) : s).replace(/[\s,.;:–-]+$/, '') + '…';
  }
  function refreshSeo() {
    var siteName = form.getAttribute('data-site-name');
    var seoTitle = $('#seo_title', form).value.trim() || title.value.trim() || 'Bài mới';
    var full = seoTitle + ' – ' + siteName;
    var shownTitle = Array.from(full).length <= 60 ? full : cut(seoTitle, 60);
    var desc = $('#seo_desc', form).value.trim() || $('#excerpt', form).value.trim() || plainText(body.value);
    var cat = selectedCategorySlug();
    $('[data-slug-prefix]', form).textContent = '/' + (cat || '…') + '/';
    $('[data-serp-path]', form).textContent = (cat || '…') + ' › ' + (slug.value || '…');
    $('[data-serp-title]', form).textContent = shownTitle;
    $('[data-serp-desc]', form).textContent = cut(desc, 155);
    $('[data-fb-title]', form).textContent = seoTitle;
    var coverImg = $('[data-cover-img]', form);
    var fbImg = $('[data-fb-img]', form);
    var hasCover = coverImg && !coverImg.hidden && coverImg.src;
    fbImg.style.backgroundImage = hasCover ? 'url("' + coverImg.src + '")' : '';
    // background-position % tính giống object-position -> thẻ Facebook xem trước cắt đúng như ảnh -og.jpg
    fbImg.style.backgroundPosition = hasCover ? (coverImg.getAttribute('data-share-pos') || '50% 50%') : '';

    var n = words();
    var base = form.getAttribute('data-base-url');
    var hasInternal = /\]\((\/[^)\s]*|https?:\/\/[^)\s]*)\)/.test(body.value) &&
      (/\]\(\/(?!uploads\/)[^)\s]*\)/.test(body.value) || body.value.indexOf('](' + base) !== -1);
    var coverId = $('[data-cover-id]', form).value;
    var alt = $('#cover_alt', form).value.trim();
    var descLen = Array.from($('#seo_desc', form).value.trim() || $('#excerpt', form).value.trim()).length;
    var checks = [
      [Array.from(seoTitle).length <= 60, 'Tiêu đề SEO ≤ 60 ký tự'],
      [descLen >= 50 && descLen <= 155, 'Có mô tả SEO hoặc tóm tắt dài 50–155 ký tự'],
      [!!cat, 'Đã chọn danh mục'],
      [!!coverId, 'Có ảnh bìa'],
      [!coverId || !!alt, 'Ảnh bìa có mô tả (alt)'],
      [n >= 300, 'Bài ≥ 300 từ (hiện ' + n + ')'],
      [/^##\s/m.test(body.value), 'Có ít nhất 1 tiêu đề mục (##)'],
      [hasInternal, 'Có ít nhất 1 link nội bộ (vd tới /phim/doi)']
    ];
    var list = $('[data-checklist]', form);
    list.innerHTML = '';
    checks.forEach(function (c) {
      var li = document.createElement('li');
      li.textContent = c[1];
      if (!c[0]) li.className = 'is-bad';
      list.appendChild(li);
    });
  }

  // Tự lưu 30 giây + bản dự phòng trên máy (localStorage)
  function setState(text) { if (saveState) saveState.textContent = text; }
  function backup() {
    try { localStorage.setItem(backupKey, JSON.stringify({ title: title.value, body: body.value, t: Date.now() })); } catch (e) {}
  }
  function clearBackup() { try { localStorage.removeItem(backupKey); } catch (e) {} }
  function autosave() {
    if (!dirty || submitting) return;
    var sentTitle = title.value, sentBody = body.value;
    setState('Đang lưu…');
    postJson(form.getAttribute('data-autosave-url'), { title: sentTitle, body_md: sentBody }).then(function (j) {
      if (j.ok) {
        if (title.value === sentTitle && body.value === sentBody) { dirty = false; clearBackup(); }
        // Bài đã xuất bản: chỉ là bản nháp, web vẫn giữ bản cũ tới khi bấm "Cập nhật"
        setState(j.data.mode === 'draft'
          ? 'Đã lưu bản nháp lúc ' + j.data.savedAt + ' – chưa đăng, bấm Cập nhật để đăng'
          : 'Đã tự lưu lúc ' + j.data.savedAt);
      } else {
        setState(j.error || 'Chưa tự lưu được');
      }
    }).catch(function () { setState('Mất kết nối – nội dung vẫn giữ trên máy'); });
  }

  // Có bản dự phòng khác nội dung trên máy chủ -> đề nghị khôi phục
  try {
    var saved = JSON.parse(localStorage.getItem(backupKey) || 'null');
    if (saved && (saved.body !== body.value || saved.title !== title.value)) {
      var note = document.createElement('div');
      note.className = 'notice';
      note.innerHTML = 'Có bản nháp lưu trên máy này chưa kịp lưu lên máy chủ. ';
      var restore = document.createElement('button');
      restore.type = 'button'; restore.className = 'btn btn--outline btn--sm'; restore.textContent = 'Khôi phục bản trên máy';
      var discard = document.createElement('button');
      discard.type = 'button'; discard.className = 'btn btn--ghost btn--sm'; discard.textContent = 'Bỏ qua';
      restore.addEventListener('click', function () {
        title.value = saved.title; body.value = saved.body; dirty = true; refreshSeo(); note.remove();
      });
      discard.addEventListener('click', function () { clearBackup(); note.remove(); });
      note.appendChild(restore); note.appendChild(discard);
      form.parentNode.insertBefore(note, form);
    }
  } catch (e) {}

  // Bài đã xuất bản có bản nháp tự lưu trên máy chủ -> nút mở bản nháp để sửa tiếp
  var restoreDraft = $('[data-restore-autosave]');
  if (restoreDraft) {
    restoreDraft.addEventListener('click', function () {
      var box = restoreDraft.closest('[data-autosave-draft]');
      title.value = $('[data-draft-title]', box).value;
      body.value = $('[data-draft-body]', box).value;
      dirty = true;
      setState('Đang sửa bản nháp – bấm Cập nhật để đăng');
      refreshSeo();
      box.remove();
    });
  }

  form.addEventListener('input', function (e) {
    if (e.target === slug) slugTouched = true;
    if (e.target === title && !slugTouched) slug.value = slugify(title.value);
    if (e.target === title || e.target === body) { dirty = true; backup(); setState('Chưa lưu'); }
    refreshSeo();
  });
  form.addEventListener('change', refreshSeo);
  form.addEventListener('submit', function () { submitting = true; clearBackup(); });
  window.addEventListener('beforeunload', function (e) {
    if (dirty && !submitting) { e.preventDefault(); e.returnValue = ''; }
  });
  setInterval(autosave, 30000);
  refreshSeo();
})();

'use strict';

(function () {
  var html = document.documentElement;

  // Theme ------------------------------------------------------------------
  var toggle = document.querySelector('[data-theme-toggle]');
  if (toggle) {
    toggle.addEventListener('click', function () {
      var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      try { localStorage.setItem('automate-theme', next); } catch (e) {}
    });
  }

  // Mobile sidebar -----------------------------------------------------------
  var sidebar = document.querySelector('[data-sidebar]');
  var backdrop = document.querySelector('.sidebar__backdrop');
  if (sidebar) {
    var setOpen = function (open) {
      sidebar.classList.toggle('is-open', open);
      document.body.classList.toggle('sidebar-open', open);
      if (backdrop) backdrop.hidden = !open;
      document.querySelectorAll('.header__burger').forEach(function (b) { b.setAttribute('aria-expanded', String(open)); });
    };
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (el) {
      el.addEventListener('click', function () { setOpen(!sidebar.classList.contains('is-open')); });
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
  }

  // Copy buttons -------------------------------------------------------------
  var copyIcon = '<svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
  var checkIcon = '<svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>';

  var copyText = function (text, button) {
    var done = function () {
      button.classList.add('is-copied');
      button.innerHTML = checkIcon;
      setTimeout(function () { button.classList.remove('is-copied'); button.innerHTML = copyIcon; }, 1600);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, function () {});
    } else {
      var ta = document.createElement('textarea');
      ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'absolute'; ta.style.left = '-9999px';
      document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); done(); } catch (e) {}
      document.body.removeChild(ta);
    }
  };

  document.querySelectorAll('[data-copy]').forEach(function (button) {
    button.addEventListener('click', function () { copyText(button.getAttribute('data-copy'), button); });
  });

  document.querySelectorAll('.prose pre').forEach(function (pre) {
    var code = pre.querySelector('code');
    var wrapper = document.createElement('div');
    wrapper.className = 'code-block';
    pre.parentNode.insertBefore(wrapper, pre);
    wrapper.appendChild(pre);

    var lang = code && (code.className.match(/language-([a-z0-9]+)/) || [])[1];
    if (lang && lang !== 'plaintext') {
      var label = document.createElement('span');
      label.className = 'code-block__lang';
      label.textContent = lang;
      wrapper.appendChild(label);
    }

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'code-block__copy';
    button.setAttribute('aria-label', 'Copy code');
    button.innerHTML = copyIcon;
    button.addEventListener('click', function () { copyText((code || pre).innerText.replace(/\n$/, ''), button); });
    wrapper.appendChild(button);
  });

  // Responsive tables --------------------------------------------------------
  document.querySelectorAll('.prose table').forEach(function (table) {
    var wrap = document.createElement('div');
    wrap.className = 'table-wrap';
    table.parentNode.insertBefore(wrap, table);
    wrap.appendChild(table);
  });

  // Table of contents highlight ---------------------------------------------
  var toc = document.querySelector('[data-toc]');
  if (toc && 'IntersectionObserver' in window) {
    var links = Array.prototype.slice.call(toc.querySelectorAll('a[href^="#"]'));
    var byId = {};
    links.forEach(function (a) { byId[decodeURIComponent(a.getAttribute('href').slice(1))] = a; });
    var headings = Object.keys(byId).map(function (id) { return document.getElementById(id); }).filter(Boolean);
    var current = null;
    var setActive = function (id) {
      if (current === id) return;
      current = id;
      links.forEach(function (a) { a.classList.remove('is-active'); });
      if (byId[id]) byId[id].classList.add('is-active');
    };
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) { if (entry.isIntersecting) setActive(entry.target.id); });
    }, { rootMargin: '-80px 0px -70% 0px', threshold: 0 });
    headings.forEach(function (h) { observer.observe(h); });
    if (headings[0]) setActive(headings[0].id);
  }

  // Search (Pagefind, generated after the build) -----------------------------
  var search = document.querySelector('[data-pagefind-ui]');
  if (search && window.PagefindUI) {
    new window.PagefindUI({ element: search, showSubResults: true, showImages: false, resetStyles: false, translations: { placeholder: 'Search the docs' } });
  }
})();

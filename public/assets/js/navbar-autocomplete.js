/* ===== Navbar Autocomplete ===== */
(function () {
    'use strict';

    const input = document.getElementById('navSearchInput');
    const list  = document.getElementById('navAcList');
    const form  = document.getElementById('navSearchForm');
    if (!input) return;
    let idx = -1;

    function debounce(fn, ms) {
        let t;
        return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
    }

    function getItems() { return [...list.querySelectorAll('li')]; }
    function hide()     { list.classList.remove('open'); idx = -1; }
    function show()     { list.classList.add('open'); }
    function esc(s)     { return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

    function mark(arr, i) {
        arr.forEach((li, j) => li.classList.toggle('nac-on', j === i));
        arr[i]?.scrollIntoView({ block: 'nearest' });
    }

    function render(movies) {
        list.innerHTML = '';
        if (!movies.length) { hide(); return; }
        movies.forEach(m => {
            const li = document.createElement('li');
            li.innerHTML =
                (m.poster_path
                    ? `<img src="${m.poster_path}" alt="" class="nac-thumb">`
                    : `<div class="nac-thumb"></div>`)
                + `<div><p class="nac-title">${esc(m.title)}</p>`
                + `<p class="nac-year">${m.release_year ?? ''}</p></div>`;
            li.addEventListener('mousedown', e => {
                e.preventDefault();
                window.location.href = `/public/movie.php?id=${m.id}`;
            });
            list.appendChild(li);
        });
        show();
        idx = -1;
    }

    const suggest = debounce(async q => {
        if (q.length < 2) { hide(); return; }
        try {
            const data = await fetch(`/public/api/search_suggest.php?q=${encodeURIComponent(q)}`).then(r => r.json());
            render(Array.isArray(data) ? data : []);
        } catch (_) { hide(); }
    }, 300);

    input.addEventListener('input',   e => suggest(e.target.value.trim()));
    input.addEventListener('focus',   () => { if (input.value.trim().length >= 2 && list.children.length) show(); });
    input.addEventListener('blur',    () => setTimeout(hide, 150));
    input.addEventListener('keydown', e => {
        const arr = getItems();
        if (!arr.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            idx = (idx + 1) % arr.length;
            mark(arr, idx);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            idx = (idx - 1 + arr.length) % arr.length;
            mark(arr, idx);
        } else if (e.key === 'Enter' && idx >= 0) {
            e.preventDefault();
            arr[idx]?.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
        } else if (e.key === 'Escape') {
            hide();
        }
    });
    form.addEventListener('submit', hide);
}());

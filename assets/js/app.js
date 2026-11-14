/* RusStart front-end behaviour. No libraries. */
(function () {
    'use strict';

    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const el = (tag, attrs = {}, text) => {
        const node = document.createElement(tag);
        Object.entries(attrs).forEach(([k, v]) => node.setAttribute(k, v));
        if (text !== undefined) node.textContent = text;
        return node;
    };
    let uid = 0;
    const TEXT = window.I18N || {};

    /* ---------- header ---------- */

    const header = $('[data-header]');
    if (header) {
        const toggle = $('[data-nav-toggle]');
        const nav = $('[data-nav]');
        let lastY = window.scrollY;

        // hide when scrolling down, show again as soon as the page moves up
        const onScroll = () => {
            const y = window.scrollY;
            header.classList.toggle('scrolled', y > 12);
            const navOpen = nav && nav.classList.contains('open');
            if (!navOpen && y > lastY + 4 && y > 160) {
                header.classList.add('is-hidden');
                $$('.menu.open', header).forEach((m) => m.classList.remove('open'));
            } else if (y < lastY - 4 || y <= 160) {
                header.classList.remove('is-hidden');
            }
            lastY = y;
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        header.addEventListener('focusin', () => header.classList.remove('is-hidden'));

        if (toggle && nav) {
            toggle.addEventListener('click', () => {
                const open = nav.classList.toggle('open');
                header.classList.toggle('nav-open', open);
                toggle.setAttribute('aria-expanded', String(open));
            });
        }
    }

    /* ---------- hero snowfall ---------- */

    const snow = $('[data-snow]');
    if (snow && snow.getContext) {
        const ctx = snow.getContext('2d');
        const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let w = 0, h = 0, flakes = [], running = false, last = 0;

        // one soft white dot, reused for every flake
        const dot = document.createElement('canvas');
        dot.width = dot.height = 32;
        const dctx = dot.getContext('2d');
        const grad = dctx.createRadialGradient(16, 16, 0, 16, 16, 16);
        grad.addColorStop(0, 'rgba(255,255,255,1)');
        grad.addColorStop(0.45, 'rgba(255,255,255,.75)');
        grad.addColorStop(1, 'rgba(255,255,255,0)');
        dctx.fillStyle = grad;
        dctx.fillRect(0, 0, 32, 32);

        const makeFlake = (anywhere) => {
            const depth = Math.random();                       // 0 = far, 1 = close to the viewer
            return {
                x: Math.random() * w,
                y: anywhere ? Math.random() * h : -20,
                size: 3 + depth * depth * 15,
                speed: 16 + depth * 52,                        // pixels per second: slow and calm
                sway: 8 + Math.random() * 22,
                phase: Math.random() * Math.PI * 2,
                alpha: 0.3 + depth * 0.6,
            };
        };

        const resize = () => {
            const ratio = Math.min(window.devicePixelRatio || 1, 2);
            w = snow.clientWidth; h = snow.clientHeight;
            snow.width = w * ratio; snow.height = h * ratio;
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            const count = Math.min(240, Math.round((w * h) / 7000));
            flakes = Array.from({ length: count }, () => makeFlake(true));
            if (still) draw(0);
        };

        const draw = (dt) => {
            ctx.clearRect(0, 0, w, h);
            flakes.forEach((f, i) => {
                f.y += f.speed * dt;
                f.phase += dt * 0.7;
                const x = f.x + Math.sin(f.phase) * f.sway;
                if (f.y > h + 20) flakes[i] = makeFlake(false);
                ctx.globalAlpha = f.alpha;
                ctx.drawImage(dot, x - f.size / 2, f.y - f.size / 2, f.size, f.size);
            });
        };

        const tick = (now) => {
            if (!running) return;
            const dt = Math.min((now - last) / 1000, 0.05);
            last = now;
            draw(dt);
            requestAnimationFrame(tick);
        };

        resize();
        window.addEventListener('resize', resize);
        if (!still) {
            // only animate while the hero is on screen
            new IntersectionObserver(([entry]) => {
                const visible = entry.isIntersecting;
                if (visible && !running) { running = true; last = performance.now(); requestAnimationFrame(tick); }
                else if (!visible) running = false;
            }).observe(snow);
        }
    }

    /* ---------- hero route: every city, scroll with arrows or by dragging ---------- */

    const routeWrap = $('[data-route]');
    if (routeWrap) {
        const route = $('.route', routeWrap);
        const arrows = $('.route-arrows', routeWrap);
        const prev = $('[data-route-prev]', routeWrap);
        const next = $('[data-route-next]', routeWrap);

        const update = () => {
            const can = route.scrollWidth > route.clientWidth + 4;
            routeWrap.classList.toggle('can-scroll', can);
            arrows.hidden = !can;
            prev.disabled = route.scrollLeft <= 2;
            next.disabled = route.scrollLeft + route.clientWidth >= route.scrollWidth - 2;
        };
        const step = (dir) => route.scrollBy({ left: dir * route.clientWidth * 0.7, behavior: 'smooth' });
        prev.addEventListener('click', () => step(-1));
        next.addEventListener('click', () => step(1));
        route.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(update);

        // drag with the mouse (touch screens scroll natively)
        let startX = 0, startLeft = 0, down = false, moved = false;
        route.addEventListener('mousedown', (e) => { down = true; moved = false; startX = e.pageX; startLeft = route.scrollLeft; });
        window.addEventListener('mousemove', (e) => {
            if (!down) return;
            const dx = e.pageX - startX;
            if (Math.abs(dx) > 5) { moved = true; route.classList.add('dragging'); }
            if (moved) route.scrollLeft = startLeft - dx;
        });
        window.addEventListener('mouseup', () => { down = false; setTimeout(() => route.classList.remove('dragging'), 0); });
        route.addEventListener('click', (e) => { if (moved) { e.preventDefault(); moved = false; } }, true);
    }

    /* ---------- account menu ---------- */

    $$('[data-menu]').forEach((menu) => {
        const btn = $('[data-menu-btn]', menu);
        const set = (open) => {
            menu.classList.toggle('open', open);
            btn.setAttribute('aria-expanded', String(open));
        };
        btn.addEventListener('click', () => set(!menu.classList.contains('open')));
        document.addEventListener('click', (e) => { if (!menu.contains(e.target)) set(false); });
        menu.addEventListener('keydown', (e) => { if (e.key === 'Escape') { set(false); btn.focus(); } });
    });

    /* ---------- slow reveal on scroll ---------- */

    const revealItems = $$('[data-reveal]');
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            // items that enter together slide in one after another
            entries.filter((en) => en.isIntersecting).forEach((en, i) => {
                en.target.style.setProperty('--d', `${Math.min(i, 6) * 110}ms`);
                en.target.classList.add('in');
                io.unobserve(en.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
        revealItems.forEach((item) => io.observe(item));
    } else {
        revealItems.forEach((item) => item.classList.add('in'));
    }

    /* ---------- custom dropdown ---------- */

    function enhanceSelect(wrap) {
        const select = $('select', wrap);
        if (!select) return;
        const id = `sel-${++uid}`;
        const options = Array.from(select.options);
        const otherBox = document.querySelector(`[data-other-for="${select.id}"]`);

        const btn = el('button', { type: 'button', class: 'select-btn', 'aria-haspopup': 'listbox', 'aria-expanded': 'false', 'aria-controls': id });
        const label = el('span', { class: 'select-label' }, wrap.dataset.label || '');
        const value = el('span', { class: 'select-value' });
        btn.append(label, value);

        const panel = el('div', { class: 'select-panel' });
        const realOptions = options.filter((o) => o.value !== '' || o.textContent.trim() !== '');
        let search = null;
        if (realOptions.length > 8) {
            search = el('input', { type: 'text', class: 'select-search', placeholder: TEXT.filter || 'Type to filter', 'aria-label': `${TEXT.filter || 'Filter'}: ${wrap.dataset.label || ''}`, autocomplete: 'off' });
            panel.append(search);
        }
        const list = el('ul', { class: 'select-list', role: 'listbox', id, tabindex: '-1' });
        const empty = el('p', { class: 'select-empty', hidden: '' }, TEXT.nothing || 'Nothing matches. Choose "Other" to add it.');
        panel.append(list, empty);

        const items = realOptions.map((opt, i) => {
            const li = el('li', { role: 'option', id: `${id}-${i}`, 'data-value': opt.value }, opt.textContent);
            if (opt.hasAttribute('data-other')) li.classList.add('is-other');
            list.append(li);
            return li;
        });

        wrap.append(btn, panel);
        wrap.classList.add('ready');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        let active = -1;
        const visible = () => items.filter((li) => !li.hidden);

        function sync() {
            const opt = select.options[select.selectedIndex];
            const has = !!opt && (opt.value !== '' || opt.textContent.trim() !== '');
            value.textContent = has ? opt.textContent : '';
            wrap.classList.toggle('has-value', has);
            items.forEach((li) => li.setAttribute('aria-selected', String(has && li.dataset.value === opt.value)));
            btn.setAttribute('aria-label', `${wrap.dataset.label || ''}: ${has ? opt.textContent : (TEXT.notChosen || 'not chosen')}`);
            if (otherBox) {
                const show = select.value === '__other';
                otherBox.hidden = !show;
                const input = $('input', otherBox);
                input.disabled = !show;
            }
        }

        function setActive(li) {
            items.forEach((x) => x.classList.remove('active'));
            active = li ? items.indexOf(li) : -1;
            if (li) {
                li.classList.add('active');
                li.scrollIntoView({ block: 'nearest' });
                (search || list).setAttribute('aria-activedescendant', li.id);
            }
        }

        function open() {
            if (wrap.classList.contains('open')) return;
            $$('.select.open').forEach((other) => other !== wrap && other.dispatchEvent(new Event('select:close')));
            wrap.classList.add('open');
            btn.setAttribute('aria-expanded', 'true');
            if (search) { search.value = ''; filter(''); }
            setActive(items.find((li) => li.getAttribute('aria-selected') === 'true') || visible()[0]);
            setTimeout(() => (search || list).focus({ preventScroll: true }), 60);
        }

        function close(focusBtn) {
            if (!wrap.classList.contains('open')) return;
            wrap.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
            if (focusBtn) btn.focus();
        }

        function choose(li) {
            select.value = li.dataset.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            sync();
            close(true);
            if (otherBox && select.value === '__other') {
                setTimeout(() => $('input', otherBox).focus(), 350);
            }
        }

        function filter(text) {
            const needle = text.trim().toLowerCase();
            let shown = 0;
            items.forEach((li) => {
                const match = li.classList.contains('is-other') || li.textContent.toLowerCase().includes(needle);
                li.hidden = !match;
                if (match && !li.classList.contains('is-other')) shown++;
            });
            empty.hidden = shown > 0 || !needle || !items.some((li) => li.classList.contains('is-other'));
            setActive(visible()[0]);
        }

        function onKey(e) {
            const vis = visible();
            const idx = vis.indexOf(items[active]);
            if (e.key === 'ArrowDown') { e.preventDefault(); setActive(vis[Math.min(idx + 1, vis.length - 1)]); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(vis[Math.max(idx - 1, 0)]); }
            else if (e.key === 'Home') { e.preventDefault(); setActive(vis[0]); }
            else if (e.key === 'End') { e.preventDefault(); setActive(vis[vis.length - 1]); }
            else if (e.key === 'Enter' || (e.key === ' ' && e.target !== search)) { e.preventDefault(); if (items[active] && !items[active].hidden) choose(items[active]); }
            else if (e.key === 'Escape') { e.preventDefault(); close(true); }
            else if (e.key === 'Tab') { close(false); }
        }

        btn.addEventListener('click', () => (wrap.classList.contains('open') ? close(true) : open()));
        btn.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); open(); }
        });
        list.addEventListener('click', (e) => { const li = e.target.closest('li'); if (li) choose(li); });
        list.addEventListener('mousemove', (e) => { const li = e.target.closest('li'); if (li && items[active] !== li) setActive(li); });
        list.addEventListener('keydown', onKey);
        if (search) {
            search.addEventListener('input', () => filter(search.value));
            search.addEventListener('keydown', onKey);
        }
        wrap.addEventListener('select:close', () => close(false));
        document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) close(false); });

        sync();
    }
    $$('[data-select]').forEach(enhanceSelect);

    /* ---------- text input with suggestions ---------- */

    function enhanceSuggest(wrap) {
        const input = $('input', wrap);
        let data = [];
        try { data = JSON.parse(wrap.dataset.suggest || '[]'); } catch (err) { return; }
        if (!input || !data.length) return;

        const id = `sug-${++uid}`;
        const panel = el('div', { class: 'suggest-panel' });
        const list = el('ul', { role: 'listbox', id });
        panel.append(list);
        wrap.append(panel);
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-controls', id);
        input.setAttribute('aria-expanded', 'false');

        let active = -1;
        let items = [];

        function close() {
            wrap.classList.remove('open');
            input.setAttribute('aria-expanded', 'false');
            active = -1;
        }

        function render() {
            const needle = input.value.trim().toLowerCase();
            const matches = data
                .filter((name) => name.toLowerCase().includes(needle))
                .sort((a, b) => a.toLowerCase().indexOf(needle) - b.toLowerCase().indexOf(needle))
                .slice(0, 8);
            list.textContent = '';
            items = matches.map((name, i) => {
                const li = el('li', { role: 'option', id: `${id}-${i}` }, name);
                list.append(li);
                return li;
            });
            active = -1;
            const exact = matches.length === 1 && matches[0].toLowerCase() === needle;
            if (matches.length && !exact) {
                wrap.classList.add('open');
                input.setAttribute('aria-expanded', 'true');
            } else {
                close();
            }
        }

        function setActive(i) {
            items.forEach((li) => li.classList.remove('active'));
            active = i;
            if (items[i]) {
                items[i].classList.add('active');
                items[i].scrollIntoView({ block: 'nearest' });
                input.setAttribute('aria-activedescendant', items[i].id);
            }
        }

        function choose(name) {
            input.value = name;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            close();
            input.focus();
        }

        input.addEventListener('focus', render);
        input.addEventListener('input', (e) => { if (e.isTrusted) render(); });
        input.addEventListener('keydown', (e) => {
            if (!wrap.classList.contains('open')) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); setActive(Math.min(active + 1, items.length - 1)); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(Math.max(active - 1, 0)); }
            else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); choose(items[active].textContent); }
            else if (e.key === 'Escape') { close(); }
        });
        list.addEventListener('mousedown', (e) => {
            const li = e.target.closest('li');
            if (li) { e.preventDefault(); choose(li.textContent); }
        });
        input.addEventListener('blur', () => setTimeout(close, 120));
    }
    $$('[data-suggest]').forEach(enhanceSuggest);

    /* ---------- small helpers ---------- */

    // show / hide password
    $$('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = $('input', btn.parentElement);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? (TEXT.hide || 'Hide') : (TEXT.show || 'Show');
            btn.setAttribute('aria-label', show ? (TEXT.hidePass || 'Hide password') : (TEXT.showPass || 'Show password'));
        });
    });

    // image upload preview
    $$('[data-upload]').forEach((box) => {
        const input = $('input[type="file"]', box);
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;
            $('[data-upload-name]', box).textContent = file.name;
            if (file.type.startsWith('image/')) {
                const preview = $('.upload-preview', box);
                preview.style.backgroundImage = `url(${URL.createObjectURL(file)})`;
                preview.textContent = '';
            }
        });
    });

    // confirm before deleting
    $$('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => { if (!window.confirm(form.dataset.confirm)) e.preventDefault(); });
    });

    /* ---------- password rules checklist ---------- */

    $$('[data-password-rules]').forEach((input) => {
        const list = input.closest('.field').nextElementSibling;
        if (!list || !list.matches('[data-pw-rules]')) return;
        const tests = {
            length: (v) => [...v].length >= 8,
            lower: (v) => /\p{Ll}/u.test(v),
            upper: (v) => /\p{Lu}/u.test(v),
            number: (v) => /\d/.test(v),
        };
        const update = () => {
            let all = true;
            $$('li', list).forEach((li) => {
                const ok = tests[li.dataset.rule](input.value);
                li.classList.toggle('is-met', ok);
                all = all && ok;
            });
            if (all) list.classList.remove('is-short');
            return all;
        };
        input.addEventListener('input', update);
        update();
        input.form.addEventListener('submit', (e) => {
            if (!update()) {
                e.preventDefault();
                list.classList.add('is-short');
                input.focus();
            }
        });
    });

    /* ---------- live username / email check on sign-up ---------- */

    // what each box accepts while typing: username = small letters and numbers, email = lowercase, no spaces
    const CLEAN = {
        code: (v) => v.replace(/\D/g, '').slice(0, 6),
        name: (v) => v.replace(/[^\p{L}\p{M}\s]/gu, '').replace(/\s{2,}/g, ' ').replace(/^\s+/, ''),
        username: (v) => v.toLowerCase().replace(/[^a-z0-9]/g, ''),
        email: (v) => v.toLowerCase().replace(/\s+/g, ''),
    };

    // replace the box's text, keeping the cursor where the person was typing
    const cleanInput = (input, clean) => {
        const before = input.value;
        const after = clean(before);
        if (after === before) return;
        const pos = input.selectionStart ?? before.length;
        input.value = after;
        const newPos = clean(before.slice(0, pos)).length;
        try { input.setSelectionRange(newPos, newPos); } catch (err) { /* email inputs do not support it */ }
    };

    // boxes that are only cleaned while typing (no server check)
    $$('[data-clean]').forEach((input) => {
        const clean = CLEAN[input.dataset.clean];
        if (!clean) return;
        input.addEventListener('input', () => cleanInput(input, clean));
        input.addEventListener('blur', () => { input.value = input.value.trim(); });
    });

    $$('form[data-check-url]').forEach((form) => {
        const endpoint = form.dataset.checkUrl;
        const results = {};                                   // field name -> true / false / null (not checked)

        $$('[data-check]', form).forEach((input) => {
            const kind = input.dataset.check;
            const field = input.closest('.field');
            const hint = $('.field-hint', field);
            // one fixed line under the box: it starts as the hint and is then replaced by the result,
            // so nothing below it moves while the person types
            let status = $('.field-status', field);
            if (!status) {
                status = el('p', { class: 'field-status', 'aria-live': 'polite', hidden: '' });
                (hint || $('.field-box', field)).after(status);
            }
            let timer = null;
            let controller = null;
            let lastValue = null;

            const show = (state, message) => {
                if (state === null) {                        // back to the plain hint
                    status.hidden = true;
                    if (hint) hint.hidden = false;
                    field.classList.remove('is-valid', 'has-error');
                    input.removeAttribute('aria-invalid');
                    return;
                }
                if (state === 'checking') {                  // keep the last message, only swap the icon
                    status.classList.add('is-checking');
                    if (status.hidden) {
                        status.textContent = TEXT.checking || 'Checking…';
                        status.hidden = false;
                        if (hint) hint.hidden = true;
                    }
                    return;
                }
                status.className = 'field-status is-' + state;
                status.textContent = message || '';
                status.hidden = false;
                if (hint) hint.hidden = true;
                field.classList.toggle('is-valid', state === 'ok');
                field.classList.toggle('has-error', state === 'bad');
                input.setAttribute('aria-invalid', state === 'bad' ? 'true' : 'false');
            };

            const run = () => {
                const value = CLEAN[kind] ? CLEAN[kind](input.value) : input.value.trim().toLowerCase();
                if (value === lastValue) return;
                lastValue = value;
                if (controller) controller.abort();
                if (value === '') { results[kind] = null; show(null); return; }

                show('checking');
                controller = new AbortController();
                fetch(`${endpoint}?field=${encodeURIComponent(kind)}&value=${encodeURIComponent(value)}`, {
                    signal: controller.signal, headers: { Accept: 'application/json' }, credentials: 'same-origin',
                })
                    .then((r) => (r.ok ? r.json() : null))
                    .then((data) => {
                        if (!data || data.ok === null) { results[kind] = null; show(null); return; }
                        results[kind] = data.ok;
                        show(data.ok ? 'ok' : 'bad', data.message);
                    })
                    .catch((err) => { if (err.name !== 'AbortError') { results[kind] = null; show(null); } });
            };

            input.addEventListener('input', () => {
                if (CLEAN[kind]) cleanInput(input, CLEAN[kind]);
                $$('.field-error', field).forEach((e) => e.remove());   // the server's old message no longer applies
                clearTimeout(timer);
                // an email is only checked once it looks complete, or when the person leaves the box
                const wait = kind === 'email' && !/^[^\s@]+@[^\s@]+\.[a-z]{2,}$/.test(input.value);
                if (!wait) timer = setTimeout(run, 450);
            });
            input.addEventListener('blur', () => { clearTimeout(timer); run(); });
            // a value already in the box (your own username on Profile) is only checked once it is changed
            lastValue = CLEAN[kind] ? CLEAN[kind](input.value) : input.value.trim().toLowerCase();
        });

        // do not send the form while a checked field is known to be taken or invalid
        form.addEventListener('submit', (e) => {
            const bad = $$('[data-check]', form).find((input) => results[input.dataset.check] === false);
            if (bad) { e.preventDefault(); bad.focus(); }
        });
    });

    // QR code for the authenticator app (library loaded only on that page)
    $$('[data-qr]').forEach((box) => {
        if (!window.QRCode) return;
        new window.QRCode(box, { text: box.dataset.qr, width: 168, height: 168, colorDark: '#143648', colorLight: '#FDFDFD', correctLevel: window.QRCode.CorrectLevel.M });
        box.removeAttribute('title');
    });

    // copy backup codes
    $$('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText(btn.dataset.copy).then(() => { btn.textContent = btn.dataset.copied; });
        });
    });

    // bring the first field with an error into view
    const firstError = $('.has-error');
    if (firstError) firstError.scrollIntoView({ block: 'nearest' });
})();

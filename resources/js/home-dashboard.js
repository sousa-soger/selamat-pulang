import { buildHomeAlerts, sortHomeAlerts } from './home-alerts.js';

const mykkpStatus = document.getElementById('homeMykkpStatus');
const dismissMykkp = document.getElementById('dismissMykkpFailure');
if (mykkpStatus && dismissMykkp) {
    const dismissalKey = `adapt360:mykkp-dismissed:${document.body.dataset.userId}:${mykkpStatus.dataset.review}`;
    const showUnverified = () => {
        mykkpStatus.classList.replace('is-rejected', 'is-not_submitted');
        mykkpStatus.querySelector('span').textContent = 'MyKKP Not verified';
        dismissMykkp.hidden = true;
    };
    try { if (localStorage.getItem(dismissalKey)) showUnverified(); } catch { /* Keep dismissal available without storage. */ }
    dismissMykkp.addEventListener('click', () => {
        showUnverified();
        try { localStorage.setItem(dismissalKey, '1'); } catch { /* Dismiss for this visit. */ }
    });
}

const panel = document.querySelector('.home-todo');
const sort = document.getElementById('homeTodoSort');
const storageKey = `adapt360:home-todo:${document.body.dataset.userId}:${document.body.dataset.organizationId || 'personal'}`;
let preferences = { mode: 'priority', order: [] };
try {
    const saved = JSON.parse(localStorage.getItem(storageKey));
    if (saved && ['priority', 'due', 'category', 'custom'].includes(saved.mode) && Array.isArray(saved.order)) preferences = saved;
} catch { /* Sorting remains available when browser storage is unavailable. */ }
let currentAlerts = [];
const persist = () => { try { localStorage.setItem(storageKey, JSON.stringify(preferences)); } catch { /* Keep the current session order. */ } };

function paintAlerts() {
    if (!panel) return;
    sort.value = preferences.mode;
    const list = document.getElementById('homeTodoList');
    const alerts = sortHomeAlerts(currentAlerts, preferences.mode, preferences.order);
    list.replaceChildren();
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    alerts.forEach((alert, index) => {
        const row = document.createElement('li');
        row.className = 'home-todo-row';
        row.dataset.priority = String(alert.priority);
        if (alert.due && alert.due <= today) row.classList.add(alert.due < today ? 'is-overdue' : 'is-today');
        row.dataset.alertId = alert.id;
        const link = document.createElement('a');
        link.href = alert.target === 'account' ? '/account' : `/${alert.target}`;
        if (alert.target !== 'account') link.dataset.section = alert.target;
        const category = document.createElement('small');
        category.className = 'home-todo-category';
        if (alert.priority < 8) {
            const prefix = document.createElement('span');
            prefix.className = 'home-todo-priority-label';
            prefix.textContent = 'Priority · ';
            category.append(prefix);
        }
        const categoryName = document.createElement('span');
        categoryName.textContent = alert.category;
        category.append(categoryName);
        const title = document.createElement('strong');
        title.textContent = alert.title;
        const detail = document.createElement('span');
        detail.textContent = alert.detail;
        link.append(category, title, detail);
        if (alert.due) {
            const due = document.createElement('time');
            due.dateTime = alert.due;
            due.textContent = `${alert.due < today ? 'Overdue · ' : alert.due === today ? 'Today · ' : 'Due · '}${alert.due}`;
            due.className = alert.due < today ? 'is-overdue' : alert.due === today ? 'is-today' : 'is-upcoming';
            link.append(due);
        }
        const controls = document.createElement('div');
        controls.className = 'home-todo-move';
        for (const [direction, label, symbol] of [[-1, 'up', '↑'], [1, 'down', '↓']]) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = symbol;
            button.setAttribute('aria-label', `Move ${alert.title} ${label}`);
            button.disabled = index + direction < 0 || index + direction >= alerts.length;
            button.addEventListener('click', () => {
                const ids = alerts.map(item => item.id);
                [ids[index], ids[index + direction]] = [ids[index + direction], ids[index]];
                preferences = { mode: 'custom', order: ids };
                persist();
                paintAlerts();
                const moved = [...list.children].find(child => child.dataset.alertId === alert.id);
                moved?.querySelectorAll('button')[direction === -1 ? 0 : 1]?.focus();
            });
            controls.append(button);
        }
        row.append(link, controls);
        list.append(row);
    });
    document.getElementById('homeTodoCount').textContent = `${alerts.length} alert${alerts.length === 1 ? '' : 's'}`;
    document.getElementById('homeTodoEmpty').hidden = alerts.length > 0;
}

sort?.addEventListener('change', () => { preferences.mode = sort.value; persist(); paintAlerts(); });

export function renderHomeTodos(state, excellenceGap) {
    if (!panel) return;
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    currentAlerts = buildHomeAlerts(state, {
        organization: Boolean(document.body.dataset.organizationId),
        organizationVerified: panel.dataset.orgVerified === 'true',
        userVerified: panel.dataset.userVerified === 'true',
        mykkpStatus: panel.dataset.mykkpStatus,
        excellenceGap,
    }, today);
    paintAlerts();
}

const deck = document.getElementById('homeDeck');
if (deck) {
    let active = 0;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const show = index => deck.scrollTo({ left: Math.max(0, Math.min(1, index)) * deck.clientWidth, behavior: reduceMotion.matches ? 'instant' : 'smooth' });
    document.querySelectorAll('[data-home-slide]').forEach(button => button.addEventListener('click', () => {
        const value = button.dataset.homeSlide;
        show(value === 'next' ? active + 1 : value === 'previous' ? active - 1 : Number(value));
    }));
    deck.addEventListener('scroll', () => {
        active = Math.round(deck.scrollLeft / deck.clientWidth);
        document.querySelectorAll('[data-home-slide]').forEach(button => {
            if (['0', '1'].includes(button.dataset.homeSlide)) button.setAttribute('aria-pressed', String(Number(button.dataset.homeSlide) === active));
            else button.disabled = button.dataset.homeSlide === 'previous' ? active === 0 : active === 1;
        });
        [...deck.children].forEach((slide, index) => { slide.inert = index !== active; });
    }, { passive: true });
    deck.lastElementChild.inert = true;
    document.querySelector('[data-home-slide="previous"]').disabled = true;
    deck.addEventListener('keydown', event => {
        if (event.target !== deck || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        event.preventDefault();
        show(active + (event.key === 'ArrowRight' ? 1 : -1));
    });
    let drag;
    deck.addEventListener('pointerdown', event => {
        if (event.pointerType !== 'mouse' || event.button !== 0 || event.target.closest('a, button, input, select, textarea, label')) return;
        drag = { x: event.clientX, scroll: deck.scrollLeft, moved: false };
    });
    deck.addEventListener('pointermove', event => {
        if (!drag) return;
        const distance = event.clientX - drag.x;
        if (Math.abs(distance) > 6 && !drag.moved) {
            drag.moved = true;
            deck.setPointerCapture(event.pointerId);
            deck.classList.add('is-dragging');
        }
        if (drag.moved) deck.scrollLeft = drag.scroll - distance;
    });
    const finishDrag = event => {
        if (!drag) return;
        const distance = event.clientX - drag.x;
        const index = Math.round(drag.scroll / deck.clientWidth);
        const moved = drag.moved;
        drag = null;
        deck.classList.remove('is-dragging');
        if (moved) show(index + (Math.abs(distance) > 60 ? (distance < 0 ? 1 : -1) : 0));
    };
    deck.addEventListener('pointerup', finishDrag);
    deck.addEventListener('pointercancel', finishDrag);
    deck.addEventListener('pointerleave', event => { if (drag && !drag.moved) finishDrag(event); });
    new ResizeObserver(() => deck.scrollTo({ left: active * deck.clientWidth, behavior: 'instant' })).observe(deck);
}

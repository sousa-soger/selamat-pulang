const navigation = document.querySelector('#sidebar nav');

if (navigation) {
    const requested = new WeakSet();

    navigation.addEventListener('click', event => {
        const summary = event.target.closest('summary');
        const section = summary?.parentElement;
        if (section?.matches('details.nav-section') && !section.open) {
            requested.add(section);
        }
    });

    navigation.querySelectorAll('details.nav-section').forEach(section => {
        section.addEventListener('toggle', () => {
            if (!requested.has(section)) return;
            requested.delete(section);
            if (!section.open) return;

            requestAnimationFrame(() => {
                if (!section.open) return;
                const viewport = navigation.getBoundingClientRect();
                const expanded = section.getBoundingClientRect();
                const padding = 12;
                const visibleTop = viewport.top + padding;
                const visibleBottom = viewport.top + navigation.clientHeight - padding;
                let adjustment = 0;

                if (expanded.height > visibleBottom - visibleTop || expanded.top < visibleTop) {
                    adjustment = expanded.top - visibleTop;
                } else if (expanded.bottom > visibleBottom) {
                    adjustment = expanded.bottom - visibleBottom;
                }

                if (adjustment) {
                    navigation.scrollTo({
                        top: navigation.scrollTop + adjustment,
                        behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
                    });
                }
            });
        });
    });
}

// Progressive enhancement: keep the native select as the submitted form value.
for (const select of document.querySelectorAll('#organizationMemberTable select')) {
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'auralis-select';
    trigger.setAttribute('aria-label', select.getAttribute('aria-label'));
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    const label = document.createElement('span');
    label.textContent = select.selectedOptions[0]?.textContent;
    trigger.append(label);
    const arrow = document.createElement('span');
    arrow.className = 'auralis-select-arrow';
    arrow.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
    trigger.append(arrow);
    const menu = document.createElement('div');
    menu.className = 'auralis-select-menu';
    menu.setAttribute('role', 'listbox');
    menu.setAttribute('aria-label', select.getAttribute('aria-label'));
    menu.id = `role-options-${Math.random().toString(36).slice(2)}`;
    trigger.setAttribute('aria-controls', menu.id);
    menu.hidden = true;
    const descriptions = {'Chairperson':'Leads the safety committee','Secretary':'Coordinates meetings and records','Employer representative':'Represents management','Employee representative':'Represents workers','Not assigned':'No committee appointment','Editor':'Can update organization records','View only':'Can view organization records'};
    function close(restore = false) { menu.hidden = true; trigger.setAttribute('aria-expanded', 'false'); if (restore) trigger.focus(); }
    const options = [...select.options].map(option => {
        const item = document.createElement('button');
        item.type = 'button'; item.className = 'auralis-select-option'; item.setAttribute('role', 'option'); item.setAttribute('aria-selected', String(option.selected));
        const text = document.createElement('span'), title = document.createElement('strong'), detail = document.createElement('small'), check = document.createElement('span');
        title.textContent = option.textContent; detail.textContent = descriptions[option.textContent] || ''; text.append(title, detail); check.textContent = option.selected ? '✓' : ''; check.setAttribute('aria-hidden', 'true'); item.append(text, check);
        item.addEventListener('click', () => { if(select.value === option.value) { close(true); return; } select.value = option.value; label.textContent = option.textContent; close(true); trigger.disabled = true; select.dispatchEvent(new Event('change', {bubbles:true})); });
        menu.append(item); return item;
    });
    function open() {
        document.dispatchEvent(new Event('auralis:close-selects'));
        const rect = trigger.getBoundingClientRect();
        menu.hidden = false; menu.style.width = `${Math.max(270, rect.width)}px`;
        menu.style.left = `${Math.max(8, Math.min(rect.left, innerWidth - menu.offsetWidth - 8))}px`;
        menu.style.top = `${rect.bottom + 8 + menu.offsetHeight > innerHeight ? Math.max(8, rect.top - menu.offsetHeight - 8) : rect.bottom + 8}px`;
        trigger.setAttribute('aria-expanded', 'true'); (options[select.selectedIndex] || options[0]).focus();
    }
    trigger.addEventListener('click', () => menu.hidden ? open() : close());
    trigger.addEventListener('keydown', event => { if(['ArrowDown','ArrowUp'].includes(event.key)) {event.preventDefault();open();} });
    menu.addEventListener('keydown', event => {
        const index = options.indexOf(document.activeElement);
        if(event.key === 'Escape') {event.preventDefault();close(true);}
        if(event.key === 'Tab') close();
        if(['ArrowDown','ArrowUp','Home','End'].includes(event.key)) {event.preventDefault();options[event.key === 'Home' ? 0 : event.key === 'End' ? options.length-1 : (index+(event.key === 'ArrowDown'?1:-1)+options.length)%options.length].focus();}
        if(event.key.length === 1) options.find(item=>item.textContent.toLowerCase().startsWith(event.key.toLowerCase()))?.focus();
    });
    document.addEventListener('pointerdown', event => {if(!menu.contains(event.target) && !trigger.contains(event.target)) close();});
    document.addEventListener('auralis:close-selects', () => close());
    window.addEventListener('resize', () => close());
    window.addEventListener('scroll', event => {if(!menu.contains(event.target)) close();}, true);
    select.hidden = true; select.after(trigger); document.body.append(menu);
}

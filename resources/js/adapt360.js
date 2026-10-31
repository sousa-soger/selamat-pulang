import { createIcons, icons } from 'lucide';
import '../css/adapt360.css';
import '../css/auralis.css';

const menu = document.querySelector('[data-menu]');
function closeMenu() { document.body.classList.remove('sidebar-open'); menu?.setAttribute('aria-expanded', 'false'); }
window.adaptNavigate = (id, push = true) => {
    const panel = document.getElementById(id);
    if (!panel?.classList.contains('adapt-screen')) return;
    document.querySelectorAll('.adapt-screen').forEach(section => section.hidden = section !== panel);
    document.querySelectorAll('.nav-link[data-section]').forEach(link => {
        if (link.dataset.section === id) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });
    document.getElementById('pageTitle').textContent = panel.getAttribute('aria-label');
    document.title = `${panel.getAttribute('aria-label')} | Adapt360`;
    if (push) history.pushState({screen: id}, '', id === 'dashboard' ? '/' : `/${id}`);
    closeMenu();
    window.scrollTo({top:0, behavior:'instant'});
};
document.addEventListener('click', event => {
    const link = event.target.closest('a[data-section]');
    if (link && !event.ctrlKey && !event.metaKey && !event.shiftKey) {
        event.preventDefault(); window.adaptNavigate(link.dataset.section);
    }
});
menu?.addEventListener('click', () => {
    const open = document.body.classList.toggle('sidebar-open');
    menu.setAttribute('aria-expanded', String(open));
});
document.querySelector('[data-menu-close]').addEventListener('click', closeMenu);
document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMenu(); });
window.addEventListener('popstate', () => window.adaptNavigate(location.pathname.slice(1) || 'dashboard', false));
document.querySelectorAll('dialog').forEach(dialog => dialog.addEventListener('click', event => {
    if (event.target === dialog) { const r = dialog.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close(); }
}));
createIcons({icons});
await import('./adapt360-reference.js');
window.makeQR();
window.adaptNavigate(document.body.dataset.screen, false);
const syncCompany = () => {
    document.getElementById('sidebarCompany').textContent = window.state.profile.company;
    document.getElementById('sidebarSite').textContent = window.state.profile.site;
};
syncCompany();
new MutationObserver(syncCompany).observe(document.getElementById('companyLine'), {childList:true});

// Keep assessment records connected to the legal register, actions and planner.
let assessmentKey;
window.openAssessment = (key, title) => {
    assessmentKey = key;
    const record = window.state.assessments?.[key] || {};
    document.getElementById('assessmentTitle').textContent = title;
    for (const [field, value] of Object.entries({Status:window.state.legalStatus[key] || 'Not assessed', Owner:record.owner || '', Report:record.report || '', Due:record.due || '', Notes:record.notes || ''})) {
        document.getElementById(`assessment${field}`).value = value;
    }
    window.openModal('assessmentModal');
};
document.getElementById('assessmentForm').addEventListener('submit', event => {
    event.preventDefault();
    const value = field => document.getElementById(`assessment${field}`).value.trim();
    const record = {owner:value('Owner'),report:value('Report'),due:value('Due'),notes:value('Notes')};
    window.state.assessments ||= {};
    window.state.assessments[assessmentKey] = record;
    window.state.legalStatus[assessmentKey] = value('Status');
    if (document.getElementById('assessmentAction').checked) {
        const action = {id:window.nextRecordId('ACT',window.state.actions),source:`HRA / ${assessmentKey}`,text:record.notes,owner:record.owner,due:record.due,status:'Open'};
        window.state.actions.unshift(action);
        window.createCalendarFromAction(action);
    }
    window.save(); window.renderAll(); window.closeModal('assessmentModal'); window.toast('Assessment review saved and linked records updated.');
});
const connectAssessments = () => {
    const keys = ['noise','chemical','ergo','iaq','prisma'];
    document.querySelectorAll('#hraCards > .card').forEach((card,index) => {
        const button = card.querySelector('button');
        button.removeAttribute('onclick');
        button.onclick = () => window.openAssessment(keys[index],card.querySelector('h4').textContent);
    });
};
connectAssessments();
new MutationObserver(connectAssessments).observe(document.getElementById('hraCards'), {childList:true});

// Give legacy prototype controls accessible names without changing their values.
document.querySelectorAll('.adapt-screen input,.adapt-screen select,.adapt-screen textarea').forEach((field,index) => {
    const label = field.previousElementSibling;
    if (label?.tagName === 'LABEL') {
        field.id ||= `adapt-field-${index}`; label.htmlFor = field.id;
    } else if (!field.id && !field.closest('label')) field.setAttribute('aria-label', field.placeholder || 'Record field');
});

// Validate essential fields before the prototype creates any linked records.
const requiredFields = {addWorker:['wName','wRole'],addEquipment:['eqName','eqArea','eqDate'],addChemical:['cName','cUse'],addTraining:['tWho','tWhat','tDue'],addAction:['aText','aOwner','aDue'],addEvent:['evDate','evItem','evOwner'],addMember:['mName'],addIncident:['iArea','iText'],addHirarc:['hActivity','hHazard','hControl'],addPtw:['pArea','pHir','pUntil'],addDrill:['dDate','dDesc'],analyseChange:['chgArea','chgDesc']};
for (const [name, ids] of Object.entries(requiredFields)) {
    const original = window[name];
    window[name] = (...args) => {
        for (const id of ids) { const field = document.getElementById(id); field.required = true; if (!field.value.trim() || !field.reportValidity()) { field.reportValidity(); field.focus(); return; } }
        return original(...args);
    };
}

import { createIcons, icons } from 'lucide';
import './sidebar.js';
import '../css/adapt360.css';
import '../css/auralis.css';

const menu = document.querySelector('[data-menu]');
function closeMenu() { document.body.classList.remove('sidebar-open'); menu?.setAttribute('aria-expanded', 'false'); }
window.adaptNavigate = (id, push = true) => {
    if(['mentor','personal'].includes(id)) id = 'learning';
    const controlTarget=['emergency','capa'].includes(id)?id:null;
    const calendarTarget = id === 'calendar';
    if (['workplace','committee','calendar','emergency','capa'].includes(id)) id = 'organization';
    const panel = document.getElementById(id);
    if (!panel?.classList.contains('adapt-screen')) return;
    document.querySelectorAll('.adapt-screen').forEach(section => section.hidden = section !== panel);
    document.querySelectorAll('.nav-link[data-section]').forEach(link => {
        if (link.dataset.section === id) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });
    document.getElementById('pageTitle').textContent = panel.getAttribute('aria-label');
    const workspace = panel.dataset.workspace;
    const trail = document.getElementById('workspaceTrail');
    trail.replaceChildren();
    if (workspace !== 'Home') {
        const parent = document.createElement('a');
        parent.href = workspace === 'Personal' ? '/learning' : '/organization';
        parent.dataset.section = workspace === 'Personal' ? 'learning' : 'organization';
        parent.textContent = workspace;
        trail.append(' / ', parent);
    }
    document.getElementById('organizationChange').hidden = workspace !== 'Organization';
    document.getElementById('companyLine').hidden = workspace !== 'Organization';
    document.querySelectorAll('.nav-section').forEach(group => {
        group.open = !!group.querySelector('[aria-current="page"]');
    });
    document.body.dataset.screen = id;
    document.title = `${panel.getAttribute('aria-label')} | Adapt360`;
    if (push) history.pushState({screen: id}, '', calendarTarget ? '/organization#dashCalendar' : (id === 'dashboard' ? '/' : `/${id}`));
    closeMenu();
    if (push) {
        const heading = panel.querySelector('h1');
        heading?.setAttribute('tabindex', '-1');
        heading?.focus({preventScroll: true});
    }
    if (calendarTarget) document.getElementById('dashCalendar')?.scrollIntoView({behavior:'smooth',block:'start'});
    else if(controlTarget)document.getElementById('organization-'+controlTarget)?.scrollIntoView({behavior:'smooth'});else window.scrollTo({top:0, behavior:'instant'});
};
document.addEventListener('click', event => {
    const link = event.target.closest('a[data-section]');
    if (link && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && event.button === 0) {
        event.preventDefault(); window.adaptNavigate(link.dataset.section);
    }
});
menu?.addEventListener('click', () => {
    const open = document.body.classList.toggle('sidebar-open');
    menu.setAttribute('aria-expanded', String(open));
});
document.querySelector('[data-menu-close]').addEventListener('click', closeMenu);
document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMenu(); });
window.addEventListener('popstate', () => window.adaptNavigate(location.pathname === '/organization' && location.hash === '#dashCalendar' ? 'calendar' : (location.pathname.slice(1) || 'dashboard'), false));
document.querySelectorAll('dialog').forEach(dialog => dialog.addEventListener('click', event => {
    if (event.target === dialog) { const r = dialog.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close(); }
}));
createIcons({icons});
await import('./adapt360-reference.js');
window.makeQR();
window.adaptNavigate(location.pathname === '/organization' && location.hash === '#dashCalendar' ? 'calendar' : document.body.dataset.screen, false);
const syncCompany = () => {
    document.getElementById('sidebarCompany').textContent = window.state.profile.company;
    document.getElementById('sidebarSite').textContent = window.state.profile.site;
};
syncCompany();
new MutationObserver(syncCompany).observe(document.getElementById('companyLine'), {childList:true});

await import('./hra.js');

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

const messageEditor=document.getElementById('safety-message-editor');
function loadSafetyMessage(){if(messageEditor)messageEditor.value=window.state.safetyMessages?.[document.getElementById('workerLang').value]||'';}
loadSafetyMessage();document.getElementById('workerLang')?.addEventListener('change',loadSafetyMessage);
document.getElementById('publish-safety-message')?.addEventListener('click',async()=>{const message=messageEditor.value.trim();if(!message){messageEditor.focus();return;}window.state.safetyMessages??={};window.state.safetyMessages[document.getElementById('workerLang').value]=message;await window.save();window.renderWorkerPortal();});
if(document.body.dataset.canEdit!=='true'){
 const lockControls=()=>{document.querySelectorAll('.adapt-screen input,.adapt-screen textarea,.adapt-screen select,.adapt-screen button[onclick],#organizationChange').forEach(control=>{if(control.closest('#learning, #workerportal, #roles') || control.matches('[type=search],#workerLang,#homeCalendarMonth')||/Search|Filter/.test(control.id)||/^(go\(|window.print\(|openEvidenceIndex\(|mentorExplain\(|changeCalMonth\()/.test(control.getAttribute('onclick')||''))return;control.disabled=true;control.title='An owner or editor can update organization records.';});};lockControls();new MutationObserver(lockControls).observe(document.querySelector('.main-content'),{childList:true,subtree:true});
}

window.dispatchEvent(new Event('adapt:ready'));

import './role-select.js';

import './member-search.js';


window.organizationCalendarEditIndex = null;
window.toggleOrganizationDueDate = (show, date) => {
 const editor=document.getElementById('eventModal'),open=show ?? editor.hidden;
 editor.hidden=!open;document.getElementById('addOrganizationDueDate')?.setAttribute('aria-expanded',String(open));
 window.organizationCalendarEditIndex=null;
 window.updateCalendarSubtypes?.();
 document.getElementById('eventModal-title').textContent='Add compliance due date';
 document.getElementById('saveCalendarEvent').textContent='Add to calendar';
 document.getElementById('calendarStatusField').hidden=true;document.getElementById('deleteCalendarEvent').hidden=true;
 if(open){document.getElementById('evDate').value=date || new Date().toLocaleDateString('en-CA');document.getElementById('evTime').value='';document.getElementById('evItem').value='';document.getElementById('evDate').focus();}
};
window.editOrganizationCalendarEvent = index => {
 const entry=window.state.calendar[index];if(!entry)return;if(/training|assessment/i.test(entry.type)){window.adaptNavigate('training');return;}
 window.toggleOrganizationDueDate(true,entry.date);window.organizationCalendarEditIndex=index;
 for(const [field,key] of [['evTime','time'],['evItem','item'],['evType','type'],['evOwner','owner'],['evStatus','status']])document.getElementById(field).value=entry[key]||'';
 window.updateCalendarSubtypes(entry.subtype);
 document.getElementById('eventModal-title').textContent='Edit compliance due date';document.getElementById('saveCalendarEvent').textContent='Save';
 document.getElementById('calendarStatusField').hidden=false;document.getElementById('deleteCalendarEvent').hidden=false;
 document.getElementById('eventModal').scrollIntoView({behavior:'smooth',block:'nearest'});
};
window.deleteOrganizationCalendarEvent = () => {const index=window.organizationCalendarEditIndex;if(index===null)return;if(!confirm('Delete this calendar entry?'))return;window.state.calendar.splice(index,1);window.save();window.toggleOrganizationDueDate(false);window.renderAll();};

import './legal-menu.js';

await import('./jms-builder.js');

import './training-materials.js';
window.updateCalendarSubtypes = selected => {
 const type=document.getElementById('evType').value,select=document.getElementById('evSubtype');
 const values=type==='Emergency & drills'?['Fire / evacuation','Chemical spill','Medical emergency','Rescue / confined space','Custom']:type==='Corrective actions'?['HIRARC','Incident','Inspection','HRA','Committee','Audit','OSHWA','Adapt Engine','Custom']:['General','Custom'];
 select.replaceChildren(...values.map(value=>new Option(value,value)));
 if(selected){select.value=values.includes(selected)?selected:'Custom';document.getElementById('evCustom').value=values.includes(selected)?'':selected;}
 document.getElementById('evCustomWrap').hidden=select.value!=='Custom';
};
window.updateCalendarSubtypes();
document.querySelectorAll('[data-calendar-filter]').forEach(button=>button.addEventListener('click',()=>{window.calendarEntryFilter=button.dataset.calendarFilter;document.querySelectorAll('[data-calendar-filter]').forEach(item=>item.setAttribute('aria-pressed',String(item===button)));window.renderDashboardCalendar();}));

await import('./safety-workflows.js');

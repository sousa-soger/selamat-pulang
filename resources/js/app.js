import '../css/auralis.css';
import { createIcons, icons } from 'lucide';
import { gsap } from 'gsap';

createIcons({ icons });

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const dialog = document.querySelector('#preview-dialog');
const toast = document.querySelector('.toast');
let toastTimer;

function notify(message) {
    toast.textContent = message;
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.hidden = true; }, 4200);
}

function selectTab(group, name) {
    if (!group) return;
    const owns = element => element.closest('[data-tabs]') === group;
    const tabs = [...group.querySelectorAll('[data-tab]')].filter(owns);
    const panels = [...group.querySelectorAll('[data-panel]')].filter(owns);
    if (!tabs.some(tab => tab.dataset.tab === name)) return;
    tabs.forEach(tab => {
        const selected = tab.dataset.tab === name;
        tab.setAttribute('aria-selected', String(selected));
        tab.tabIndex = selected ? 0 : -1;
    });
    panels.forEach(panel => {
        panel.hidden = panel.dataset.panel !== name;
        if (!panel.hidden && !reducedMotion.matches) {
            gsap.fromTo(panel, { y: 7 }, { y: 0, duration: 0.3, ease: 'power2.out', clearProps: 'transform' });
        }
    });
}

document.querySelectorAll('[data-tabs]').forEach((group, index) => {
    const tabs = [...group.querySelectorAll('[data-tab]')].filter(tab => tab.closest('[data-tabs]') === group);
    tabs.forEach((tab, tabIndex) => {
        const panel = [...group.querySelectorAll('[data-panel]')].find(panel => panel.closest('[data-tabs]') === group && panel.dataset.panel === tab.dataset.tab);
        tab.id = `tab-${index}-${tabIndex}`;
        tab.tabIndex = tab.getAttribute('aria-selected') === 'true' ? 0 : -1;
        if (panel) {
            panel.id = `panel-${index}-${tabIndex}`;
            tab.setAttribute('aria-controls', panel.id);
            panel.setAttribute('aria-labelledby', tab.id);
            panel.tabIndex = 0;
        }
    });
});

const field = (label, value = '', type = 'text') => `<label class="field"><span>${label}</span><input type="${type}" value="${value}"></label>`;
const select = (label, options) => `<label class="field"><span>${label}</span><select>${options.map(option => `<option>${option}</option>`).join('')}</select></label>`;
const previewForm = (body, action) => `<form data-preview-form>${body}<button type="submit" class="button primary mt-5">${action}</button></form>`;

const dialogs = {
    workplace: ['Your workplace', '<div class="flex items-center gap-3 mb-4"><span class="workplace-symbol">CF</span><div><strong class="text-gray-800">Syarikat Contoh Fabrikasi Sdn Bhd</strong><p>Miri, Sarawak / 60 employees</p></div></div><a class="button primary" href="/profile">Open workplace profile</a>'],
    account: ['Aina binti Rahman', '<p>OSH Coordinator</p><p class="mt-3">Syarikat Contoh Fabrikasi Sdn Bhd<br>Miri, Sarawak</p>'],
    help: ['Workplace guidance', '<p>Choose a module to review its example records and supporting guidance.</p><div class="flex flex-col items-start gap-3 mt-5"><a class="text-link" href="/profile">Workplace profile</a><a class="text-link" href="/legal">Legal requirements</a><a class="text-link" href="/hirarc">Job methods and risk assessment</a><a class="text-link" href="/health">Health risk assessments</a></div>'],
    notifications: ['On your radar', '<div class="timeline"><div class="timeline-item alert"><a href="/committee"><strong>CHRA quotations overdue</strong><p>Action due 15 September 2026</p></a></div><div class="timeline-item"><a href="/committee"><strong>Quarterly meeting approaching</strong><p>Due by 10 October 2026</p></a></div><div class="timeline-item"><a href="/hirarc"><strong>Spray painting ready for assessment</strong><p>Review the submitted JMS</p></a></div></div>'],
    report: ['Export preview', '<p>The export would include the current module, assessment statuses and supporting records.</p><div class="notice">Export generation will be connected during implementation.</div>'],
    evaluation: ['Record compliance evaluation', previewForm(field('Evaluation date','2026-10-02','date') + field('Reviewed by','Aina binti Rahman') + '<label class="field mt-3"><span>Review notes</span><textarea placeholder="Evidence checked and follow-up required"></textarea></label>', 'Record evaluation')],
    evidence: ['Supporting evidence', previewForm(select('Compliance status',['Partial','Compliant','Not compliant','Not assessed']) + field('Evidence reference') + field('Next review date','','date'), 'Save review')],
    'sign-policy': ['Signed policy record', previewForm(field('Signed by','Tan Wei Ming') + field('Position','Managing Director') + field('Date signed','','date') + '<label class="check-row"><input type="checkbox"><span><strong>Policy displayed and shared with employees</strong></span></label>', 'Record signature')],
    member: ['Committee member', previewForm(field('Full name') + field('Position') + select('Committee role',['Employee rep','Employer rep','Chairman','Secretary']), 'Save member')],
    meeting: ['Plan a committee meeting', previewForm(field('Meeting date','2026-10-09','date') + field('Time','10:00','time') + field('Venue','Meeting room, Level 1') + '<label class="field mt-3"><span>Agenda</span><textarea rows="5">Previous minutes\nOpen action review\nIncident reports\nWorkplace inspection\nJMS and HIRARC\nHealth assessments\nTraining and awareness</textarea></label><p class="mt-3 text-[10px]">All six committee members are included.</p>', 'Prepare invitation')],
    invitation: ['Meeting invitation / 2-2026', '<p><strong>Mesyuarat Jawatankuasa Keselamatan dan Kesihatan</strong></p><p class="mt-3">Date: 9 October 2026<br>Time: 10:00 AM<br>Venue: Meeting room, Level 1</p><p class="mt-3">All committee members are invited to review open actions, incidents, workplace inspections, HIRARC and health assessments.</p><p class="mt-3">Aina binti Rahman<br>Committee Secretary</p>'],
    action: ['Committee action', previewForm(field('Action description') + select('Responsible person',['Aina Rahman','Rajesh Kumar','Lim Mei Ling','Tan Wei Ming']) + field('Due date','','date') + select('Status',['Open','In progress','Done']), 'Save action')],
    activity: ['Add work activity', previewForm(field('Activity name') + field('Work area') + field('Engineer / supervisor'), 'Add activity')],
    jms: ['Job method statement', previewForm(field('JMS reference','JMS-FAB-001') + field('Prepared by','Ir. Hafiz bin Ismail') + field('Revision','1') + select('Status',['Draft','Submitted','Approved']) + '<label class="field mt-3"><span>Job steps</span><textarea placeholder="Sequence of work, tools and materials"></textarea></label>', 'Save statement')],
    hazard: ['Hazard & control', previewForm(field('Job step') + field('Hazard') + field('Possible harm') + select('Likelihood',['1','2','3','4','5']) + select('Severity',['1','2','3','4','5']) + select('Control type',['Elimination','Substitution','Engineering','Administrative','PPE']) + field('Control measure') + field('Responsible person') + field('Due date','','date'), 'Save hazard')],
    chemical: ['Add chemical', previewForm(field('Chemical name') + field('Where used') + field('Main hazard from SDS') + '<label class="check-row"><input type="checkbox"><span><strong>Safety data sheet on file</strong></span></label>', 'Add chemical')],
    unit: ['Add work unit', previewForm(field('Work unit') + field('Work area') + field('Number of workers','','number'), 'Add work unit')],
    recommendation: ['Assessment recommendation', previewForm(field('Recommendation') + field('Responsible person') + field('Due date','','date') + select('Status',['Open','In progress','Done']), 'Save recommendation')],
    'language-print': ['Safety handout preview', '<p>Handouts combine the selected language, workplace safety messages and company details.</p><div class="notice">Handout generation will be connected during implementation.</div>'],
    review: ['Complete self-check', previewForm(field('Reviewed by','Aina binti Rahman') + field('Review date','2026-10-02','date') + '<label class="field mt-3"><span>Review notes</span><textarea placeholder="Document gaps and agreed follow-up"></textarea></label>', 'Complete review')],
};

document.addEventListener('click', async event => {
    const tab = event.target.closest('[data-tab]');
    if (tab) selectTab(tab.closest('[data-tabs]'), tab.dataset.tab);
    const jump = event.target.closest('[data-select-tab]');
    if (jump) selectTab(document.querySelector(`[data-tabs="${jump.dataset.tabGroup}"]`), jump.dataset.selectTab);
    const trigger = event.target.closest('[data-dialog]');
    if (trigger) {
        event.preventDefault();
        const content = dialogs[trigger.dataset.dialog];
        if (content) {
            document.querySelector('#dialog-title').textContent = content[0];
            document.querySelector('#dialog-body').innerHTML = content[1];
            dialog.showModal();
        }
    }
    if (event.target.closest('[data-close-dialog]')) dialog.close();
    if (event.target === dialog) {
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    }
    const menu = event.target.closest('[data-menu]');
    if (menu) {
        const opened = document.body.classList.toggle('sidebar-open');
        menu.setAttribute('aria-expanded', String(opened));
        if (opened) document.querySelector('.sidebar .nav-link[aria-current]').focus();
    }
    if (event.target.closest('[data-menu-close]')) closeMenu();
    const filter = event.target.closest('[data-filter]');
    if (filter) {
        document.querySelectorAll(`[data-filter="${filter.dataset.filter}"]`).forEach(button => button.setAttribute('aria-pressed', String(button === filter)));
        document.querySelectorAll(`[data-filter-row="${filter.dataset.filter}"]`).forEach(row => {
            row.hidden = filter.dataset.value === 'attention' ? row.dataset.state === 'Compliant' : filter.dataset.value !== 'all' && row.dataset.state !== filter.dataset.value;
        });
    }
    if (event.target.closest('[data-copy]')) {
        const policy = [...document.querySelectorAll('[data-policy]')].find(element => !element.closest('[data-panel]').hidden);
        try {
            await navigator.clipboard.writeText(policy.innerText);
            notify('Policy text copied.');
        } catch {
            notify('Copy unavailable. Select the policy text to copy it.');
        }
    }
});

function closeMenu() {
    document.body.classList.remove('sidebar-open');
    document.querySelector('[data-menu]').setAttribute('aria-expanded', 'false');
}

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
        closeMenu();
        document.querySelector('[data-menu]').focus();
    }
    const tab = event.target.closest('[data-tab]');
    if (!tab || !['ArrowLeft','ArrowRight','Home','End'].includes(event.key)) return;
    event.preventDefault();
    const group = tab.closest('[data-tabs]');
    const tabs = [...group.querySelectorAll('[data-tab]')].filter(element => element.closest('[data-tabs]') === group);
    let index = tabs.indexOf(tab);
    if (event.key === 'Home') index = 0;
    else if (event.key === 'End') index = tabs.length - 1;
    else index = (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
    selectTab(group, tabs[index].dataset.tab);
    tabs[index].focus();
});

document.addEventListener('submit', event => {
    if (!event.target.matches('[data-preview-form]')) return;
    event.preventDefault();
    if (dialog.open) dialog.close();
    notify('Design preview only. Changes are not saved.');
});

document.addEventListener('change', event => {
    if (!event.target.matches('[data-file-preview]')) return;
    const file = event.target.files?.[0];
    if (!file) return;
    event.target.closest('.upload').querySelector('strong').textContent = file.name;
    notify('File selected for preview. Nothing has been uploaded.');
});

if (!reducedMotion.matches) {
    gsap.fromTo('.page-heading', { y: 10 }, { y: 0, duration: 0.55, ease: 'power2.out', clearProps: 'transform' });
    const progressItems = document.querySelectorAll('.stats .stat-item, .level-item');
    if (progressItems.length) {
        gsap.fromTo(progressItems, { y: 8 }, { y: 0, duration: 0.55, stagger: 0.045, ease: 'power2.out', clearProps: 'transform' });
    }
}

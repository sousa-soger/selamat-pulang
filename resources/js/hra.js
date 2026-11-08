import './hra-select.js';
import '../css/hra.css';

const $ = id => document.getElementById(id);
const esc = window.esc;
const canEdit = () => document.body.dataset.canEdit === 'true';
const base = `/organizations/${document.body.dataset.organizationId}/hra/documents`;
let draft = null;
let importing = false;
const fields = {title:'Title', type:'Type', owner:'Owner', assessedOn:'AssessedOn', due:'Due', priority:'Priority', status:'Status', overview:'Overview'};
const newRecord = () => ({id:crypto.randomUUID(),title:'',type:'other',owner:'',assessedOn:'',due:'',priority:'Unassigned',status:'Draft',overview:'',items:[],documents:[]});
const records = () => window.state.hraRecords ||= [];
const overdue = record => record.due && record.due < window.dateISO(new Date());

// Retain existing reviews without creating records for unused pathways.
if (!Array.isArray(window.state.hraRecords)) {
    window.state.hraRecords = Object.entries(window.state.assessments || {}).map(([type, old]) => ({...newRecord(),type,title:old.report || `${type} assessment`,owner:old.owner || '',due:old.due || '',overview:old.notes || '',status:'In progress'}));
}

window.renderHraWorkspace = () => {
    const list = records();
    $('hraCount').textContent = list.length;
    const late = list.filter(overdue).length;
    $('hraNotice').textContent = !list.length ? 'Action needed: start your organization’s health risk assessment. Review workplace exposures, arrange the relevant assessment, then upload the report or add it manually.' : late ? `${late} assessment review${late === 1 ? ' is' : 's are'} overdue. Open the assessment to plan follow-up.` : 'Keep assessment dates and recommendations current. Review imported details against the original report before relying on them.';
    $('hraCards').innerHTML = list.length ? list.map(record => `<article class="hra-record"><header><div><h3>${esc(record.title)}</h3><span class="hra-value hra-tone-${({Draft:"neutral","In progress":"progress",Reviewed:"done"})[record.status]||"neutral"}">${esc(record.status)}</span> <span class="hra-value hra-tone-${({Unassigned:"neutral",Low:"low",Medium:"medium",High:"high",Urgent:"urgent"})[record.priority]||"neutral"}">${esc(record.priority)}</span> ${overdue(record) ? '<span class="badge red">Review overdue</span>' : ''}</div><button type="button" class="btn" data-hra-edit="${esc(record.id)}">${canEdit() ? 'Open assessment' : 'View assessment'}</button></header><p>${esc(record.overview || 'Add an overview of the assessment and the work it covers.')}</p><dl><div><dt>Assessment date</dt><dd>${esc(record.assessedOn || 'Not recorded')}</dd></div><div><dt>Next review</dt><dd>${esc(record.due || 'Not recorded')}</dd></div><div><dt>Owner / assessor</dt><dd>${esc(record.owner || 'Not assigned')}</dd></div><div><dt>Follow-up</dt><dd>${(record.items || []).filter(x => x.status !== 'Done').length} open items</dd></div></dl><footer>${(record.documents || []).map(document => `<a class="hra-attachment" href="${base}/${encodeURIComponent(document.id)}">${esc(document.name)}</a>`).join('')}</footer></article>`).join('') : '<div class="hra-empty"><h3>No assessments yet</h3><p>Your organization starts with a clean register. Upload an existing report to prepare its details, or add an assessment and build your own items and steps.</p><button class="btn primary" type="button" data-hra-new="upload">Upload your first report</button> <button class="btn" type="button" data-hra-new="manual">+ Add manually</button></div>';
    document.querySelectorAll('[data-hra-new]').forEach(button => button.disabled = !canEdit());
    const home = $('homeHraStatus');
    if (home) home.textContent = !list.length ? 'Start your first health risk assessment' : `${list.length} assessments · ${late} reviews overdue`;
};

function readFields() { for (const [key, suffix] of Object.entries(fields)) draft[key] = $(`hra${suffix}`).value.trim(); }
function writeFields() {
    for (const [key, suffix] of Object.entries(fields)) $(`hra${suffix}`).value = draft[key] || '';
    $('hraSource').textContent = draft.sourceExcerpt || 'No extracted text. You can fill in the assessment manually.';
    $('hraDateSource').textContent = draft.dateSource ? `Assessment date source: ${draft.dateSource}` : 'Dates and priorities that cannot be identified remain unassigned.';
    $('hraAttachments').innerHTML = draft.documents.map(document => `<a class="hra-attachment" href="${base}/${encodeURIComponent(document.id)}">${esc(document.name)}</a>`).join('');
    renderItems();
}
function itemField(index, key, label, value, options) {
    const id = `hra-item-${index}-${key}`;
    const attributes = `id="${id}" data-item="${index}" data-field="${key}"`;
    return `<div><label for="${id}">${label}</label>${options ? `<select ${attributes}>${options.map(option => `<option${value === option ? ' selected' : ''}>${option}</option>`).join('')}</select>` : key === 'description' ? `<textarea ${attributes} maxlength="12000">${esc(value || '')}</textarea>` : `<input ${attributes} ${key === 'title' ? 'required maxlength="250"' : 'type="date"'} value="${esc(value || '')}">`}</div>`;
}
function renderItems() {
    $('hraItems').innerHTML = draft.items.map((item, index) => `<section class="hra-item"><div class="hra-item-head"><h4><span class="jms-step-number">${String(index + 1).padStart(2,'0')}</span> Finding / recommendation</h4><button class="btn small danger" type="button" data-remove-item="${index}">Remove item</button></div><div class="form-grid">${itemField(index,'title','Finding title',item.title)}${itemField(index,'priority','Priority',item.priority,['Unassigned','Low','Medium','High','Urgent'])}${itemField(index,'due','Due date',item.due)}${itemField(index,'status','Status',item.status,['Open','In progress','Done'])}</div>${itemField(index,'description','Description / recommendation',item.description)}<div class="hra-step-guidance"><h4>Plan the action steps</h4><p class="note">Break this finding into practical actions. Use one action per step, in the order it should happen.</p></div>${!(item.steps || []).length ? '<p class="hra-steps-empty">No steps yet. Add the first action needed to address this finding.</p>' : ''}${(item.steps || []).map((step, si) => `<div class="hra-step"><span class="jms-step-number">${String(si + 1).padStart(2,'0')}</span><div><label for="hra-step-${index}-${si}">Action / step title</label><input id="hra-step-${index}-${si}" required maxlength="250" data-item="${index}" data-step="${si}" data-field="title" value="${esc(step.title)}"><label for="hra-step-desc-${index}-${si}">Details (optional)</label><textarea id="hra-step-desc-${index}-${si}" maxlength="12000" data-item="${index}" data-step="${si}" data-field="description">${esc(step.description || '')}</textarea></div><div class="hra-step-controls"><button class="btn small" type="button" data-move-step="${index}:${si}:-1" aria-label="Move step up" ${si===0?'disabled':''}>↑</button><button class="btn small" type="button" data-move-step="${index}:${si}:1" aria-label="Move step down" ${si===item.steps.length-1?'disabled':''}>↓</button><button class="btn small danger" type="button" data-remove-step="${index}:${si}" aria-label="Remove step">×</button></div></div>`).join('')}<button type="button" class="btn" data-add-step="${index}">+ Add action step</button></section>`).join('');
    lockEditor();
}
function lockEditor() { $('hraEditor').querySelectorAll('input,select,textarea,button').forEach(control => { if (control.id !== 'hraCancel') control.disabled = !canEdit() || importing || (control.matches('[data-move-step]') && (()=>{const [i,from,offset]=control.dataset.moveStep.split(':').map(Number);return from+offset<0||from+offset>=draft.items[i].steps.length;})()); }); }
function openEditor(record) {
    draft = structuredClone(record || newRecord());
    draft.items ||= []; draft.documents ||= [];
    $('hraEditor').hidden = false;
    $('hraImportStatus').textContent = 'Detected details fill empty fields. Review them before saving.';
    $('hraSaveError').textContent = '';
    $('hraDocument').value = '';
    writeFields();
    $('hraEditor').scrollIntoView({behavior:'smooth',block:'start'});
    $('hraTitle').focus({preventScroll:true});
}
document.addEventListener('click', event => {
    const add = event.target.closest('[data-hra-new]');
    const edit = event.target.closest('[data-hra-edit]');
    if (add && canEdit()) { openEditor(); if (add.dataset.hraNew === 'upload') $('hraDocument').click(); }
    if (edit) openEditor(records().find(record => record.id === edit.dataset.hraEdit));
});
$('hraCancel').addEventListener('click', () => { if (!importing && (!canEdit() || confirm('Discard unsaved changes? Uploaded reports will not appear in the register until saved.'))) { draft = null; $('hraEditor').hidden = true; } });
$('hraAddItem').addEventListener('click', () => { if (!canEdit() || importing) return; draft.items.push({title:'',description:'',priority:'Unassigned',due:'',status:'Open',steps:[]}); renderItems(); $('hraItems').lastElementChild.querySelector('input').focus(); });
$('hraItems').addEventListener('input', event => {
    const {item,step,field} = event.target.dataset;
    if (item === undefined || !canEdit()) return;
    const target = step === undefined ? draft.items[Number(item)] : draft.items[Number(item)].steps[Number(step)];
    target[field] = event.target.value;
});
$('hraItems').addEventListener('click', event => {
    if (!canEdit() || importing) return;
    const button = event.target.closest('[data-add-step],[data-remove-item],[data-remove-step],[data-move-step]'); if (!button) return;
    let focusStep=null;
    if(button.dataset.moveStep){const [i,from,offset]=button.dataset.moveStep.split(':').map(Number);const steps=draft.items[i].steps,to=from+offset;if(to<0||to>=steps.length)return;[steps[from],steps[to]]=[steps[to],steps[from]];focusStep=[i,to];}
    if (button.dataset.addStep !== undefined) draft.items[Number(button.dataset.addStep)].steps.push({title:'',description:''});
    if (button.dataset.removeItem !== undefined) draft.items.splice(Number(button.dataset.removeItem),1);
    if (button.dataset.removeStep) { const [i,s] = button.dataset.removeStep.split(':').map(Number); draft.items[i].steps.splice(s,1); }
    renderItems();
    if(focusStep)document.getElementById('hra-step-'+focusStep.join('-'))?.focus();
    if(button.dataset.addStep!==undefined){const i=Number(button.dataset.addStep);document.getElementById('hra-step-'+i+'-'+(draft.items[i].steps.length-1))?.focus();}
});
$('hraDocument').addEventListener('change', async event => {
    const file = event.target.files[0]; if (!file || importing || !canEdit()) return;
    if (!/\.(pdf|docx)$/i.test(file.name) || file.size > 15 * 1024 * 1024) { $('hraImportStatus').textContent = 'Choose a PDF or DOCX report smaller than 15 MB.'; event.target.value = ''; return; }
    readFields(); importing = true; lockEditor(); $('hraCancel').disabled = true;
    $('hraImportStatus').textContent = 'Uploading report and reading its contents…';
    const body = new FormData(); body.append('document',file);
    try {
        const response = await fetch(base,{method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body});
        const result = await response.json();
        if (!response.ok) throw new Error(result.errors?.document?.[0] || result.message || 'Upload failed. Try again.');
        draft.documents.push(result.document);
        for (const key of ['title','owner','assessedOn','due','overview']) if (!draft[key]) draft[key] = result.draft[key];
        if (draft.type === 'other') draft.type = result.draft.type;
        draft.sourceExcerpt = result.draft.sourceExcerpt; draft.dateSource = result.draft.dateSource;
        draft.items.push(...(result.draft.items || []));
        $('hraImportStatus').textContent = result.draft.warning;
        writeFields();
    } catch (error) { $('hraImportStatus').textContent = error.message || 'Upload failed. Try again.'; }
    finally { importing = false; $('hraCancel').disabled = false; event.target.value = ''; lockEditor(); }
});
$('hraRecordForm').addEventListener('submit', async event => {
    event.preventDefault(); if (!canEdit() || importing) return;
    readFields();
    if (!draft.title) { $('hraTitle').focus(); return; }
    const index = records().findIndex(record => record.id === draft.id);
    const old = index < 0 ? null : structuredClone(records()[index]);
    if (index < 0) records().push(structuredClone(draft)); else records()[index] = structuredClone(draft);
    $('hraSave').disabled = true;
    const saved = await window.save();
    if (saved === false) {
        if (index < 0) records().pop(); else records()[index] = old;
        $('hraSaveError').textContent = 'Assessment was not saved. Keep these details and reload the workspace before retrying.'; $('hraSave').disabled = false; return;
    }
    $('hraEditor').hidden = true; draft = null;
    window.renderAll(); window.toast('Assessment saved to your organization.');
});
window.renderHraWorkspace();

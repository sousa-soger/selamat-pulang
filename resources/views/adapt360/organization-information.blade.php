@if($organization)
@php $canManage = $organization->canManage(auth()->user()); @endphp
<section class="card organization-summary organization-profile" id="organizationProfile">
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form method="post" action="{{ route('organizations.update', $organization) }}" id="organizationDetailsEditor">
@csrf @method('PATCH')
<div class="toolbar organization-summary-heading">
<label class="profile-name"><span class="sr-only">Organization name</span><input name="name" aria-label="Organization name" value="{{ old('name',$organization->name) }}" required maxlength="160" readonly></label>
@if($canManage)<button type="button" class="btn profile-edit" aria-label="Edit organization information" aria-expanded="false" onclick="setProfileEditing(true)"><x-icon name="pencil" /></button><button type="button" class="btn profile-cancel" hidden onclick="document.getElementById('organizationDetailsEditor').reset();setProfileEditing(false)">Cancel</button>@endif
</div>
<div class="organization-information-grid">
@foreach(['industry'=>'Industry','registration'=>'Registration number','site'=>'Site / location','employees'=>'Number of employees','email'=>'Email','phone'=>'Phone','address'=>'Address'] as $field=>$label)
<div class="profile-field"><label for="organization-{{ $field }}">{{ $label }}:</label><input id="organization-{{ $field }}" name="{{ $field }}" value="{{ old($field,$organization->$field) }}" placeholder="-" readonly @if($field === 'employees') type="number" min="0" @elseif($field === 'email') type="email" @else maxlength="255" @endif @if($field === 'industry') autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="msicResults" @endif>
@if($field === 'industry')<div id="msicResults" class="msic-results" role="listbox" aria-label="MSIC industries" hidden></div><small class="profile-industry-help" hidden>Search code or industry in English / Bahasa Melayu. <a href="https://open.dosm.gov.my/data-catalogue/msic" target="_blank" rel="noopener">DOSM MSIC 2008</a></small><small id="msicStatus" role="status"></small>@endif
</div>
@endforeach
</div>
<div id="profileWorkplaceView">@include('adapt360.organization-workplace-settings', ['editing'=>false])</div>
@if($canManage)<div id="profileWorkplaceEdit" class="organization-inline-editor" hidden>@include('adapt360.organization-workplace-settings', ['editing'=>true])</div><button class="btn primary profile-save" hidden>Save organization</button>@endif
</form>
</section>
@if($canManage)
<script>
(() => {
const root=document.getElementById('organizationProfile'), form=root.querySelector('form'), industry=root.querySelector('#organization-industry'), results=root.querySelector('#msicResults'), status=root.querySelector('#msicStatus');
let rows=[], active=-1;
window.setProfileEditing=editing=>{
root.classList.toggle('is-editing',editing);
root.querySelectorAll('.organization-summary-heading input,.profile-field>input').forEach(input=>input.readOnly=!editing);
root.querySelector('.profile-edit').hidden=editing;
root.querySelector('.profile-edit').setAttribute('aria-expanded',String(editing));
root.querySelectorAll('.profile-cancel,.profile-save,.profile-industry-help').forEach(el=>el.hidden=!editing);
root.querySelector('#profileWorkplaceView').hidden=editing;
root.querySelector('#profileWorkplaceEdit').hidden=!editing;
root.querySelectorAll('#profileWorkplaceEdit input,#profileWorkplaceEdit select').forEach(el=>el.disabled=!editing);
close(); if(editing)form.elements.name.focus();
};
function close(){results.hidden=true;industry.setAttribute('aria-expanded','false');industry.removeAttribute('aria-activedescendant');active=-1;}
function search(){
if(industry.readOnly)return;
const terms=industry.value.toLowerCase().trim().split(/\s+/);
const matches=rows.filter(row=>terms.every(term=>(row.code+' '+row.name+' '+row.name_bm).toLowerCase().includes(term))).slice(0,30);
results.replaceChildren();active=-1;
matches.forEach((row,index)=>{const button=document.createElement('button');button.type='button';button.role='option';button.id='msic-option-'+index;const code=document.createElement('strong'),name=document.createElement('span');code.textContent=row.code;name.textContent=row.name;button.append(code,name);button.addEventListener('click',()=>{industry.value=row.code+' — '+row.name;close();industry.focus();});results.append(button);});
results.hidden=!matches.length;industry.setAttribute('aria-expanded',String(!!matches.length));status.textContent=matches.length?'':rows.length?'No matching industries. Try another name or code.':'Loading industries…';
}
industry.addEventListener('input',search);industry.addEventListener('focus',search);
industry.addEventListener('keydown',event=>{const options=[...results.children];if(event.key==='Escape'){close();return;}if(results.hidden)return;if(['ArrowDown','ArrowUp'].includes(event.key)){event.preventDefault();active=(active+(event.key==='ArrowDown'?1:-1)+options.length)%options.length;options.forEach((el,i)=>el.setAttribute('aria-selected',String(i===active)));industry.setAttribute('aria-activedescendant',options[active].id);options[active].scrollIntoView({block:'nearest'});}if(event.key==='Enter'&&active>=0){event.preventDefault();options[active].click();}});
document.addEventListener('click',event=>{if(!industry.parentElement.contains(event.target))close();});
fetch('{{ asset('msic-2008.json') }}').then(response=>{if(!response.ok)throw Error();return response.json();}).then(data=>{rows=data;if(document.activeElement===industry)search();}).catch(()=>status.textContent='Industry search is unavailable. Please try again later.');
setProfileEditing(@json($errors->any() && old('name') !== null));
})();
</script>
@endif
@endif

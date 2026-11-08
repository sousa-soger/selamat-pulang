@verbatim

<div class="hero"><div><h1>Permit to Work</h1><p>Interactive permit workflow linked to location, workers, risk assessment and required controls.</p></div><button type="button" class="btn primary" onclick="openModal('ptwModal')">+ Create permit</button></div>
<div class="grid g4">
<div class="card metric"><div class="label">Active</div><div class="value" id="ptwActive">2</div></div>
<div class="card metric"><div class="label">Pending</div><div class="value" id="ptwPending">0</div></div>
<div class="card metric"><div class="label">High risk</div><div class="value" id="ptwHighRisk">0</div></div>
<div class="card metric"><div class="label">Expired</div><div class="value" id="ptwExpired">0</div></div>
</div>
<div class="table-wrap"><table><thead><tr><th>Permit</th><th>Type</th><th>Location</th><th>Linked HIRARC</th><th>Assigned member</th><th>Valid until</th><th>Status</th><th></th></tr></thead><tbody id="ptwRows"></tbody></table></div>

@endverbatim

@php $permitPeople=$organization?->users()->get(['users.id','users.name','users.email']) ?? collect(); @endphp
<script type="application/json" id="permit-member-data">{!! json_encode($permitPeople->map(fn($person)=>['id'=>$person->id,'name'=>$person->name,'email'=>$person->email])->values(),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
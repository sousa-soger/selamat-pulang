@verbatim

<div class="hero"><div><h1>Permit to Work</h1><p>Interactive permit workflow linked to location, workers, risk assessment and required controls.</p></div><button type="button" class="btn primary" onclick="openModal('ptwModal')">+ Create permit</button></div>
<div class="grid g4">
<div class="card metric"><div class="label">Active</div><div class="value" id="ptwActive">2</div></div>
<div class="card metric"><div class="label">Pending</div><div class="value">1</div></div>
<div class="card metric"><div class="label">High risk</div><div class="value">1</div></div>
<div class="card metric"><div class="label">Expired</div><div class="value">0</div></div>
</div>
<div class="table-wrap"><table><thead><tr><th>Permit</th><th>Type</th><th>Location</th><th>Linked HIRARC</th><th>Valid until</th><th>Status</th><th></th></tr></thead><tbody id="ptwRows"></tbody></table></div>

@endverbatim

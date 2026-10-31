@verbatim

<div class="hero"><div><h1>Training Matrix &amp; Toolbox Talks</h1><p>Assign training by role and exposure; use a broad toolbox library for supervisor-led briefings and record attendance.</p></div><button type="button" class="btn primary" onclick="openModal('trainingModal')">+ Assign training</button></div>
<div class="grid g2">
<div class="card"><h4>Training matrix</h4><div class="table-wrap"><table><thead><tr><th>Worker / Role</th><th>Training</th><th>Due</th><th>Status</th><th></th></tr></thead><tbody id="trainingRows"></tbody></table></div></div>
<div class="card"><h4>Toolbox topic library</h4><div class="toolbar"><input id="toolboxSearch" oninput="renderToolbox()" placeholder="Search 25+ topics"><button type="button" class="btn teal" onclick="generateToolbox()">Assign custom topic</button></div><div class="list" id="toolboxList"></div></div>
</div>

@endverbatim

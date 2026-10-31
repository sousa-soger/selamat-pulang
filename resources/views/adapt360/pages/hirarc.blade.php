@verbatim

<div class="hero"><div><h1>JMS &amp; HIRARC</h1><p>Start with how the job is actually performed, then assess each job step. Risk score is calculated as <strong>Likelihood × Severity</strong> and linked controls feed the action tracker.</p></div><button type="button" class="btn primary" onclick="openModal('hirarcModal')">+ Add HIRARC row</button></div>
<div class="grid g2">
<div class="card"><h4>DOSH-style risk matrix prototype</h4><div><table class="matrix"><thead><tr><th>L \ S</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th></tr></thead><tbody id="matrixBody"></tbody></table></div><p class="note">Prototype bands only. Validate against the official matrix before production use.</p></div>
<div class="card"><h4>JMS before HIRARC</h4><label>Activity</label><input value="Fabrication / MIG welding"><label>Job steps</label><textarea>1. Prepare work area
2. Set up welding machine and gas
3. Fit and tack components
4. Weld joint
5. Grind / finish
6. Inspect and clean area</textarea><button type="button" class="btn" onclick="saveJMS()">Save JMS</button></div>
</div>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Activity</th><th>Hazard</th><th>L</th><th>S</th><th>Risk</th><th>Controls</th><th>Status</th><th></th></tr></thead><tbody id="hirarcRows"></tbody></table></div>

@endverbatim

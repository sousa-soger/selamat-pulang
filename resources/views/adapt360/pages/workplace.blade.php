@verbatim

<div class="hero"><div><h1>Workplace Profile</h1><p>Your workplace profile determines which Malaysian OSH requirements may apply and gives the Adapt Engine a baseline for future changes.</p></div><button type="button" class="btn primary" onclick="saveProfile()">Save workplace profile</button></div>
<div class="card">
<div class="form-grid">
<div><label for="companyName">Company name *</label><input id="companyName" value="Maju Engineering Sdn. Bhd." autocomplete="organization" required></div>
<div><label for="industry">Industry / business sector *</label><input id="industry" list="industryChoices" value="Manufacturing" placeholder="Type your industry" required><datalist id="industryChoices"><option value="Manufacturing"></option><option value="Construction"></option><option value="Logistics"></option><option value="Food Manufacturing"></option><option value="Retail / Services"></option><option value="Plantation"></option><option value="Oil & Gas"></option><option value="Healthcare"></option><option value="Hospitality"></option><option value="Other"></option></datalist></div>
<div><label for="employees">Number of employees *</label><input id="employees" min="1" step="1" type="number" value="68" required></div>
<div><label for="site">Primary site / location *</label><input id="site" value="Miri, Sarawak" required></div>
<div><label for="companyRegistration">Registration number (optional)</label><input id="companyRegistration" placeholder="e.g. SSM / ROC number"></div>
<div><label for="companyPhone">Company phone (optional)</label><input id="companyPhone" type="tel" placeholder="e.g. +60..."></div>
<div><label for="companyEmail">Company email (optional)</label><input id="companyEmail" type="email" placeholder="name@company.com"></div>
<div><label for="companyAddress">Company address (optional)</label><textarea id="companyAddress" rows="2" placeholder="Street, city, state, postcode"></textarea></div>
<div><label for="oshlead">OSH lead</label><select id="oshlead"><option value="OSH Coordinator">OSH Coordinator</option><option value="SHO">SHO</option><option value="Management / no dedicated OSH staff">Management / no dedicated OSH staff</option></select></div>
<div><label for="foreignWorkers">Foreign workers</label><select id="foreignWorkers"><option value="yes">Yes</option><option value="no">No</option></select></div>
</div>
<hr>
<label>Exposure / workplace triggers</label>
<div class="checkbox-grid">
<label class="check"><input checked id="fChemical" type="checkbox"> Chemicals used</label>
<label class="check"><input checked id="fNoise" type="checkbox"> Significant noise / grinding</label>
<label class="check"><input checked id="fErgo" type="checkbox"> Manual handling / ergonomic risk</label>
<label class="check"><input checked id="fIAQ" type="checkbox"> Air-conditioned indoor area</label>
<label class="check"><input checked id="fMachinery" type="checkbox"> Machinery / equipment</label>
<label class="check"><input checked id="fContractor" type="checkbox"> Contractors</label>
<label class="check"><input checked id="fHotwork" type="checkbox"> Welding / hot work</label>
<label class="check"><input id="fHeight" type="checkbox"> Work at height</label>
<label class="check"><input id="fConfined" type="checkbox"> Confined space</label>
</div>
<div class="callout tealish"><strong>Editable company profile:</strong> change the fields above, then tap <strong>Save workplace profile</strong>. Saving this profile rebuilds the applicability register, HRA tracker, next-step queue and selected operational controls. Check the workspace save status above to confirm your changes have been saved.</div>
</div>

@endverbatim

@verbatim

<div class="hero"><div><h1>Safety messages &amp; reporting</h1><p>Workers can access safety messages, acknowledgements and hazard reporting without a paid management licence. When deployed, the QR should point to the live worker portal URL.</p></div><button type="button" class="btn" onclick="window.print()">Print portal card</button></div>
<div class="portal">
<div class="grid g2">
<div>
<h3>Adapt360 Worker Portal</h3>
<p>Choose a language, read site-critical safety information and report a hazard or near miss.</p>
<div class="form-grid">
<div><label for="workerLang">Language</label><select id="workerLang" onchange="renderWorkerPortal()"><option value="en">English</option><option value="ms">Bahasa Malaysia</option><option value="id">Bahasa Indonesia</option><option value="bn">বাংলা / Bengali</option><option value="ne">नेपाली / Nepali</option><option value="my">Burmese</option><option value="fil">Filipino</option></select></div>
<div><label>Worker ID</label><input placeholder="Worker ID"></div>
</div>
<div class="card"><h4 id="portalTitle">Today's safety message</h4><p id="portalMessage">Wear the required PPE, follow the approved work method, and stop work if conditions become unsafe.</p><label class="check"><input id="workerAck" onchange="state.workerPortalAck=this.checked;save();renderDashboard();renderExcellence();toast(this.checked?'Safety message acknowledgement recorded.':'Acknowledgement removed.')" type="checkbox"> I understand / I have received this message.</label></div>
<div class="card"><h4>Report a hazard / near miss</h4><textarea id="workerReport" placeholder="Describe what you saw..."></textarea><button type="button" class="btn teal" onclick="submitWorkerReport()">Submit anonymously</button></div>
</div>
<div><div class="qrbox" id="qrcode"><div class="qr-fallback">QR loads when the QR library is available. The live deployment should encode the public worker portal URL.</div></div><p>Portal deep link: <span id="portalLinkText"></span></p></div>
</div>
</div>

@endverbatim

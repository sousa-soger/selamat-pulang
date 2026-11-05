@verbatim

<div class="hero"><div><h1>Safety &amp; Health Committee</h1><p>Meeting planning, membership check, minutes and linked actions. The module is activated when the workplace profile indicates it is required.</p></div><button type="button" class="btn primary" onclick="openModal('memberModal')">+ Add member</button></div>
<div class="grid g2">
<div class="card"><h4>Membership</h4><div class="list" id="committeeMembers"></div></div>
<div class="card"><h4>Next meeting</h4><div class="form-grid"><div><label for="meetingDate">Date</label><input id="meetingDate" type="date"></div><div><label>Venue</label><input placeholder="Meeting venue"></div></div><label>Standard agenda</label><textarea>1. Previous actions
2. Incidents and near misses
3. Inspection findings
4. HIRARC changes
5. Training and competency
6. Worker concerns
7. New actions and owners</textarea><button type="button" class="btn teal" onclick="saveMeetingPlan()">Save meeting plan</button></div>
</div>

@endverbatim

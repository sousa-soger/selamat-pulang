<div class="toolbar"><a class="btn" href="{{ route('account') }}">Manage organizations, members &amp; billing</a></div>
@verbatim

<div class="hero">
<div><h1>Keep your organization on track.</h1><p>Review workplace priorities, manage safety records and track occupational safety and health (OSH) progress.</p></div>
<span class="badge green">● Guided + Adaptive</span>
</div>
<div class="toolbar"><button class="btn" onclick="go('workplace')" type="button">Set up workplace</button><button class="btn" onclick="go('calendar')" type="button">Plan deadlines</button></div>
<div class="grid g2">
<div class="callout">
<h4> Adapt Alert</h4>
<p id="adaptSummary">New welding activity detected. 7 linked OSH areas require review.</p>
<div class="flow" id="adaptFlow"><span>HIRARC</span><span>Hot Work PTW</span><span>PPE</span><span>Training</span><span>Fire Safety</span><span>Gas Cylinder Register</span><span>Toolbox Talk</span></div>
<button class="btn primary" onclick="go('adapt')" type="button">Review impact</button>
</div>
<div class="callout tealish">
<h4>→ What needs attention?</h4>
<div class="list" id="dashboardNext"></div>
<button class="btn teal" onclick="go('next')" type="button">View organization priorities</button>
</div>
</div>
<div class="grid g4">
<div class="card metric clickable-metric" onclick="go('solve')" role="button" tabindex="0"><div class="label">Current pathway</div><div class="value" id="metricStage">Comply</div><div class="sub" id="metricSolve">SOLVE: Silver track</div><button class="metric-link" onclick="event.stopPropagation();go('solve')" type="button">Open pathway controls ↗</button></div>
<div class="card metric clickable-metric" onclick="go('legal')" role="button" tabindex="0"><div class="label">Compliance readiness</div><div class="value" id="metricCompliance">72%</div><div class="bar"><span id="metricComplianceBar" style="width:72%"></span></div><button class="metric-link" onclick="event.stopPropagation();go('legal')" type="button">Open legal register ↗</button></div>
<div class="card metric clickable-metric" onclick="go('capa')" role="button" tabindex="0"><div class="label">Open actions</div><div class="value" id="metricActions">6</div><div class="sub" id="metricOverdue">2 overdue</div><button class="metric-link" onclick="event.stopPropagation();go('capa')" type="button">Open corrective actions ↗</button></div>
<div class="card metric clickable-metric" onclick="go('hirarc')" role="button" tabindex="0"><div class="label">HIRARC coverage</div><div class="value" id="metricHirarc">83%</div><div class="sub">Activities assessed</div><button class="metric-link" onclick="event.stopPropagation();go('hirarc')" type="button">Open risk assessments ↗</button></div>
<div class="card metric linked-metric clickable-metric" onclick="go('adapt')" role="button" tabindex="0"><div class="label">Adaptive reviews</div><div class="value" id="metricLinked">0</div><div class="sub">Generated from connected modules</div><button class="inline-metric-btn" onclick="event.stopPropagation();go('adapt')" type="button">Open Adapt Engine ↗</button></div></div>

<details class="workspace-details"><summary class="btn">View readiness, calendar &amp; management reports</summary><div class="disclosure-content"><div class="grid g2">
<div class="card spotlight spotlight-v7">
<div class="spotlight-head">
<div>
<div class="eyebrow">EXCELLENCE SNAPSHOT</div>
<h4 id="statusHeadline">You are currently in the Comply stage</h4>
<p id="statusNarrative">Live demonstration indicators based on the records you edit. Scores are illustrative, not formal compliance certification.</p>

</div>
<div class="spotlight-score">
<div class="score-label">Readiness estimate</div>
<div class="score-value" id="readinessScore">68%</div>
<div class="score-chip" id="statusBadge">In Progress</div>
</div>
</div>
<div class="bar xl"><span id="readinessBar" style="width:68%"></span></div>
<div class="mini-stats">
<div class="mini-stat"><span>Current stage</span><strong id="statusCurrent">Comply</strong></div>
<div class="mini-stat"><span>Next milestone</span><strong id="statusNext">Manage</strong></div>
<div class="mini-stat"><span>Stage completion</span><strong id="statusProgressText">68%</strong></div>
</div>
</div>
<div class="card">
<h4>What is driving our position right now?</h4>
<div class="list">
<div class="driver-item"><div class="meta"><strong>Compliance readiness</strong><small>Generated from the current legal register status selections.</small></div><div class="driver-actions"><span class="badge blue" id="statusCompBadge">72%</span><button class="btn small" onclick="go('legal')" type="button">Open</button></div></div>
<div class="driver-item"><div class="meta"><strong>SOLVE / OSHWA maturity</strong><small>Linked to the checklist progress in the maturity module.</small></div><div class="driver-actions"><span class="badge purple" id="statusSolveBadge">62%</span><button class="btn small" onclick="go('solve')" type="button">Open</button></div></div>
<div class="driver-item"><div class="meta"><strong>5S + workplace condition</strong><small>Updated whenever the 5S safety checklist is toggled.</small></div><div class="driver-actions"><span class="badge teal" id="status5SBadge">70%</span><button class="btn small" onclick="go('fives')" type="button">Open</button></div></div>
<div class="driver-item"><div class="meta"><strong>Biggest blocker</strong><small id="statusBlockerText">Overdue actions and incomplete compliance evidence are slowing progression.</small></div><div class="driver-actions"><span class="badge red" id="statusBlockerBadge">Needs attention</span><button class="btn small" onclick="go('capa')" type="button">Fix now</button></div></div>
<div class="driver-item"><div class="meta"><strong>Immediate focus</strong><small id="statusFocusText">Close overdue actions, improve compliance evidence and keep maturity checkpoints moving.</small></div><div class="driver-actions"><span class="badge amber" id="statusFocusBadge">Priority</span><button class="btn small" onclick="go('next')" type="button">Open plan</button></div></div>
</div>
</div>
</div>
<div class="pathway">
<div class="stage stage-click" id="dashStageStart"><div class="num">Stage 1</div><h4>Start</h4><div class="note">Profile, applicability, policy, roles, basic workplace controls.</div><button class="stage-jump" onclick="go('workplace')" type="button">Open setup ↗</button></div>
<div class="stage active stage-click" id="dashStageComply"><div class="num">Stage 2</div><h4>Comply</h4><div class="note">JMS/HIRARC, committee, chemicals, PPE, emergency plan and legal duties.</div><button class="stage-jump" onclick="go('legal')" type="button">Open compliance ↗</button></div>
<div class="stage stage-click" id="dashStageManage"><div class="num">Stage 3</div><h4>Manage</h4><div class="note">HRA recommendations, drills, internal audit, control closure and KPI tracking.</div><button class="stage-jump" onclick="go('capa')" type="button">Open management ↗</button></div>
<div class="stage stage-click" id="dashStageExcellence"><div class="num">Stage 4</div><h4>Excellence</h4><div class="note">ISO 45001 evidence, management review, award readiness and continuous improvement.</div><button class="stage-jump" onclick="go('excel')" type="button">Open excellence ↗</button></div>
</div>
<div aria-label="Calendar preview" class="card dash-calendar-panel" id="dashCalendar">
<div class="cal-head"><div><div class="eyebrow cal-kicker">CONNECTED PLANNER</div><h4>Compliance calendar</h4><p class="note">Upcoming inspections, training, actions and deadlines / select any date to edit the full calendar.</p></div>
<button class="btn primary cal-edit" onclick="go('calendar')" type="button">Open &amp; edit calendar ↗</button></div>
<div class="cal-body">
<div class="cal-month"><div class="cal-month-head"><button aria-label="Previous month" class="btn small cal-shift" id="calPrev" type="button">‹</button><strong id="calMonthLabel"></strong><button aria-label="Next month" class="btn small cal-shift" id="calNext" type="button">›</button></div>
<div class="cal-weekdays"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div><div class="cal-month-grid" id="calMonthGrid"></div></div>
<div class="cal-upcoming"><div class="cal-upcoming-head"><h4>Upcoming &amp; overdue</h4><span class="badge blue" id="calEventCount">0 items</span></div><div class="cal-upcoming-list" id="calUpcomingList"></div></div>
</div>
</div>
<div class="grid g2">
<div class="card"><h4>Management view</h4><div id="managementView"></div></div>
<div class="card"><h4>Evidence health</h4>
<div class="progress-row"><span>Legal register</span><div class="bar"><span id="evidenceLegalBar" style="width:75%"></span></div><strong id="evidenceLegalPct">75%</strong></div>
<div class="progress-row"><span>Risk control</span><div class="bar"><span id="evidenceRiskBar" style="width:75%"></span></div><strong id="evidenceRiskPct">75%</strong></div>
<div class="progress-row"><span>Training</span><div class="bar"><span id="evidenceTrainingBar" style="width:68%"></span></div><strong id="evidenceTrainingPct">68%</strong></div>
<div class="progress-row"><span>Emergency</span><div class="bar"><span id="evidenceEmergencyBar" style="width:61%"></span></div><strong id="evidenceEmergencyPct">61%</strong></div>
<div class="progress-row"><span>Audit evidence</span><div class="bar"><span id="evidenceAuditBar" style="width:70%"></span></div><strong id="evidenceAuditPct">70%</strong></div>
</div>
</div>
<p class="dash-disclaimer">Prototype demonstration / maturity and readiness indicators are illustrative, editable and not an official DOSH, ISO 45001 or SOLVE assessment.</p>
</div></details>
@endverbatim


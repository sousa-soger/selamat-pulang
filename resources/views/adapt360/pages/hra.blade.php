<div class="hero"><div><h1>Health risk assessments</h1><p>Keep reports, findings and follow-up work together. Upload a report to prepare a draft, or build an assessment manually.</p></div></div>
<div id="hraNotice" class="hra-notice" role="status"></div>
<div class="toolbar hra-toolbar"><div><h2>Your assessments <span id="hraCount" class="badge grey">0</span></h2><p class="note">Only assessments added by your organization appear here.</p></div><div class="toolbar"><button type="button" class="btn" data-hra-new="manual">+ Add manually</button><button type="button" class="btn primary" data-hra-new="upload">Upload report</button></div></div>
<div id="hraCards" class="hra-records"></div>
<section id="hraEditor" class="hra-editor" aria-labelledby="hraEditorTitle" hidden>
    <div class="toolbar"><div><p class="note">ASSESSMENT DRAFT</p><h2 id="hraEditorTitle">Review assessment details</h2></div><button type="button" class="btn" id="hraCancel">Cancel</button></div>
    <form id="hraRecordForm">
        <div class="hra-upload"><label for="hraDocument"><strong>Attach a report</strong><span>PDF or DOCX · up to 15 MB · one file at a time</span></label><input id="hraDocument" type="file" accept=".pdf,.docx"><p id="hraImportStatus" role="status" aria-live="polite">Detected details fill empty fields. Review them before saving.</p><div id="hraAttachments"></div></div>
        <div class="form-grid">
            <div><label for="hraTitle">Assessment title</label><input id="hraTitle" required maxlength="250"></div>
            <div><label for="hraType">Assessment type</label><select id="hraType"><option value="other">Other / select type</option><option value="noise">Noise</option><option value="chemical">Chemical (CHRA)</option><option value="ergo">Ergonomics (IERA / AERA)</option><option value="iaq">Indoor air quality</option><option value="prisma">Psychosocial (PRiSMA)</option></select></div>
            <div><label for="hraOwner">Owner / assessor</label><input id="hraOwner" maxlength="250"></div>
            <div><label for="hraAssessedOn">Assessment date</label><input id="hraAssessedOn" type="date"></div>
            <div><label for="hraDue">Next review date</label><input id="hraDue" type="date"></div>
            <div><label for="hraPriority">Priority</label><select id="hraPriority"><option>Unassigned</option><option>Low</option><option>Medium</option><option>High</option><option>Urgent</option></select></div>
            <div><label for="hraStatus">Workflow status</label><select id="hraStatus"><option>Draft</option><option>In progress</option><option>Reviewed</option></select></div>
        </div>
        <label for="hraOverview">Overview / description</label><textarea id="hraOverview" rows="5" maxlength="12000"></textarea>
        <p class="note" id="hraDateSource"></p>
        <details class="hra-source"><summary>Extracted report text</summary><pre id="hraSource"></pre></details>
        <div class="toolbar"><div><h3>Findings &amp; recommendations</h3><p class="note">1. Describe a finding or recommendation. 2. Set its priority and owner follow-up date. 3. Add the actions needed to address it.</p></div><button type="button" class="btn" id="hraAddItem">+ Add finding</button></div>
        <div id="hraItems"></div>
        <p id="hraSaveError" role="alert"></p>
        <div class="toolbar hra-editor-footer"><span class="note">Saving a report does not mark a legal requirement compliant.</span><button class="btn primary" type="submit" id="hraSave">Save assessment</button></div>
    </form>
</section>

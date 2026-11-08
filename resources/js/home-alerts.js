const finished = item => ['Completed', 'Closed', 'Done', 'Cancelled'].includes(item.status);

export function buildHomeAlerts(state, account, today) {
    const alerts = [];
    const legal = state.legalStatus || {};
    const add = (id, category, title, detail, target, priority, due = '') => alerts.push({ id, category, title, detail, target, priority, due });
    if (account.organization) {
        const checks = state.solveChecks || [];
        const missing = pattern => checks.some(item => pattern.test(item.name) && !item.done);
        if (legal.policy !== 'Compliant' || missing(/policy/i)) add('policy', 'Policy', 'Review OSH policy', state.policyDraft ? 'Confirm approval and update the policy evidence.' : 'Prepare and approve your workplace safety policy.', 'roles', 0);
        if (!(state.members || []).length || legal.committee === 'Partial' || legal.committee === 'Not compliant') add('committee', 'Safety & health committee', 'Review committee arrangements', 'Check applicability, membership and appointment records.', 'committee', 1);
        const calendar = (state.calendar || []).filter(item => !finished(item));
        let committeeMeeting = false;
        let auditMeeting = false;
        calendar.forEach(item => {
            const text = `${item.type} ${item.item}`;
            const committee = /committee|jawatankuasa/i.test(text);
            const audit = /audit/i.test(text);
            committeeMeeting ||= committee;
            auditMeeting ||= audit;
            add(`calendar:${item.id || `${item.type}:${item.item}:${item.date}:${item.owner || ''}`}`, committee ? 'Committee meeting' : audit ? 'Audit meeting' : 'Compliance calendar', item.item, item.owner ? `Owner: ${item.owner}` : 'Review this scheduled item.', 'calendar', committee ? 2 : audit ? 3 : 8, item.date);
        });
        if (!committeeMeeting && (missing(/committee meetings/i) || !state.committeePlan?.date)) add('committee-meeting', 'Committee meeting', 'Review the committee meeting schedule', 'Check the next meeting date and minutes in the compliance calendar.', 'calendar', 2);
        if (!auditMeeting) add('audit-meeting', 'Audit meeting', 'Review the audit meeting schedule', 'No open audit is scheduled. Add the next audit when applicable.', 'calendar', 3);
        if (!(state.hirarc || []).length || legal.risk !== 'Compliant' || missing(/HIRARC/i)) add('hirarc', 'HIRARC', 'Review risk assessments', 'Complete job-step assessments, controls and review evidence.', 'hirarc', 4);
        if (!(state.jms || []).length) add('jms', 'JMS & emergency response', 'Prepare job method statements', 'Record the work steps before completing HIRARC.', 'hirarc', 5);
        if (legal.emergency !== 'Compliant' || missing(/emergency/i)) add('emergency', 'JMS & emergency response', 'Review emergency response', 'Review the response plan, team and drill evidence.', 'emergency', 5);
        (state.drills || []).filter(item => !finished(item)).forEach((item, index) => add(`drill:${item.id || index}`, 'JMS & emergency response', `${item.type} drill`, item.desc || 'Prepare and record the emergency exercise.', 'emergency', 5, item.date));
        const records = state.hraRecords || [];
        if (!records.length && !Object.keys(state.assessments || {}).length) add('hra', 'HRA', 'Start a health risk assessment', 'Review exposures and upload a report or add an assessment.', 'hra', 6);
        records.forEach((record, index) => {
            if (record.due && record.due <= today) add(`hra-review:${record.id || index}`, 'HRA', `Review ${record.title}`, 'The assessment review date has arrived.', 'hra', 6, record.due);
            (record.items || []).filter(item => !finished(item)).forEach((item, itemIndex) => add(`hra:${record.id || index}:${item.id || itemIndex}`, 'HRA', item.title, record.title, 'hra', 6, item.due));
        });
        Object.entries(state.assessments || {}).forEach(([name, item]) => {
            if (!finished(item) && (item.status !== 'Current' || (item.due && item.due <= today))) add(`assessment:${name}`, 'HRA', `Review ${name} assessment`, item.status || 'Review assessment evidence.', 'hra', 6, item.due);
        });
        const hraGaps = ['prisma', ...['noise', 'chemical', 'ergo', 'iaq'].filter(key => state.profile?.[key])].filter(key => legal[key] !== 'Compliant');
        if (hraGaps.length) add('hra-pathways', 'HRA', 'Review health assessment pathways', `${hraGaps.length} exposure pathway${hraGaps.length === 1 ? '' : 's'} need${hraGaps.length === 1 ? 's' : ''} evidence review.`, 'hra', 6);
        if (account.excellenceGap) add('iso', 'ISO & awards', 'Review ISO & award readiness', 'Complete the evidence gaps in the organization overview.', 'excel', 7);
        const fiveGaps = (state.fiveSChecks || []).filter(done => !done).length;
        if (fiveGaps) add('fives', '5S + safety', 'Complete the 5S checklist', `${fiveGaps} workplace checks remain.`, 'fives', 8);
        if (!account.organizationVerified) add('organization-verification', 'Verification', 'Verify your organization', 'Review organization details and verification status.', 'account', 8);
        for (const [key, target, category, titleKey, dueKey] of [['actions', 'capa', 'Corrective actions', 'text', 'due'], ['training', 'training', 'Training', 'what', 'due'], ['linkedReviews', 'adapt', 'Adaptive reviews', 'label', 'due']]) {
            (state[key] || []).filter(item => !finished(item)).forEach((item, index) => add(`${key}:${item.id || index}`, category, item[titleKey], item.owner ? `Owner: ${item.owner}` : 'Review the outstanding record.', target, 8, item[dueKey]));
        }
        checks.filter(item => !item.done && !/policy|HIRARC|committee meetings|emergency|5S/i.test(item.name)).forEach((item, index) => add(`solve:${index}:${item.name}`, 'Improvement', item.name, item.evidence, 'solve', 8));
    }
    if (!account.userVerified) add('user-verification', 'Verification', 'Verify your user account', 'Review your profile and email verification.', 'account', 8);
    if (account.mykkpStatus !== 'approved') add('mykkp', 'MyKKP', account.mykkpStatus === 'pending' ? 'MyKKP verification pending' : 'Complete MyKKP verification', account.mykkpStatus === 'pending' ? 'Your certificate is awaiting administrator review.' : 'Add your MyKKP ID and certificate, or correct your submission.', 'account', 8);
    return alerts;
}

export function sortHomeAlerts(alerts, mode, order = []) {
    const date = item => item.due || '9999-12-31';
    const rank = item => order.includes(item.id) ? order.indexOf(item.id) : order.length;
    return [...alerts].sort((a, b) => {
        if (mode === 'due') return date(a).localeCompare(date(b)) || a.priority - b.priority;
        if (mode === 'category') return a.category.localeCompare(b.category) || date(a).localeCompare(date(b));
        if (mode === 'custom') return rank(a) - rank(b) || a.priority - b.priority;
        return a.priority - b.priority || date(a).localeCompare(date(b));
    });
}

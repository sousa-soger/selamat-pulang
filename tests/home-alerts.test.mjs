import test from 'node:test';
import assert from 'node:assert/strict';
import { buildHomeAlerts, sortHomeAlerts } from '../resources/js/home-alerts.js';

const account = { organization: true, userVerified: false, organizationVerified: false, mykkpStatus: 'not_submitted', excellenceGap: true };
const today = '2026-11-08';
const state = {
    calendar: [
        { type: 'Committee', item: 'Quarterly meeting', date: '2026-11-20', status: 'Planned' },
        { type: 'Audit', item: 'Internal audit', date: '2026-11-09', status: 'Due' },
        { type: 'Audit', item: 'Finished audit', date: '2026-11-01', status: 'Completed' },
    ],
    fiveSChecks: [true, false],
    actions: [{ text: 'Open action', status: 'Open', due: '2026-11-01' }, { text: 'Closed action', status: 'Closed' }],
    hraRecords: [{ id: 'health', title: 'Noise', due: '2026-11-01', items: [{ title: 'Provide hearing protection', status: 'Open' }, { title: 'Completed control', status: 'Done' }] }],
};

test('recommended order puts requested safety sources ahead of equally ranked supporting tasks', () => {
    const alerts = sortHomeAlerts(buildHomeAlerts(state, account, today), 'priority');
    assert.deepEqual([...new Set(alerts.filter(item => item.priority < 8).map(item => item.category))], ['Policy', 'Safety & health committee', 'Committee meeting', 'Audit meeting', 'HIRARC', 'JMS & emergency response', 'HRA', 'ISO & awards']);
    assert.ok(alerts.filter(item => ['fives', 'mykkp', 'user-verification', 'organization-verification'].includes(item.id)).every(item => item.priority === 8));
    assert.ok(!alerts.some(item => ['Finished audit', 'Closed action', 'Completed control'].includes(item.title)));
    assert.equal(alerts.find(item => item.title === 'Internal audit').due, '2026-11-09');
    assert.ok(alerts.some(item => item.title === 'Review Noise'));
});

test('date, category and custom ordering include every alert and do not mutate source order', () => {
    const alerts = buildHomeAlerts(state, account, today);
    assert.equal(sortHomeAlerts(alerts, 'due')[0].due, '2026-11-01');
    const custom = sortHomeAlerts(alerts, 'custom', ['mykkp', 'policy']);
    assert.deepEqual(custom.slice(0, 2).map(item => item.id), ['mykkp', 'policy']);
    assert.equal(custom.length, alerts.length);
    assert.equal(alerts[0].id, 'policy');
    const categories = sortHomeAlerts(alerts, 'category').map(item => item.category);
    assert.deepEqual(categories, [...categories].sort((a, b) => a.localeCompare(b)));
});

test('verified personal accounts without an organization receive no organizational alerts', () => {
    assert.deepEqual(buildHomeAlerts({}, { organization: false, userVerified: true, mykkpStatus: 'approved' }, today), []);
    const alerts = buildHomeAlerts({}, { organization: false, userVerified: false, mykkpStatus: 'pending' }, today);
    assert.deepEqual(alerts.map(item => item.id), ['user-verification', 'mykkp']);
    assert.equal(alerts[1].title, 'MyKKP verification pending');
});

test('meeting reminders follow open calendar records and completed health controls disappear', () => {
    const alerts = buildHomeAlerts({ ...state, calendar: state.calendar.map(item => ({ ...item, status: 'Completed' })), hraRecords: [], assessments: { noise: { status: 'Completed' } } }, account, today);
    assert.ok(alerts.some(item => item.id === 'audit-meeting'));
    assert.ok(alerts.some(item => item.id === 'committee-meeting'));
    assert.ok(!alerts.some(item => item.id === 'assessment:noise'));
});

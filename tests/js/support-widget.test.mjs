// The support chat panel's script, run for real with a faked fetch and storage: new chat, history, opening and deleting a chat, and the
// formatting. Run with:  node --test tests/js     (this is not part of the PHP suite or CI)
import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

process.env.TZ = 'Africa/Nairobi';

const blade = fs.readFileSync(new URL('../../resources/views/components/support/widget.blade.php', import.meta.url), 'utf8');
const script = blade.slice(blade.indexOf('<script>') + 8, blade.lastIndexOf('</script>'));

function boot(fetchImpl, { footer = null } = {}) {
    const store = new Map();
    const calls = [];
    const listeners = [];  // every scroll/resize listener the panel registers, so a test can fire them
    const ctx = {
        console,
        innerHeight: 800,
        requestAnimationFrame: (fn) => fn(),
        addEventListener: (type, fn) => listeners.push([type, fn]),
        document: {
            querySelector: (selector) => (selector === '[data-app-footer]' ? footer : { content: 'csrf-token-123' }),
            addEventListener: (type, fn) => listeners.push([type, fn]),
        },
        sessionStorage: { getItem: (k) => store.get(k) ?? null, setItem: (k, v) => store.set(k, v) },
        fetch: async (url, options = {}) => { calls.push({ url, options }); return fetchImpl(url, options); },
        setTimeout,
    };
    ctx.window = ctx;
    ctx.matchMedia = () => ({ matches: false });
    vm.createContext(ctx);
    vm.runInContext(script, ctx);

    const chips = ['A?', 'B?', 'C?', 'D?'].map((label) => ({ label, key: 'ask' }));
    const data = ctx.window.supportChat({
        mode: 'dock', live: true, state: 'ready', userId: 29, transcript: [], input: '',
        endpoints: { list: '/support/conversations', show: '/support/conversations/__id__', destroy: '/support/conversations/__id__' },
        context: { greeting: 'Hi Kevin, how may we help you today?', cards: [], chips, label: 'Profile' },
    });
    data.$nextTick = (f) => f();
    data.$refs = {};
    data.messages = [data.greeting()];
    const fire = (type) => listeners.filter(([t]) => t === type).forEach(([, fn]) => fn());
    return { data, calls, store, ctx, listeners, fire };
}

// Objects made inside the sandbox have a different prototype from this file's, so compare their JSON form
const plain = (value) => JSON.parse(JSON.stringify(value));

const json = (body, status = 200) => ({ ok: status >= 200 && status < 300, status, json: async () => body });

const SAVED = [
    { id: 'c-today', title: 'Standard vs Extended licence?', updated_at: new Date(2026, 9, 6, 10, 16).toISOString() },
    { id: 'c-old', title: 'Can I get a refund?', updated_at: new Date(2026, 8, 20, 9, 0).toISOString() },
];

test('the panel opens on the greeting and the suggestions', () => {
    const { data } = boot(() => json({}));
    assert.equal(data.messages.length, 1);
    assert.equal(data.messages[0].text, 'Hi Kevin, how may we help you today?');
    assert.equal(data.messages[0].chips.length, 4);
    assert.equal(data.view, 'chat');
});

test('New chat goes back to the greeting and suggestions, forgets the open chat, and clears what the tab remembers', () => {
    const { data, store } = boot(() => json({}));
    data.conversationId = 'c-today';
    data.messages.push({ role: 'user', text: 'hello' }, { role: 'assistant', text: 'hi there', citations: [] });
    data.persist();
    assert.match(store.get('support.chat.v1.29'), /hi there/);

    data.newChat();
    assert.equal(data.conversationId, null);
    assert.equal(data.messages.length, 1);
    assert.equal(data.messages[0].chips.length, 4);
    assert.equal(JSON.parse(store.get('support.chat.v1.29')).turns.length, 0);
    data.restore();  // a reload now finds nothing to restore: it stays a fresh chat
    assert.equal(data.messages.length, 1);
});

test('New chat does nothing while an answer is still streaming', () => {
    const { data } = boot(() => json({}));
    data.conversationId = 'c-today';
    data.busy = true;
    data.newChat();
    assert.equal(data.conversationId, 'c-today');
});

test('History loads the chats with a plain GET and shows them grouped by day', async () => {
    const { data, calls } = boot(() => json({ conversations: SAVED }));
    await data.showHistory();

    assert.equal(data.view, 'history');
    assert.equal(data.chatsState, 'ready');
    assert.equal(calls[0].url, '/support/conversations');
    assert.equal(calls[0].options.method, undefined);  // a GET
    assert.equal(calls[0].options.credentials, 'same-origin');
    const labels = data.groups.map((g) => g.label);
    assert.ok(labels.includes('Earlier'));
});

test('History says so when the assistant is switched off, and when the list cannot be loaded', async () => {
    let boots = boot(() => json({}, 503));
    await boots.data.showHistory();
    assert.equal(boots.data.state, 'unavailable');

    boots = boot(() => { throw new Error('offline'); });
    await boots.data.showHistory();
    assert.equal(boots.data.chatsState, 'error');
    assert.equal(boots.data.chats.length, 0);
});

test('Opening an earlier chat shows its messages and citations, and the next message carries on in that chat', async () => {
    const chat = {
        id: 'c-today', title: 'Licences',
        messages: [
            { role: 'user', text: 'Standard vs Extended licence?', citations: [] },
            { role: 'assistant', text: 'Sure.', citations: [{ slug: 'licences', title: 'Licences', heading: 'Licences', updated: '3 Oct 2026' }] },
        ],
    };
    let next = json({ conversations: SAVED });
    const { data, calls } = boot((url) => (url.endsWith('/c-today') ? json(chat) : next));
    await data.showHistory();
    await data.openChat('c-today');

    assert.equal(calls[1].url, '/support/conversations/c-today');
    assert.equal(data.view, 'chat');
    assert.equal(data.conversationId, 'c-today');
    assert.equal(data.messages.length, 3);  // the greeting, then the two turns
    assert.equal(data.messages[0].chips.length, 0);  // no suggestions on a chat that is already under way
    assert.deepEqual(plain(data.messages[2].citations), [{ title: 'Licences', updated: '3 Oct 2026' }]);

    // sending now continues THAT conversation
    calls.length = 0;
    next = json({ error: { code: 'x' } }, 422);
    await data.sendLive('and refunds?');
    const sent = JSON.parse(calls[0].options.body);
    assert.equal(sent.conversation_id, 'c-today');
    assert.equal(sent.message, 'and refunds?');
});

test('Opening a chat that has gone drops it from the list and says so', async () => {
    const { data } = boot((url) => (url.endsWith('/c-old') ? json({ error: { code: 'conversation_not_found' } }, 404) : json({ conversations: SAVED })));
    await data.showHistory();
    await data.openChat('c-old');

    assert.equal(data.view, 'history');
    assert.deepEqual(plain(data.chats.map((c) => c.id)), ['c-today']);
    assert.match(data.notice, /isn't available/);
});

test('Opening a chat that fails to load leaves the member where they were', async () => {
    const { data } = boot((url) => (url.endsWith('/c-old') ? json({}, 500) : json({ conversations: SAVED })));
    await data.showHistory();
    data.conversationId = 'something-else';
    await data.openChat('c-old');

    assert.equal(data.view, 'history');
    assert.equal(data.conversationId, 'something-else');
    assert.match(data.notice, /Couldn't open/);
});

test('Deleting a chat sends a DELETE with the CSRF token and removes it from the list', async () => {
    const { data, calls } = boot((url, o) => (o.method === 'DELETE' ? json({}, 204) : json({ conversations: SAVED })));
    await data.showHistory();
    data.confirming = 'c-old';
    await data.archiveChat('c-old');

    const del = calls[1];
    assert.equal(del.url, '/support/conversations/c-old');
    assert.equal(del.options.method, 'DELETE');
    assert.equal(del.options.headers['X-CSRF-TOKEN'], 'csrf-token-123');
    assert.deepEqual(plain(data.chats.map((c) => c.id)), ['c-today']);
    assert.equal(data.confirming, null);
});

test('Deleting the chat that is open starts a clean chat; deleting another leaves the open one alone', async () => {
    const { data } = boot((url, o) => (o.method === 'DELETE' ? json({}, 204) : json({ conversations: SAVED })));
    await data.showHistory();
    data.conversationId = 'c-today';
    data.messages.push({ role: 'user', text: 'x' });

    await data.archiveChat('c-old');
    assert.equal(data.conversationId, 'c-today');
    assert.equal(data.messages.length, 2);

    await data.archiveChat('c-today');
    assert.equal(data.conversationId, null);
    assert.equal(data.messages.length, 1);
});

test('A failed delete keeps the chat in the list and says so', async () => {
    const { data } = boot((url, o) => (o.method === 'DELETE' ? json({}, 500) : json({ conversations: SAVED })));
    await data.showHistory();
    await data.archiveChat('c-old');

    assert.equal(data.chats.length, 2);
    assert.match(data.notice, /Couldn't delete/);
});

test('an id is never pasted raw into a URL', async () => {
    const { data, calls } = boot(() => json({}, 404));
    await data.openChat('../../v1/chat');
    assert.equal(calls[0].url, '/support/conversations/..%2F..%2Fv1%2Fchat');
});

test('chats are grouped Today / Yesterday / Previous 7 days / Earlier by calendar day, keeping their order', () => {
    const { ctx } = boot(() => json({}));
    const now = new Date(2026, 9, 6, 15, 0);
    const at = (d, h, m) => ({ id: `${d}-${h}:${m}`, updated_at: new Date(2026, 9, d, h, m).toISOString() });
    const chats = [at(6, 14, 0), at(6, 0, 1), at(5, 23, 59), at(1, 8, 0), { id: 'sep', updated_at: new Date(2026, 8, 20).toISOString() }];

    const groups = ctx.window.supportGroupChats(chats, now);
    assert.deepEqual(plain(groups.map((g) => [g.label, g.chats.map((c) => c.id)])), [
        ['Today', ['6-14:0', '6-0:1']],
        ['Yesterday', ['5-23:59']],
        ['Previous 7 days', ['1-8:0']],
        ['Earlier', ['sep']],
    ]);
    assert.deepEqual(plain(ctx.window.supportGroupChats([], now)), []);
});

test('the time shown is the clock time today and the date otherwise', () => {
    const { ctx } = boot(() => json({}));
    const now = new Date(2026, 9, 6, 15, 0);
    assert.equal(ctx.window.supportChatTime(new Date(2026, 9, 6, 10, 16).toISOString(), now), '10:16');
    assert.equal(ctx.window.supportChatTime(new Date(2026, 8, 20, 9, 0).toISOString(), now), '20 Sept');
    assert.match(ctx.window.supportChatTime(new Date(2025, 11, 31, 9, 0).toISOString(), now), /31 Dec 2025/);
    assert.equal(ctx.window.supportChatTime('not a date', now), '');
});

// ---- the Help button and the footer -------------------------------------------------------------------------------------------------------

/** A footer whose top edge is `top.value` pixels from the top of the screen. */
const footerAt = (top) => ({ value: top, getBoundingClientRect() { return { top: this.value }; } });

test('the Help button stays where it is while the footer is below the screen', () => {
    const { data } = boot(() => json({}), { footer: footerAt(1200) });
    data.followFooter();
    assert.equal(data.lift, 0);
});

test('the Help button rises by exactly the part of the footer that is on screen, and follows the scroll', () => {
    const footer = footerAt(720);  // 80px of footer showing on an 800px screen
    const { data, fire } = boot(() => json({}), { footer });
    data.followFooter();
    assert.equal(data.lift, 80);

    footer.value = 700;  // scrolled further: 100px showing
    fire('scroll');
    assert.equal(data.lift, 100);

    footer.value = 900;  // scrolled back up: footer off screen again, the button drops back down
    fire('scroll');
    assert.equal(data.lift, 0);
});

test('the Help button never rises more than half the screen, and re-measures when the window is resized', () => {
    const footer = footerAt(100);
    const { data, ctx, fire } = boot(() => json({}), { footer });
    data.followFooter();
    assert.equal(data.lift, 400);  // 700px of footer showing, capped at half of 800

    footer.value = 500;
    ctx.innerHeight = 600;
    fire('resize');
    assert.equal(data.lift, 100);
});

test('with no footer on the page the Help button is left alone and nothing is listened to', () => {
    const { data, listeners } = boot(() => json({}), { footer: null });
    data.followFooter();
    assert.equal(data.lift, 0);
    assert.equal(listeners.length, 0);
});


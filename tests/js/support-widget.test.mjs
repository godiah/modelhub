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
    const listeners = [];
    const timers = { ticks: [], cleared: [] };  // the panel's wait counter: a test fires the ticks itself  // every scroll/resize listener the panel registers, so a test can fire them
    const ctx = {
        console,
        TextDecoder,  // the panel decodes the answer's stream with it
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
        setInterval: (fn) => { timers.ticks.push(fn); return timers.ticks.length; },
        clearInterval: (id) => { timers.cleared.push(id); },
    };
    ctx.window = ctx;
    ctx.matchMedia = () => ({ matches: false });
    vm.createContext(ctx);
    vm.runInContext(script, ctx);

    const chips = ['A?', 'B?', 'C?', 'D?'].map((label) => ({ label, key: 'ask' }));
    const data = ctx.window.supportChat({
        mode: 'dock', live: true, state: 'ready', userId: 29, transcript: [], input: '',
        endpoints: { list: '/support/conversations', show: '/support/conversations/__id__', destroy: '/support/conversations/__id__', article: '/support/articles/__slug__', handoff: '/support/handoff' },
        context: { greeting: 'Hi Kevin, how may we help you today?', cards: [], chips, label: 'Profile' },
    });
    data.$nextTick = (f) => f();
    data.$refs = {};
    data.messages = [data.greeting()];
    const fire = (type) => listeners.filter(([t]) => t === type).forEach(([, fn]) => fn());
    return { data, calls, store, ctx, listeners, fire, timers };
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
            { role: 'assistant', text: 'Sure.', citations: [{ slug: 'licences', title: 'Licences', heading: 'Licences', updated: '3 Oct 2026', chunk_id: 'chunk-1' }] },
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
    assert.deepEqual(plain(data.messages[2].citations), [{ title: 'Licences', slug: 'licences', chunk_id: 'chunk-1' }]);  // enough to open the article; no date

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

// ---- sources: opening the article behind an answer ---------------------------------------------------------------------------------------------------

const ARTICLE = {
    slug: 'selling-a-model', title: 'Selling a model',
    sections: [
        { heading: 'Becoming a seller', level: 2, text: 'Apply on the Sell page.', cited: false },
        { heading: 'Review', level: 2, text: 'Staff publish the model or send it back.', cited: true },
    ],
};

test('a source opens its article with a plain GET carrying the passage to mark, and shows it', async () => {
    const { data, calls } = boot(() => json(ARTICLE));
    await data.openArticle({ title: 'Selling a model', slug: 'selling-a-model', chunk_id: 'chunk-1' });

    assert.equal(calls[0].url, '/support/articles/selling-a-model?chunk=chunk-1');
    assert.equal(calls[0].options.method, undefined);  // a GET
    assert.equal(data.view, 'article');
    assert.equal(data.articleState, 'ready');
    assert.equal(data.article.sections.filter((s) => s.cited).length, 1);
});

test('a source without a passage id opens the whole article, unmarked', async () => {
    const { data, calls } = boot(() => json(ARTICLE));
    await data.openArticle({ title: 'Selling a model', slug: 'selling-a-model' });
    assert.equal(calls[0].url, '/support/articles/selling-a-model');
});

test('an unknown source (no article id) does nothing, and the member stays in the chat', async () => {
    const { data, calls } = boot(() => json(ARTICLE));
    await data.openArticle({ title: 'Old chat source' });
    await data.openArticle(null);
    assert.equal(calls.length, 0);
    assert.equal(data.view, 'chat');
});

test('an article that is gone says so, and one that fails says so without switching the whole assistant off', async () => {
    let boots = boot(() => json({ error: { code: 'article_not_found' } }, 404));
    await boots.data.openArticle({ slug: 'gone' });
    assert.equal(boots.data.articleState, 'missing');

    boots = boot(() => json({ error: { code: 'assistant_unavailable' } }, 503));
    await boots.data.openArticle({ slug: 'selling-a-model' });
    assert.equal(boots.data.articleState, 'error');
    assert.equal(boots.data.state, 'ready');  // the panel is not turned into the "assistant unavailable" screen over one article

    boots = boot(() => { throw new Error('offline'); });
    await boots.data.openArticle({ slug: 'selling-a-model' });
    assert.equal(boots.data.articleState, 'error');
});

test('going back from an article returns to the conversation at the place the member was reading', async () => {
    const { data } = boot(() => json(ARTICLE));
    const scroller = { scrollTop: 340 };
    data.$refs = { scroller };
    await data.openArticle({ slug: 'selling-a-model' });
    scroller.scrollTop = 0;  // the hidden panel loses its place

    data.backToChat();
    assert.equal(data.view, 'chat');
    assert.equal(scroller.scrollTop, 340);
});

test('an article id is never pasted raw into the address', async () => {
    const { data, calls } = boot(() => json({}, 404));
    await data.openArticle({ slug: '../../v1/chat', chunk_id: 'a&b=c' });
    assert.equal(calls[0].url, '/support/articles/..%2F..%2Fv1%2Fchat?chunk=a%26b%3Dc');
});

test('a live answer keeps its sources with the article id and passage id, and no date', async () => {
    const sse = 'event: conversation\ndata: {"conversation_id": "c-9"}\n\n'
        + 'event: delta\ndata: {"text": "Staff review each model."}\n\n'
        + 'event: done\ndata: {"message_id": "m-1", "citations": [{"slug": "selling-a-model", "title": "Selling a model", "heading": "Selling a model > Review", "updated": "3 Oct 2026", "chunk_id": "chunk-7"}]}\n\n';
    const bytes = new TextEncoder().encode(sse);
    let sent = false;
    const response = { ok: true, status: 200, body: { getReader: () => ({ read: async () => (sent ? { done: true } : ((sent = true), { value: bytes, done: false })) }) } };
    const { data } = boot(() => response);
    await data.sendLive('How long does approval take?');

    const answer = data.messages.at(-1);
    assert.equal(answer.text, 'Staff review each model.');
    assert.deepEqual(plain(answer.citations), [{ title: 'Selling a model', slug: 'selling-a-model', chunk_id: 'chunk-7' }]);
});

// ---- the highlighted part of an article is narrowed to what the answer used ---------------------------------------------------------------

const PACKED = [
    { heading: 'Upload limits', level: 2, text: 'Up to 20 files of up to 50 MB each, up to 10 images, and up to 15 tags.', cited: true },
    { heading: 'Review', level: 2, text: 'Staff publish the model or send it back with a reason. A rejected model shows "Needs changes" with the reason.', cited: true },
    { heading: 'Your store name', level: 2, text: 'You can rename your store once every 30 days. The store address does not change.', cited: true },
    { heading: 'Becoming a seller', level: 2, text: 'Apply on the Sell page. Staff review each application.', cited: false },
];
const APPROVAL = 'Staff review models and can publish them or send them back with reasons. A rejected model shows "Needs changes" with the specific reasons.';

test('only the marked section the answer draws on stays highlighted', () => {
    const { ctx } = boot(() => json({}));
    const narrowed = plain(ctx.window.supportNarrowCited(PACKED, APPROVAL));
    assert.deepEqual(narrowed.map((s) => [s.heading, s.cited]), [['Upload limits', false], ['Review', true], ['Your store name', false], ['Becoming a seller', false]]);
});

test('a section the server did not mark is never marked, and a single marked section is left alone', () => {
    const { ctx } = boot(() => json({}));
    assert.equal(ctx.window.supportNarrowCited(PACKED, 'Apply on the Sell page. Staff review each application.')[3].cited, false);
    const one = [{ heading: 'A', text: 'x', cited: true }, { heading: 'B', text: 'y', cited: false }];
    assert.equal(ctx.window.supportNarrowCited(one, 'unrelated words'), one);
});

test('when the answer shares nothing with the marked sections the server marking is kept', () => {
    const { ctx } = boot(() => json({}));
    const narrowed = ctx.window.supportNarrowCited(PACKED, "I don't have that information.");
    assert.deepEqual(plain(narrowed.map((s) => s.cited)), [true, true, true, false]);
});

test('opening a source from an answer narrows the highlight using that answer', async () => {
    const { data } = boot(() => json({ slug: 'selling-a-model', title: 'Selling a model', sections: PACKED }));
    await data.openArticle({ slug: 'selling-a-model', chunk_id: 'chunk-1' }, APPROVAL);
    assert.deepEqual(plain(data.article.sections.filter((s) => s.cited).map((s) => s.heading)), ['Review']);
});

// ---- when the assistant says "wait" --------------------------------------------------------------------------------------------------------------

test('a 429 from the assistant shows its own words, hands the message back, and does not switch the whole panel off', async () => {
    const { data } = boot(() => json({ error: { code: 'busy', message: 'I am still answering your last message. Please wait for it to finish.' } }, 429));

    await data.sendLive('and my refund?');

    assert.equal(data.guard, 'I am still answering your last message. Please wait for it to finish.');
    assert.equal(data.input, 'and my refund?');               // the text is given back to send again
    assert.equal(data.state, 'ready');                        // the panel is not "unavailable"
    assert.equal(data.messages.some((m) => m.role === 'user'), false);  // the turn was taken back out (the greeting stays)
    assert.equal(data.busy, false);
});

test('a 429 that is not the assistant\'s own (the site\'s throttle) keeps the standard wording, never a raw server message', async () => {
    const { data } = boot(() => json({ message: 'Too Many Attempts.' }, 429));

    await data.sendLive('hello');

    assert.match(data.guard, /sending messages quickly/);
    assert.doesNotMatch(data.guard, /Too Many Attempts/);
    assert.equal(data.state, 'ready');
});

// ---- waiting for an answer -----------------------------------------------------------------------------------------------------------------------

test('the wait is counted while an answer is being prepared, the label changes when it runs long, and it stops when the answer fails', async () => {
    let finish;
    const gate = new Promise((resolve) => { finish = resolve; });
    const { data, timers } = boot(async () => { await gate; return json({ error: { code: 'x' } }, 422); });

    const pending = data.sendLive('How long does approval take?');
    assert.equal(data.busy, true);
    assert.equal(timers.ticks.length, 1);                      // a counter started
    assert.equal(data.waitingLabel, 'Thinking…');

    for (let i = 0; i < 9; i++) timers.ticks[0]();
    assert.equal(data.waitingLabel, 'Thinking…');  // 9 seconds: still the first message
    timers.ticks[0]();
    assert.match(data.waitingLabel, /Still thinking/);          // 10 seconds: say it can take a while

    finish();
    await pending;
    assert.equal(data.busy, false);
    assert.deepEqual(plain(timers.cleared), [1]);              // the counter was stopped...
    assert.equal(data.waited, 0);                              // ...and reset
});

test('a second question starts the wait again from zero rather than carrying the old count', async () => {
    const { data, timers } = boot(() => json({ error: { code: 'x' } }, 422));
    data.waited = 42;
    await data.sendLive('first');
    await data.sendLive('second');
    assert.equal(data.waited, 0);
    assert.equal(timers.ticks.length, 2);  // one counter per question
});


test('the secrets guard blocks a PIN or SMS code but lets an M-Pesa receipt through, so a payment can be looked up', () => {
    const { data } = boot(async () => ({ ok: true, json: async () => ({}) }));
    const blocked = (text) => { data.input = text; data.check(); return data.guard !== ''; };

    assert.equal(blocked('my pin is 4821'), true);
    assert.equal(blocked('the otp 482913'), true);
    assert.equal(blocked('4111 1111 1111 1111'), true);
    assert.equal(blocked('my receipt is QWE5678RTY and my pin is 4821'), true);  // a receipt does not hide a real PIN

    assert.equal(blocked('check payment with code QWE5678RTY'), false);
    assert.equal(blocked('my M-Pesa code is shk3x92lmn'), false);
    assert.equal(blocked('I paid Ksh1500 and it is stuck'), false);
});

// ---- "Talk to a person" -------------------------------------------------------------------------------------------------

const SUGGESTION = { categories: [{ value: 'payment_issue', label: 'A payment or licence' }, { value: 'other', label: 'Something else' }], category: 'payment_issue', summary: 'Payment MHX: Needs review.', aim: 'We aim to reply within 4 business hours.', existing: null };

test('Talk to a person asks ModelHub for a suggestion, for this chat, and shows a form to confirm', async () => {
    const { data, calls } = boot(() => json(SUGGESTION));
    data.conversationId = 'c-today';

    await data.talkToAPersonLive();

    assert.equal(calls[0].url, '/support/handoff?conversation_id=c-today');
    assert.equal(data.messages.at(-2).text, 'Talk to a person');
    const m = data.messages.at(-1);
    assert.equal(m.handoff.state, 'form');
    assert.equal(m.handoff.category, 'payment_issue');
    assert.equal(m.handoff.summary, 'Payment MHX: Needs review.');
    assert.equal(m.handoff.categories.length, 2);
});

test('with no chat yet it asks without a conversation id', async () => {
    const { data, calls } = boot(() => json({ ...SUGGESTION, category: 'other', summary: '' }));

    await data.talkToAPersonLive();

    assert.equal(calls[0].url, '/support/handoff');
    assert.equal(data.messages.at(-1).handoff.summary, '');
});

test('confirming files the request with only the category, the summary and the chat id, then shows the reference', async () => {
    const { data, calls } = boot((url, options) => (options.method === 'POST' ? json({ reference: 'SUP-1042', url: '/support/requests/SUP-1042', aim: 'We aim to reply within 4 business hours.', existing: false }, 201) : json(SUGGESTION)));
    data.conversationId = 'c-today';
    await data.talkToAPersonLive();
    const m = data.messages.at(-1);
    m.handoff.summary = '  My payment never gave me a licence  ';
    m.handoff.category = 'other';

    await data.confirmHandoff(m);

    const post = calls.find((c) => c.options.method === 'POST');
    assert.deepEqual(plain(JSON.parse(post.options.body)), { conversation_id: 'c-today', category: 'other', summary: 'My payment never gave me a licence' });
    assert.equal(post.options.headers['X-CSRF-TOKEN'], 'csrf-token-123');
    assert.equal(m.handoff.state, 'done');
    assert.equal(m.handoff.reference, 'SUP-1042');
    assert.equal(m.handoff.url, '/support/requests/SUP-1042');
});

test('a summary that is too short is not sent', async () => {
    const { data, calls } = boot(() => json(SUGGESTION));
    await data.talkToAPersonLive();
    const m = data.messages.at(-1);
    m.handoff.summary = 'hi';

    await data.confirmHandoff(m);

    assert.equal(calls.filter((c) => c.options.method === 'POST').length, 0);
    assert.match(m.handoff.error, /Tell us a little/);
    assert.equal(m.handoff.state, 'form');
});

test('a refusal from ModelHub is shown in its own words and the form stays, so nothing typed is lost', async () => {
    const { data } = boot((url, options) => (options.method === 'POST' ? json({ error: { code: 'ticket_refused', message: 'You already have several open requests.' } }, 422) : json(SUGGESTION)));
    await data.talkToAPersonLive();
    const m = data.messages.at(-1);
    m.handoff.summary = 'Something I typed carefully';

    await data.confirmHandoff(m);

    assert.equal(m.handoff.state, 'form');
    assert.equal(m.handoff.error, 'You already have several open requests.');
    assert.equal(m.handoff.summary, 'Something I typed carefully');
    assert.equal(m.handoff.sending, false);
});

test('a lost connection while sending keeps the form and says so', async () => {
    const { data } = boot((url, options) => { if (options.method === 'POST') throw new Error('offline'); return json(SUGGESTION); });
    await data.talkToAPersonLive();
    const m = data.messages.at(-1);

    await data.confirmHandoff(m);

    assert.equal(m.handoff.state, 'form');
    assert.match(m.handoff.error, /couldn't reach ModelHub/);
    assert.equal(m.handoff.sending, false);
});

test('sending twice quickly files it once', async () => {
    const { data, calls } = boot((url, options) => (options.method === 'POST' ? new Promise((r) => setTimeout(() => r(json({ reference: 'SUP-1', url: '/u', aim: '' }, 201)), 20)) : json(SUGGESTION)));
    await data.talkToAPersonLive();
    const m = data.messages.at(-1);

    await Promise.all([data.confirmHandoff(m), data.confirmHandoff(m)]);

    assert.equal(calls.filter((c) => c.options.method === 'POST').length, 1);
});

test('when this chat already has a request it points at it instead of asking again', async () => {
    const { data } = boot(() => json({ ...SUGGESTION, existing: { reference: 'SUP-1042', url: '/support/requests/SUP-1042' } }));
    data.conversationId = 'c-today';

    await data.talkToAPersonLive();

    const m = data.messages.at(-1);
    assert.equal(m.handoff.state, 'existing');
    assert.equal(m.handoff.reference, 'SUP-1042');
});

test('cancelling sends nothing and says so', async () => {
    const { data, calls } = boot(() => json(SUGGESTION));
    await data.talkToAPersonLive();
    const m = data.messages.at(-1);

    data.cancelHandoff(m);

    assert.equal(m.handoff, null);
    assert.match(m.text, /nothing was sent/);
    assert.equal(calls.filter((c) => c.options.method === 'POST').length, 0);
});

test('when ModelHub cannot prepare it, the member is given the email address as before', async () => {
    const { data } = boot(() => json({}, 500));

    await data.talkToAPersonLive();

    const m = data.messages.at(-1);
    assert.equal(m.handoff, null);
    assert.match(m.text, /couldn't set that up/);
});

test('the Talk to a person button and the card buttons open the same flow', async () => {
    const { data, calls } = boot(() => json(SUGGESTION));

    data.run('human');
    await new Promise((r) => setTimeout(r, 0));

    assert.equal(calls[0].url, '/support/handoff');
});

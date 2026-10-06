// The panel's text renderer: the assistant's light Markdown becomes safe HTML. Run with:  node --test tests/js/support-render.test.mjs
// (not part of the PHP suite or CI). It runs the real function out of the widget's Blade file.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const blade = fs.readFileSync(new URL('../../resources/views/components/support/widget.blade.php', import.meta.url), 'utf8');
const a = blade.indexOf('window.supportRender = function');
const b = blade.indexOf('// end supportRender');
assert.ok(a > 0 && b > a, 'renderer not found in the widget');
const ctx = { window: {} };
vm.createContext(ctx);
vm.runInContext(blade.slice(a, b), ctx);
const render = ctx.window.supportRender;
const count = (html, re) => (html.match(re) || []).length;

// ---- the replies that exposed bugs --------------------------------------------------------------------------------

// A reply Kevin got: numbered items, bullets indented under each, a blank line between items. Every number showed as "1".
const NUMBERED_WITH_BULLETS = `ModelHub is a marketplace where buyers can hire freelancers for 3D work. Here’s how it generally works:

1. **Hiring Freelancers:**
   - Buyers post job listings for 3D work.
   - Freelancers can bid on these jobs.
   - Buyers select a freelancer and agree on the terms.
   - Payments are made through M-Pesa, with ModelHub taking a commission.

2. **Buying Models:**
   - Buyers browse and purchase 3D models listed by freelancers.
   - Payments are made through M-Pesa, with ModelHub taking a commission.

3. **Commission:**
   - For model sales, ModelHub keeps 15% of the sale price.
   - For projects, ModelHub keeps 10% of the agreed amount.
   - Some freelancers may have their own agreed rates.

4. **Payment:**
   - Payments are made in Kenyan Shillings (Ksh) through M-Pesa.
   - ModelHub does not reverse M-Pesa transfers to the wrong number; contact Safaricom support as well.

If you have specific questions or need more details, you can press "Talk to a person" to speak with a staff member.`;

test('numbered items with bullets under them and blank lines between them stay ONE numbered list (1, 2, 3, 4)', () => {
    const h = render(NUMBERED_WITH_BULLETS);
    assert.equal(count(h, /<ol[ >]/g), 1, h);                 // one list, not four lists that each start at 1
    assert.equal(count(h, /<ol[^>]*start=/g), 0);             // it starts at 1, so no start attribute is needed
    assert.equal(count(h, /<ul /g), 4);                       // each item has its own nested bullet list
    assert.equal(count(h, /<li /g), 4 + 11);                  // 4 numbered items + their 11 bullets
    assert.ok(h.indexOf('<strong>Hiring Freelancers:</strong>') < h.indexOf('<strong>Buying Models:</strong>'));
    assert.ok(!h.includes('**'));
    // the bullets sit INSIDE their numbered item, not after it
    assert.match(h, /<li [^>]*><strong>Hiring Freelancers:<\/strong><ul /);
    // the closing sentence is an ordinary paragraph after the list
    assert.match(h, /<\/ol><p [^>]*>If you have specific questions/);
});

test('a list that starts at another number keeps its number (a reply that begins "3.")', () => {
    const h = render('3. Third step\n4. Fourth step');
    assert.match(h, /<ol start="3"/);
    assert.equal(count(h, /<li /g), 2);
});

test('numbered items separated by blank lines are still one list', () => {
    const h = render('1. First\n\n2. Second\n\n3. Third');
    assert.equal(count(h, /<ol[ >]/g), 1);
    assert.equal(count(h, /<li /g), 3);
});

test('a numbered list followed by a paragraph and then a new list is two lists', () => {
    const h = render('1. One\n2. Two\n\nSome words in between.\n\n1. Again one\n2. Again two');
    assert.equal(count(h, /<ol[ >]/g), 2);
    assert.match(h, /<\/ol><p [^>]*>Some words in between\.<\/p><ol/);
});

test('bullets nested two levels deep, and a change of list kind at the same level', () => {
    const deep = render('- Top\n  - Middle\n    - Bottom\n- Next top');
    assert.equal(count(deep, /<ul /g), 3);
    assert.equal(count(deep, /<li /g), 4);

    const mixed = render('- a bullet\n1. a number');
    assert.equal(count(mixed, /<ul /g), 1);
    assert.equal(count(mixed, /<ol[ >]/g), 1);
});

test('an indented line under an item carries on that item', () => {
    const h = render('1. First step\n   with more detail\n2. Second step');
    assert.equal(count(h, /<ol[ >]/g), 1);
    assert.match(h, /First step<br>with more detail<\/li>/);
});

// ---- flat bold labels, as the model sometimes writes them ----------------------------------------------------------------------------------

const FLAT = `Sure, here's the difference between Standard and Extended licences:

- **Standard Licence:**
- Allows one end product.
- Commercial use is permitted.

- **Extended Licence:**
- Allows any number of end products.
- You can share the model with up to 10 team members.

Anything else?`;

test('flat bold-label bullets become sub-headings with their bullets under them', () => {
    const h = render(FLAT);
    assert.ok(!h.includes('**'));
    assert.ok(h.includes('<strong>Standard Licence:</strong>') && h.includes('<strong>Extended Licence:</strong>'));
    assert.equal(count(h, /<ul /g), 2);
    assert.equal(count(h, /<li /g), 4);
    assert.match(h, /Anything else\?<\/p>$/);
});

// ---- formatting ---------------------------------------------------------------------------------------------------------------------------

test('bold, italics, code and underlined links', () => {
    const h = render('Use **bold**, *italic*, _also italic_, `code **not bold**` and [a link](https://example.com/a?b=1&c=2).');
    assert.ok(h.includes('<strong>bold</strong>') && h.includes('<em>italic</em>') && h.includes('<em>also italic</em>'));
    assert.ok(h.includes('<code '));
    assert.ok(h.includes('href="https://example.com/a?b=1&amp;c=2"') && h.includes('text-decoration:underline') && h.includes('rel="noopener noreferrer"'));
});

test('snake_case words and arithmetic are not turned into italics', () => {
    const h = render('A snake_case_word and 5 * 3 * 2 stay as they are.');
    assert.ok(h.includes('snake_case_word') && h.includes('5 * 3 * 2') && !h.includes('<em>'));
});

test('a heading is a bold line, and newlines inside a paragraph become line breaks', () => {
    const h = render('## Fees\nA line\nsecond line');
    assert.ok(h.startsWith('<p style="margin:0 0 .5rem"><strong>Fees</strong></p>'));
    assert.ok(h.includes('A line<br>second line'));
});

test('half-written markup while streaming is shown plainly and does not break', () => {
    const h = render('Half written **bold and no end');
    assert.ok(h.includes('**bold and no end') && !h.includes('<strong>'));
    assert.doesNotThrow(() => render('1. one\n   - nested, cut off mid-'));
});

test('empty and missing text', () => {
    assert.equal(render(''), '');
    assert.equal(render(null), '');
    assert.equal(render(undefined), '');
});

// ---- nothing the model writes can inject markup -------------------------------------------------------------------------------------------

test('HTML and script tags are escaped, including inside lists and bold', () => {
    const h = render('<script>alert(1)</script> and <img src=x onerror=alert(1)> and **<b>x</b>**\n- <svg onload=alert(1)>\n  - <iframe src=x>');
    assert.ok(!/<script|<img|<svg|<iframe|<b>/.test(h), h);
    assert.ok(h.includes('&lt;script&gt;') && h.includes('&lt;img') && h.includes('&lt;svg') && h.includes('&lt;iframe'));
});

test('only http and https links become links, and a quote cannot break out of the address', () => {
    const h = render('[a](javascript:alert(1)) [b](data:text/html,<script>) [c](https://a.com/"onmouseover="alert(1))');
    assert.ok(!/href="javascript|href="data/.test(h), h);
    assert.ok(!/onmouseover="/.test(h), h);
});

test('apostrophes and quotes are escaped once', () => {
    assert.equal(render(`it's "fine" & ok`), `<p style="margin:0 0 .5rem">it&#39;s &quot;fine&quot; &amp; ok</p>`);
});

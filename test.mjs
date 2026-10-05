import assert from 'node:assert/strict';
import { access, readFile } from 'node:fs/promises';

const html = await readFile(new URL('./index.html', import.meta.url), 'utf8');

await access(new URL('./assets/golden-jubilee-logo.png', import.meta.url));
await access(new URL('./assets/ieor-building.jpg', import.meta.url));
await access(new URL('./alumni-meet/index.html', import.meta.url));

assert.match(html, /<link rel="canonical" href="https:\/\/www\.ieor\.iitb\.ac\.in\/golden-jubilee\/">/);
assert.doesNotMatch(html, /ieor-golden-jubilee\.vercel\.app/);
assert.match(html, /id="jubilee-title">What the Jubilee is for\.<\/h2>/);
assert.match(html, /<dt>2076<\/dt><dd>A century of IEOR<\/dd>/);
assert.match(html, /21 and 22 November 2026/);
assert.doesNotMatch(html, /class="hero-kicker"|class="hero-monument"|class="date-rail"/);
assert.match(html, /<span class="line">Fifty years<\/span>/);
assert.doesNotMatch(html, /28(?:–29| and 29) November 2026/);
assert.match(html, /href="\/alumni-meet\/"[^>]*target="_blank"/);
assert.match(html, /href="\/golden-reveal\/"/);
assert.match(html, /programme-status">Held/);
await access(new URL('./golden-reveal/index.html', import.meta.url));
await access(new URL('./assets/golden-reveal/mark-unveiled.jpg', import.meta.url));
const reveal = await readFile(new URL('./golden-reveal/index.html', import.meta.url), 'utf8');
assert.match(reveal, /The mark is out\./);
assert.match(reveal, /3 September 2026/);
assert.doesNotMatch(reveal, /—|–/);
assert.match(html, /Indian Registration/);
assert.match(html, /International Registration/);
assert.doesNotMatch(html, /Get the invite|Join the updates list|fetch\(['"]api\//);

const schedule = await readFile(new URL('./alumni-meet/index.html', import.meta.url), 'utf8');
assert.match(schedule, /Saturday/);
assert.match(schedule, /Alumni-Faculty Presentations/);
assert.match(schedule, /Gala Dinner &amp; Cultural Evening/);
assert.match(schedule, /Alumni-Student Interaction Session/);

console.log('ok: static Golden Jubilee page, Alumni Meet schedule, and official registration links');

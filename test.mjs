import assert from 'node:assert/strict';
import { access, readFile } from 'node:fs/promises';

const html = await readFile(new URL('./index.html', import.meta.url), 'utf8');

await access(new URL('./assets/golden-jubilee-logo.webp', import.meta.url));
await access(new URL('./alumni-meet.ics', import.meta.url));
await access(new URL('./alumni-meet/index.html', import.meta.url));

assert.match(html, /<link rel="canonical" href="https:\/\/ieor\.iitb\.ac\.in\/golden-jubilee\/">/);
assert.doesNotMatch(html, /ieor-golden-jubilee\.vercel\.app/);
assert.match(html, /id="jubilee-title">What the Jubilee is for\.<\/h2>/);
assert.match(html, /<dt>2076<\/dt><dd>A century of IEOR<\/dd>/);
assert.match(html, /21 and 22 November 2026/);
assert.doesNotMatch(html, /class="hero-kicker"|class="hero-monument"|class="date-rail"/);
assert.match(html, /<span class="line">Fifty years<\/span>/);
assert.doesNotMatch(html, /28(?:–29| and 29) November 2026/);
assert.match(html, /href="alumni-meet\/"[^>]*target="_blank"/);
assert.match(html, /href="golden-reveal\/"/);
assert.match(html, /programme-status">Held/);
await access(new URL('./golden-reveal/index.html', import.meta.url));
await access(new URL('./assets/golden-reveal/mark-unveiled.webp', import.meta.url));
const reveal = await readFile(new URL('./golden-reveal/index.html', import.meta.url), 'utf8');
assert.match(reveal, /The mark is out\./);
assert.match(reveal, /3 September 2026/);

assert.match(html, /Indian Registration/);
assert.match(html, /International Registration/);
assert.doesNotMatch(html, /Get the invite|Join the updates list|fetch\(['"]api\//);

const schedule = await readFile(new URL('./alumni-meet/index.html', import.meta.url), 'utf8');
assert.match(schedule, /Saturday/);
assert.match(schedule, /Alumni-Faculty Presentations/);
assert.match(schedule, /Gala Dinner &amp; Cultural Evening/);
assert.match(schedule, /Alumni-Student Interaction Session/);

// Registration is reachable from the first screen and the mobile menu.
const hero = html.slice(html.indexOf('class="hero shell"'), html.indexOf('class="seen shell"'));
assert.match(hero, /ieor-alumni-meet-2026"/);
assert.match(hero, /ieor-alumni-meet-2026-usd\/"/);
assert.match(html, /id="navLinks">[\s\S]*?href="#register"[\s\S]*?<\/div>/);
// Content stays visible without JS: hidden states only apply under .js.
assert.doesNotMatch(html, /^\s*\.reveal \{/m);
assert.doesNotMatch(html, /gsap/);
assert.match(html, /"@type": "Event"/);

for (const [name, page] of [['index', html], ['golden-reveal', reveal], ['alumni-meet', schedule]]) {
  assert.doesNotMatch(page, /—|–/, `${name}: no em or en dashes`);
  // Every on-page photo exists and ships as WebP.
  for (const [, src] of page.matchAll(/<img[^>]*src="([^"]+)"/g)) {
    assert.match(src, /\.webp$/, `${name}: ${src} should be WebP`);
    await access(new URL(src.replace(/^\.\.\//, './'), import.meta.url));
  }
}

console.log('ok: pages, schedule, registration entry points, WebP assets, no dashes');

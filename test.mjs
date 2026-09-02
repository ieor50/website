import assert from 'node:assert/strict';
import { access, readFile } from 'node:fs/promises';

const html = await readFile(new URL('./index.html', import.meta.url), 'utf8');

await access(new URL('./assets/golden-jubilee-logo.png', import.meta.url));
await access(new URL('./assets/ieor-building.jpg', import.meta.url));
await access(new URL('./api/subscribe.js', import.meta.url));

assert.match(html, /fetch\('\/api\/subscribe'/);
assert.match(html, /<link rel="canonical" href="https:\/\/gj\.theharshitsingh\.com\/">/);
assert.doesNotMatch(html, /ieor-golden-jubilee\.vercel\.app/);
assert.match(html, /id="jubilee-title">What the Jubilee is for\.<\/h2>/);
assert.match(html, /<dt>2076<\/dt><dd>A century of IEOR<\/dd>/);
assert.doesNotMatch(html, /data-review-id|review\.js|reviewToken|localReview|\/api\/review/);

console.log('ok — production page, assets, signup API, and review-code exclusion');

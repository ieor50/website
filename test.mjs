import assert from 'node:assert/strict';
import { access, readFile } from 'node:fs/promises';

const html = await readFile(new URL('./index.html', import.meta.url), 'utf8');

await access(new URL('./assets/golden-jubilee-logo.png', import.meta.url));
await access(new URL('./assets/ieor-building.jpg', import.meta.url));
await access(new URL('./api/subscribe.php', import.meta.url));
await access(new URL('./api/register.php', import.meta.url));
await access(new URL('./api/export.php', import.meta.url));
await access(new URL('./lib/storage.php', import.meta.url));
await access(new URL('./data/.htaccess', import.meta.url));

assert.match(html, /fetch\('api\/subscribe\.php'/);
assert.match(html, /<link rel="canonical" href="https:\/\/www\.ieor\.iitb\.ac\.in\/golden-jubilee\/">/);
assert.doesNotMatch(html, /ieor-golden-jubilee\.vercel\.app/);
assert.match(html, /id="jubilee-title">What the Jubilee is for\.<\/h2>/);
assert.match(html, /<dt>2076<\/dt><dd>A century of IEOR<\/dd>/);
assert.match(html, /21 and 22 November 2026/);
assert.doesNotMatch(html, /28(?:–29| and 29) November 2026/);
assert.doesNotMatch(html, /data-review-id|review\.js|reviewToken|localReview|\/api\/review/);

const registration = await readFile(new URL('./register.html', import.meta.url), 'utf8');
assert.match(registration, /fetch\('api\/register\.php'/);
assert.match(registration, /name="consent"/);
assert.doesNotMatch(registration, /Preview complete/);

const config = await readFile(new URL('./lib/config.php', import.meta.url), 'utf8');
assert.doesNotMatch(config, /REPLACE_WITH_/);
assert.doesNotMatch(config, /GJ_PRIVATE_KEY/);

const ignore = await readFile(new URL('./.gitignore', import.meta.url), 'utf8');
assert.match(ignore, /data\/\*/);

console.log('ok: production pages, encrypted PHP APIs, protected data, and review code exclusion');

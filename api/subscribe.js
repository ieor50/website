import { put, head } from '@vercel/blob';
import { createHash } from 'node:crypto';

const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const BATCH = /^(19|20)\d{2}$/;

export default async function handler(req, res) {
  if (req.method !== 'POST') {
    res.setHeader('Allow', 'POST');
    return res.status(405).json({ error: 'Method not allowed.' });
  }

  const body = typeof req.body === 'string' ? safeParse(req.body) : (req.body || {});
  const email = String(body.email || '').trim().toLowerCase();
  const batch = String(body.batch || '').trim();

  if (!EMAIL.test(email) || email.length > 254) {
    return res.status(400).json({ error: 'That email does not look right.' });
  }
  if (batch && !BATCH.test(batch)) {
    return res.status(400).json({ error: 'Batch year should be four digits.' });
  }

  // One blob per address, keyed by a hash of it, so a resubmission overwrites
  // rather than racing against a shared list.
  const key = `signups/${createHash('sha256').update(email).digest('hex').slice(0, 32)}.json`;

  let duplicate = false;
  try {
    await head(key);
    duplicate = true;
  } catch {
    // not found, which is the normal path for a new signup
  }

  try {
    await put(key, JSON.stringify({ email, batch: batch || null, at: new Date().toISOString() }), {
      access: 'private',
      contentType: 'application/json',
      addRandomSuffix: false,
      allowOverwrite: true,
    });
  } catch (err) {
    console.error('blob put failed', err);
    return res.status(502).json({ error: 'We could not save that just now.' });
  }

  return res.status(200).json({ ok: true, duplicate });
}

function safeParse(s) {
  try { return JSON.parse(s); } catch { return {}; }
}

# IEOR Golden Jubilee website

Production website for the Department of Industrial Engineering and Operations Research,
IIT Bombay Golden Jubilee.

The homepage is plain HTML, CSS, and JavaScript. The only server-side component is
`api/subscribe.js`, which stores updates-list signups in a private Vercel Blob store.

## Deploy

Deploy this repository on Vercel and provide `BLOB_READ_WRITE_TOKEN` in the production
environment. The production URL is `https://work.hsbhandari.dev/ieor-gj/` (served behind
a path proxy; the origin still serves the same files at its own root).

## Verify

```sh
npm install
npm test
```

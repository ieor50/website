# IEOR Golden Jubilee website

Production website for the Department of Industrial Engineering and Operations Research,
IIT Bombay Golden Jubilee.

The pages are plain HTML, CSS, and JavaScript. PHP endpoints store updates list signups and
Grand Alumni Meet registrations as encrypted, append only records under `data/`.

## Storage security

The server contains only a Sodium public key. The matching private key and the export token
are held locally in macOS Keychain and `~/.private/gj.env/secrets.env`. A server compromise
or accidental static file exposure therefore does not reveal submitted personal data.

Records are PHP guarded files as well as encrypted. `data/.htaccess` denies HTTP access and
disables directory listing. Apache needs write access to `data/`, but no source file should
be writable by Apache.

## First deployment

1. Deploy this repository as the `golden-jubilee` folder in the IEOR web root.
2. Confirm PHP 8.1 or newer with Sodium and mbstring enabled.
3. Preserve `golden-jubilee/data/` across every later deployment.
4. Give the Apache user write access to that directory. This is the same one time action
   used by Next Fifty:

   ```sh
   cd <webroot>/golden-jubilee
   mkdir -p data
   sudo chown -R www-data:www-data data
   sudo find data -type d -exec chmod 0750 {} \;
   sudo find data -type f -exec chmod 0640 {} \;
   ```

5. Open `/golden-jubilee/data/` and confirm it returns 403.
6. Submit one test subscription and one test registration, then export and decrypt them.

Do not use a deployment command that deletes `data/`. Copy the application files into the
existing directory or exclude `data/` explicitly.

## Export

From this repository on Harshit's Mac:

```sh
./scripts/export-data.sh https://www.ieor.iitb.ac.in/golden-jubilee ~/.private/gj-exports
```

The command reads secrets from Keychain, downloads ciphertext, decrypts locally, and writes
a subscriptions CSV plus a registrations JSON file with mode `0600`.

`scripts/setup-secrets.sh` is idempotent. It restores existing secrets to Keychain when the
local secrets file already exists. Do not run it after deleting that file unless rotating
the deployed public key intentionally.

## Verify

```sh
npm install
npm test
php -l api/subscribe.php
php -l api/register.php
php -l api/export.php
```

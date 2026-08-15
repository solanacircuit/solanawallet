INSTALLATION GUIDE:
## Deploy to cPanel

We provide free installation if you want us to install on cpanel. Just connect with us on WhatsApp / telegram / email from https://solanacircuit.com/contact/

Else follow below steps and you can install it by yourself.

Deploy to cPanel:

### Step 1 — Upload to cPanel
Upload the folder 'main.zip' Using File Manager or FTP to your domains or subdomain's document root (e.g. 'public_html' for main domain OR if you are uploading on subdomain say 'app.yourdomain.com' the folder for `app.solanacircuit.com`):

When done, the folder structure on cPanel should look like:
```
public_html/app/
├── index.html          ← from dist/
├── assets/              ← from dist/
├── favicon.svg, icons.svg, brand-logo.svg  ← from dist/
└── api/
    ├── _bootstrap.php
    ├── public-config.php
    ├── track.php
    ├── admin/...
    ├── lib/...
    └── data/            ← must be writable by PHP (see below)
```

### Step 2 — cPanel setup (one-time)
1. PHP version: in cPanel → MultiPHP Manager, set the subdomain to PHP 8.1 or newer.
2. Writable data folder: `api/data/` must be writable by PHP (755 folders / 644 files). If you get a "failed to write" error later, set `api/data` to 755 and retry.
3. SSL: turn on AutoSSL for the subdomain (cPanel → SSL/TLS Status) so it serves over HTTPS. Not strictly required for the app to function, but recommended before real wallets are used.

### Step 3 — Start clean (important, do this before going live)
1. visit to `https://yourdoamin.com/#admin`
Should show 'Create Admin Account'. It will run some commands and show a password. Note down password. It will not show again.
2. Immediately log into `/#admin` and: change the admin username/password (Change Admin Credentials panel).

### Step 4 — Verify
1. Visit your domain — should show the Connect Wallet screen.
2. Visit `yourdomain/#admin`
3. Log in and set: Branding, RPC endpoint, Theme, Social Links, etc. — same admin panel as localhost.

IMPORTANT:
RPC endpoint is very much required. Without this app will not work. You can get free RPC endpoint for solana chain from https://www.helius.dev/


If any confusion / queries, you can always connect with us: 

https://solanacircuit/contact
Gmail: solanacircuit@gmail.com

WhatsApp: https://wa.me/917820980936

Telegram: https://t.me/solanacircuit

Discord: https://discord.gg/EEjEQZT2j

Instagram: https://instagram.com/solanacircuit

Facebook: https://www.facebook.com/profile.php?id=61592858924212

Youtube: https://youtube.com/@SolanaCircuitOfficial


Facebook: social@solanacircuit.com

Youtube: @SolanaCircuitOfficial


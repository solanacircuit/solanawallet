# solanawallet
The Solana terminal for people who move fast on-chain.  Send, swap, bridge, stake and manage NFTs from one battle-tested wallet terminal. Your keys never leave your device — use the hosted app instantly, or download it and run your own branded instance. 

INSTALLATION GUIDE:
## Deploy to cPanel

Step 1 — Upload to cPanel
Using File Manager or FTP, upload to your main domain or subdomain's document root (e.g. the folder for `public_html` OR `app.solanacircuit.com`):

| From your machine | To (on cPanel, same folder) |
|---|---|
| everything **inside** `upload/` (not the `upload` folder itself) | 

When done, the folder structure on cPanel should look like:
```
public_html/
├── index.html          
├── assets/             
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
1. **PHP version**: in cPanel → *MultiPHP Manager*, set the subdomain to **PHP 8.1 or newer**.
2. **Writable data folder**: `api/data/` must be writable by PHP — default cPanel upload permissions (755 folders / 644 files, owned by your cPanel user) already work; no need to `chmod 777`. If you get a "failed to write" error later, set `api/data` to 755 and retry.
3. **SSL**: turn on AutoSSL for the subdomain (cPanel → *SSL/TLS Status*) so it serves over HTTPS. Not strictly required for the app to function, but recommended before real wallets are used.

### Step 3 — Start clean (important, do this before going live)
The copy of `api/data/` on your development machine right now contains **test data** from building/testing this app (a test admin login, test analytics, test activity log).

### Step 4 — Verify
1. Visit your domain — should show the Connect Wallet screen.
2. Visit `yourdomain/#admin` — 
Username: 
Password: 
3. Log in and set: Branding, RPC endpoint, Theme, Social Links, etc. — same admin panel as localhost.

If any confusion / queries, you can always connect with us: 

https://solanacircuit/contact
Gmail: solanacircuit@gmail.com

WhatsApp: +917820980936

Telegram: @solanacircuit

Discord:solanacircuit

Instagram:solanacircuit

Facebook: social@solanacircuit.com

Youtube: @SolanaCircuitOfficial




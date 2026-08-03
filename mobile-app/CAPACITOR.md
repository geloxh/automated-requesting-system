## ARS Mobile App (Capacitor / Android)

This wraps the existing ARS web app in a real native Android shell using
[Capacitor](https://capacitorjs.com). It does **not** duplicate or rebuild any app logic —
the native app is a thin WebView that loads your live server, the same way a browser tab
does. All PHP code, routes, and the database are untouched.

Lives in `mobile-app/` alongside the main PHP project.

---

### ⚠️ Before you build: the certificate requirement

This is the one thing that will silently break if skipped.

A desktop/mobile **browser** lets you click through a self-signed certificate warning
("Advanced → Proceed anyway"). Android's native **WebView does not offer that option** —
if `server.url` in `capacitor.config.ts` points at a server with a self-signed cert (which
is what `docker/Dockerfile` currently bakes in), the app will just fail to load with no way
for the user to bypass it.

**You need a real, trusted HTTPS certificate before this app will work.** The easiest path,
since you're already using Tailscale (see `../TAILSCALE.md`):

1. Admin console → **HTTPS Certificates** → enable.
2. On the Docker host: `tailscale cert ars-server.your-tailnet.ts.net`
3. Mount that cert into the container in place of the self-signed one — this is already
   wired up (commented out) in `docker-compose.yml`; see `../docker/certs/README.md` for
   the exact steps.

Once that's done, point `capacitor.config.ts` at `https://ars-server.your-tailnet.ts.net`
and the app will load exactly like a normal website — no cert errors, no native code changes.

(A publicly-issued Let's Encrypt cert on a real domain works too, if you go that route
instead of Tailscale — same requirement either way: it just has to be trusted, not self-signed.)

---

### Project structure

```
mobile-app/
├── capacitor.config.ts    ← set your server URL here (see warning above)
├── package.json
├── assets/                ← source icon/splash images (edit these, then regenerate)
│   ├── icon-only.png          (1024×1024 master icon)
│   ├── icon-foreground.png    (Android adaptive icon — glyph layer)
│   ├── icon-background.png    (Android adaptive icon — background layer)
│   ├── splash.png              (2732×2732 launch screen)
│   └── splash-dark.png
├── www/                   ← minimal fallback page only; real content loads from your server
└── android/                ← generated native Android Studio project
```

---

### One-time setup (on your own machine)

You'll need:
- [Node.js](https://nodejs.org) (already used to scaffold this)
- [Android Studio](https://developer.android.com/studio) (includes the Android SDK)

```bash
cd mobile-app
npm install
```

Edit `capacitor.config.ts` and replace the placeholder `ARS_SERVER_URL` with your real,
trusted-HTTPS address.

If you ever change the app's icon/splash, replace the files in `assets/` and regenerate:

```bash
npx capacitor-assets generate --android
npx cap sync android
```

---

### Building & running

```bash
# Opens the project in Android Studio
npx cap open android
```

From Android Studio: **Run ▶** to install on a connected device/emulator, or
**Build → Generate Signed Bundle/APK** to produce a real installer.

- **Sideload only** (install the `.apk` directly on your own staff's phones, no store needed):
  no developer account required, just share the `.apk` file.
- **Google Play listing:** requires a one-time $25 Google Play Console developer account.

---

### What this does *not* do

- No offline support — same as the PWA, this app is a thin shell over your live server. If
  the phone can't reach the server (no Tailscale connection, server down), the app won't load.
- No push notifications, camera access, etc. out of the box — those are genuine native
  features Capacitor *can* add via plugins, but none are wired in here. Ask if you want any
  of those added.
- iOS was intentionally not scaffolded here — it requires a Mac with Xcode to build at all,
  and an Apple Developer account ($99/yr) even just to install on your own devices without
  the App Store. Say the word if you want the iOS project added (`npx cap add ios`, run from
  a Mac) — the config/icons above already work for it, it's the same source assets.
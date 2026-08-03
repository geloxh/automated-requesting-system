import type { CapacitorConfig } from '@capacitor/cli';

// IMPORTANT: replace this with your actual server address before building.
// - Tailscale MagicDNS name is recommended (see ../TAILSCALE.md), e.g.
//   'https://ars-server.your-tailnet.ts.net'
// - Must be a real, trusted HTTPS certificate (see the cert note in
//   ../CAPACITOR.md) — Android's WebView, unlike a desktop browser, has no
//   "click through" option for self-signed certificates. It will just fail
//   to load with no way for the user to bypass it.
const ARS_SERVER_URL = 'https://ars-server.your-tailnet.ts.net';

const config: CapacitorConfig = {
  appId: 'com.ars.app',
  appName: 'ARS',
  webDir: 'www',
  server: {
    url: ARS_SERVER_URL,
    cleartext: false,
    // Allow the WebView to follow links/redirects within your own domain
    // (login redirects, form submissions, file downloads, etc.) without
    // Capacitor blocking cross-navigation.
    allowNavigation: [ARS_SERVER_URL.replace(/^https?:\/\//, '')],
  },
  android: {
    allowMixedContent: false,
  },
};

export default config;

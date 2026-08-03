// Minimal entry point. The window's target URL lives in tauri.conf.json
// (app.windows[0].url) — this app is a thin native shell over the existing
// ARS web app, the same way the Capacitor mobile app is. No custom Rust
// commands/IPC are needed for that, so this file intentionally stays empty
// beyond the standard Tauri bootstrap.
fn main() {
    ars_desktop_app_lib::run();
}

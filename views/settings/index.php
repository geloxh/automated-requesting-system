<?php
/**
 * Settings Page  –  /settings
 * Tabs: General | System Info
 * Admin-only view rendered inside base.php
 */

$timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

// Helper: read a saved setting with a fallback to the .env value
function cfg(array $settings, string $key, string $envKey = '', string $default = ''): string {
    if (isset($settings[$key])) return htmlspecialchars($settings[$key]);
    if ($envKey && isset($_ENV[$envKey])) return htmlspecialchars($_ENV[$envKey]);
    return htmlspecialchars($default);
}

// ── PHP / system info ─────────────────────────────────────────────────────────
$phpVersion = PHP_VERSION;
$phpSapi = PHP_SAPI;
$osInfo = php_uname('s') . ' ' . php_uname('r');
$serverSw = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A';
$dbVersion = 'N/A';
try { $dbVersion = db()->query('SELECT VERSION()')->fetchColumn(); } catch (\Throwable) {}
$loadedExt = implode(', ', array_filter(get_loaded_extensions(), fn($e) => in_array($e, ['pdo','pdo_mysql','mbstring','openssl','curl','json','zip','gd','imagick','intl'])));
$memoryLimit = ini_get('memory_limit');
$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
$maxExecTime = ini_get('max_execution_time') . 's';
$timezone = date_default_timezone_get();
$diskTotal = function_exists('disk_total_space') ? round(disk_total_space('/') / 1073741824, 1) . ' GB' : 'N/A';
$diskFree = function_exists('disk_free_space')  ? round(disk_free_space('/')  / 1073741824, 1) . ' GB' : 'N/A';
$sessionSave = session_save_path() ?: sys_get_temp_dir();
?>

<link rel="stylesheet" href="/processing-system/public/stylesheets/settings.css">

<div class="page-header">
    <div class="page-title-group">
        <h5 class="page-heading">Settings</h5>
        <p class="page-subheading">Manage application configuration and view system information.</p>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success mb-4">
        <i class="ti ti-circle-check"></i>
        <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger mb-4">
        <i class="ti ti-alert-circle"></i>
        <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- ── Settings Layout ─────────────────────────────────────────────────────── -->
<div class="settings-layout">

    <!-- Left nav -->
    <aside class="settings-nav">
        <button class="settings-nav-item active" data-tab="general">
            <i class="ti ti-settings-2"></i> General
        </button>
        <button class="settings-nav-item" data-tab="mail">
            <i class="ti ti-mail"></i> Mail
        </button>
        <button class="settings-nav-item" data-tab="sysinfo">
            <i class="ti ti-cpu"></i> System Info
        </button>
    </aside>

    <!-- Right panels -->
    <div class="settings-panels">

        <!-- ══ TAB: GENERAL ═══════════════════════════════════════════════════ -->
        <section class="settings-panel active" id="tab-general">

            <div class="settings-section-header">
                <h6 class="settings-section-title">
                    <i class="ti ti-settings-2"></i> General
                </h6>
                <p class="settings-section-sub">Core application settings and preferences.</p>
            </div>

            <form method="POST" action="/processing-system/public/settings/general">
                <?= \App\Helpers\Csrf::field() ?>

                <!-- Application -->
                <div class="settings-card">
                    <div class="settings-card-header">Application</div>
                    <div class="settings-card-body">

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="app_name">App Name</label>
                                <span class="settings-row-hint">Displayed in the browser tab and emails.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="text" id="app_name" name="app_name" class="form-control"
                                       value="<?= cfg($settings, 'app_name', 'APP_NAME', 'ProcessingSystem') ?>"
                                       placeholder="ProcessingSystem" required>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="app_url">App URL</label>
                                <span class="settings-row-hint">Base URL used for links in emails and redirects.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="url" id="app_url" name="app_url" class="form-control"
                                       value="<?= cfg($settings, 'app_url', 'APP_URL', 'https://localhost/processing-system/public') ?>"
                                       placeholder="https://yourdomain.com/processing-system/public">
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="app_env">Environment</label>
                                <span class="settings-row-hint">Affects error reporting and caching.</span>
                            </div>
                            <div class="settings-row-control">
                                <select id="app_env" name="app_env" class="form-control">
                                    <?php foreach (['local' => 'Local (Development)', 'staging' => 'Staging', 'production' => 'Production'] as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= cfg($settings, 'app_env', 'APP_ENV', 'local') === $val ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="app_timezone">Timezone</label>
                                <span class="settings-row-hint">Used for all date/time display.</span>
                            </div>
                            <div class="settings-row-control">
                                <select id="app_timezone" name="app_timezone" class="form-control">
                                    <?php
                                    $currentTz = cfg($settings, 'app_timezone', 'APP_TIMEZONE', 'Asia/Manila');
                                    foreach ($timezones as $tz):
                                    ?>
                                        <option value="<?= $tz ?>" <?= $currentTz === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Pagination & Sessions -->
                <div class="settings-card">
                    <div class="settings-card-header">Pagination &amp; Sessions</div>
                    <div class="settings-card-body">

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="items_per_page">Items Per Page</label>
                                <span class="settings-row-hint">Default row count for tables.</span>
                            </div>
                            <div class="settings-row-control settings-row-control--short">
                                <input type="number" id="items_per_page" name="items_per_page"
                                       class="form-control" min="5" max="200" step="5"
                                       value="<?= cfg($settings, 'items_per_page', '', '20') ?>">
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="session_lifetime">Session Lifetime</label>
                                <span class="settings-row-hint">Minutes before an idle session expires.</span>
                            </div>
                            <div class="settings-row-control settings-row-control--short">
                                <div class="input-addon">
                                    <input type="number" id="session_lifetime" name="session_lifetime"
                                           class="form-control" min="5" max="1440"
                                           value="<?= cfg($settings, 'session_lifetime', '', '120') ?>">
                                    <span class="input-addon-text">min</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="settings-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy"></i> Save General Settings
                    </button>
                </div>
            </form>
        </section>

        <!-- ══ TAB: MAIL ══════════════════════════════════════════════════════ -->
        <section class="settings-panel" id="tab-mail">

            <div class="settings-section-header">
                <h6 class="settings-section-title">
                    <i class="ti ti-mail"></i> Mail
                </h6>
                <p class="settings-section-sub">SMTP configuration used for notifications and password resets.</p>
            </div>

            <form method="POST" action="/processing-system/public/settings/mail">
                <?= \App\Helpers\Csrf::field() ?>

                <div class="settings-card">
                    <div class="settings-card-header">SMTP Server</div>
                    <div class="settings-card-body">

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="mail_host">SMTP Host</label>
                                <span class="settings-row-hint">e.g. smtp.gmail.com</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="text" id="mail_host" name="mail_host" class="form-control"
                                       value="<?= cfg($settings, 'mail_host', 'MAIL_HOST', 'smtp.gmail.com') ?>"
                                       placeholder="smtp.gmail.com">
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="mail_port">Port</label>
                                <span class="settings-row-hint">465 (SSL) or 587 (TLS)</span>
                            </div>
                            <div class="settings-row-control settings-row-control--short">
                                <input type="number" id="mail_port" name="mail_port" class="form-control"
                                       min="1" max="65535"
                                       value="<?= cfg($settings, 'mail_port', 'MAIL_PORT', '587') ?>">
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="mail_encryption">Encryption</label>
                            </div>
                            <div class="settings-row-control settings-row-control--short">
                                <select id="mail_encryption" name="mail_encryption" class="form-control">
                                    <?php foreach (['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'] as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= cfg($settings, 'mail_encryption', 'MAIL_ENCRYPTION', 'tls') === $val ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header">Credentials &amp; Sender</div>
                    <div class="settings-card-body">

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="mail_username">Username</label>
                                <span class="settings-row-hint">Usually your email address.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="email" id="mail_username" name="mail_username" class="form-control"
                                       value="<?= cfg($settings, 'mail_username', 'MAIL_USERNAME', '') ?>"
                                       placeholder="you@example.com">
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="mail_password">Password / App Key</label>
                                <span class="settings-row-hint">Leave blank to keep the existing value.</span>
                            </div>
                            <div class="settings-row-control">
                                <div class="input-password-wrap">
                                    <input type="password" id="mail_password" name="mail_password"
                                           class="form-control" placeholder="••••••••••••"
                                           autocomplete="new-password">
                                    <button type="button" class="toggle-password" title="Show/hide password">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="mail_from_address">From Address</label>
                            </div>
                            <div class="settings-row-control">
                                <input type="email" id="mail_from_address" name="mail_from_address" class="form-control"
                                       value="<?= cfg($settings, 'mail_from_address', 'MAIL_FROM_ADDRESS', '') ?>"
                                       placeholder="noreply@example.com">
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="mail_from_name">From Name</label>
                            </div>
                            <div class="settings-row-control">
                                <input type="text" id="mail_from_name" name="mail_from_name" class="form-control"
                                       value="<?= cfg($settings, 'mail_from_name', 'MAIL_FROM_NAME', 'Processing System') ?>"
                                       placeholder="Processing System">
                            </div>
                        </div>

                    </div>
                </div>

                <div class="settings-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy"></i> Save Mail Settings
                    </button>
                </div>
            </form>
        </section>

        <!-- ══ TAB: SYSTEM INFO ═══════════════════════════════════════════════ -->
        <section class="settings-panel" id="tab-sysinfo">

            <div class="settings-section-header">
                <h6 class="settings-section-title">
                    <i class="ti ti-cpu"></i> System Info
                </h6>
                <p class="settings-section-sub">Read-only runtime diagnostics for this server environment.</p>
            </div>

            <!-- PHP Runtime -->
            <div class="settings-card">
                <div class="settings-card-header">PHP Runtime</div>
                <div class="settings-card-body settings-card-body--info">
                    <?php
                    $infoRows = [
                        ['PHP Version', $phpVersion, 'ti-brand-php'],
                        ['SAPI', $phpSapi, 'ti-terminal'],
                        ['OS', $osInfo, 'ti-device-desktop'],
                        ['Web Server', $serverSw, 'ti-server'],
                        ['DB Version', $dbVersion, 'ti-database'],
                        ['Loaded Extensions', $loadedExt, 'ti-puzzle'],
                    ];
                    foreach ($infoRows as [$label, $val, $icon]):
                    ?>
                    <div class="info-row">
                        <span class="info-row-label"><i class="ti <?= $icon ?>"></i> <?= $label ?></span>
                        <span class="info-row-value"><?= htmlspecialchars($val) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PHP Limits -->
            <div class="settings-card">
                <div class="settings-card-header">PHP Limits &amp; Paths</div>
                <div class="settings-card-body settings-card-body--info">
                    <?php
                    $limitRows = [
                        ['Memory Limit', $memoryLimit, 'ti-stack'],
                        ['Upload Max Size', $uploadMax, 'ti-upload'],
                        ['POST Max Size', $postMax, 'ti-file-upload'],
                        ['Max Execution Time',$maxExecTime, 'ti-clock'],
                        ['Timezone', $timezone,'ti-world'],
                        ['Session Save Path', $sessionSave, 'ti-folder'],
                    ];
                    foreach ($limitRows as [$label, $val, $icon]):
                    ?>
                    <div class="info-row">
                        <span class="info-row-label"><i class="ti <?= $icon ?>"></i> <?= $label ?></span>
                        <span class="info-row-value"><?= htmlspecialchars($val) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Disk -->
            <div class="settings-card">
                <div class="settings-card-header">Storage</div>
                <div class="settings-card-body settings-card-body--info">
                    <div class="info-row">
                        <span class="info-row-label"><i class="ti ti-device-floppy"></i> Total Disk Space</span>
                        <span class="info-row-value"><?= $diskTotal ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-row-label"><i class="ti ti-device-floppy"></i> Free Disk Space</span>
                        <span class="info-row-value"><?= $diskFree ?></span>
                    </div>
                    <?php
                    // Show disk usage bar if both values are available
                    if ($diskTotal !== 'N/A' && $diskFree !== 'N/A'):
                        $total = disk_total_space('/');
                        $free = disk_free_space('/');
                        $used = $total - $free;
                        $pct = $total > 0 ? round($used / $total * 100) : 0;
                        $barClass = $pct > 85 ? 'disk-bar--danger' : ($pct > 65 ? 'disk-bar--warning' : '');
                    ?>
                    <div class="disk-usage-bar-wrap">
                        <div class="disk-usage-bar <?= $barClass ?>" style="--pct: <?= $pct ?>%"></div>
                        <span class="disk-usage-label"><?= $pct ?>% used</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </section>

    </div><!-- /.settings-panels -->
</div><!-- /.settings-layout -->

<script src="/processing-system/public/scripts/settings.js"></script>
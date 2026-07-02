<?php
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$assetPrefix = $basePath === '/' ? '' : $basePath;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-label-key="app.title">CASPTI Mini ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $assetPrefix; ?>/public/css/app.css">
</head>
<body>
    <div class="ambient-gradient"></div>
    <div id="toast-host" class="toast-host"></div>

    <section id="login-screen" class="login-screen">
        <div class="login-panel glass-card">
            <div class="brand-stack">
                <span class="eyebrow" data-label-key="app.brand">CASPTI</span>
                <h1 data-label-key="app.title">CASPTI Mini ERP</h1>
                <p data-label-key="login.subtitle">Technology consulting, e-commerce operations and financial control in one web workspace.</p>
            </div>
            <form id="login-form" class="login-form">
                <label>
                    <span data-label-key="login.email">Email</span>
                    <input type="email" name="Email" autocomplete="username" required>
                </label>
                <label>
                    <span data-label-key="login.password">Password</span>
                    <input type="password" name="Password" autocomplete="current-password" required>
                </label>
                <button type="submit" class="primary-button" data-label-key="login.signin">Sign in</button>
            </form>
        </div>
    </section>

    <section id="erp-shell" class="erp-shell hidden">
        <aside class="sidebar glass-card">
            <div>
                <div class="sidebar-brand">
                    <span class="eyebrow" data-label-key="app.brand">CASPTI</span>
                    <h2 data-label-key="app.title">CASPTI Mini ERP</h2>
                    <p data-label-key="sidebar.subtitle">AX-style operational workspace</p>
                </div>
                <nav class="sidebar-nav" id="sidebar-nav"></nav>
            </div>
            <div class="sidebar-footer">
                <span id="sidebar-user-name" data-label-key="user.guest">Guest</span>
                <button id="logout-button" class="secondary-button" data-label-key="button.logout">Logout</button>
            </div>
        </aside>

        <main class="main-shell">
            <header class="page-header glass-card">
                <div>
                    <span class="eyebrow" data-label-key="header.operations">Operations</span>
                    <h1 id="page-title" data-label-key="module.dashboard.title">Dashboard</h1>
                    <p id="page-subtitle" data-label-key="module.dashboard.subtitle">Executive financial and operational overview.</p>
                </div>
                <div class="user-chip">
                    <span id="header-user-name" data-label-key="user.guest">Guest</span>
                    <small id="header-user-role" data-label-key="user.norole">No role</small>
                    <button id="logout-top-button" class="ghost-button logout-top-button" data-label-key="button.logout">Logout</button>
                </div>
            </header>

            <section id="dashboard-view" class="view-panel"></section>
            <section id="workspace-view" class="view-panel hidden">
                <div id="workspace-toolbar" class="workspace-toolbar"></div>
                <div id="workspace-content" class="workspace-content"></div>
            </section>
        </main>
    </section>

    <script>
        window.APP_BASE_PATH = <?php echo json_encode($assetPrefix); ?>;
        window.APP_NAME = 'CASPTI Mini ERP';
        window.APP_OAUTH_CREDENTIAL_KEY = <?php echo json_encode($GLOBALS['app_config']['oauthCredentialKey']); ?>;
    </script>
    <!-- PDF and Excel Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.min.js"></script>
    <script src="<?php echo $assetPrefix; ?>/public/js/api.js"></script>
    <script src="<?php echo $assetPrefix; ?>/public/js/app.js"></script>
</body>
</html>
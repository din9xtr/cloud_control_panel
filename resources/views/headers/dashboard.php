<?php
/** @var Din9xtrCloud\ViewModels\Dashboard\DashboardViewModel $page */
?>
<header class="dashboard-header">
    <div class="welcome-section">
        <h1 class="welcome-title">Welcome to your cloud storage
            <?= htmlspecialchars($page->username) ?> 👋</h1>
        <p class="welcome-subtitle">Manage your cloud storage efficiently</p>
    </div>
    <div class="header-actions">
        <form action="/" method="GET" style="display: inline;">

            <button class="btn btn-primary" id="refresh-dashboard">
                <span class="btn-icon">💨</span>Refresh
            </button>
        </form>

        <form action="/logout" method="POST" style="display: inline;">
            <button type="submit" class="btn btn-secondary" id="logout-btn">
                <span class="btn-icon">🚪</span> Logout
            </button>
            <input type="hidden" name="_csrf"
                   value="<?= htmlspecialchars($page->csrf) ?>">
        </form>
    </div>
</header>
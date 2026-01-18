<div class="login-card">
    <h1>Connect iCloud</h1>

    <?php if (!empty($viewModel->error)) : ?>
        <p class="error"><?= htmlspecialchars($viewModel->error) ?></p>
    <?php endif; ?>

    <div class="info-message">
        <ol>
            <li>Open <b>icloud.com</b> and log in</li>
            <li>Complete 2FA</li>
            <li>Open DevTools → Network</li>
            <li>Copy <b>Request Header → Cookie</b></li>
            <li>Copy <b>X-APPLE-WEBAUTH-HSA-TRUST</b> value</li>
        </ol>
    </div>

    <form method="POST" action="/icloud/connect" class="login-form">
        <label for="apple_id">Apple ID</label>
        <input
                type="email"
                id="apple_id"
                name="apple_id"
                required
                placeholder="yourname@icloud.com"
        >

        <label for="password">Password</label>
        <input
                type="password"
                id="password"
                name="password"
                required
                placeholder="iCloud password"
        >

        <label for="cookies">Cookies (full header)</label>
        <textarea
                id="cookies"
                name="cookies"
                rows="6"
                required
                placeholder="X-APPLE-WEBAUTH-HSA-TRUST=...; X-APPLE-WEBAUTH-USER=...;"
        ></textarea>

        <label for="trust_token">Trust token</label>
        <input
                type="text"
                id="trust_token"
                name="trust_token"
                required
                placeholder="X-APPLE-WEBAUTH-HSA-TRUST value"
        >

        <button type="submit">
            Connect iCloud
        </button>

        <input type="hidden" name="_csrf"
               value="<?= htmlspecialchars($viewModel->csrf) ?>">
    </form>
</div>

<!-- icloud_login.php -->
<div class="login-card">
    <h1><?= htmlspecialchars($viewModel->title ?? 'iCloud Login') ?></h1>

    <?php if (!empty($viewModel->error)) : ?>
        <p class="error"><?= htmlspecialchars($viewModel->error) ?></p>
    <?php endif; ?>

    <?php if (isset($viewModel->show2fa) && $viewModel->show2fa): ?>
        <div class="info-message" style="
            background: rgba(66, 153, 225, 0.1);
            border-left: 4px solid #4299e1;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0 8px 8px 0;
            color: #2d3748;
        ">
            <p style="margin: 0; font-weight: 500;">
                Enter the 6-digit verification code sent to your trusted devices.
            </p>
        </div>

        <form method="POST" action="/icloud/2fa" class="login-form">
            <input type="hidden" name="apple_id" value="<?= htmlspecialchars($viewModel->appleId ?? '') ?>">
            <input type="hidden" name="password" value="<?= htmlspecialchars($viewModel->password ?? '') ?>">

            <label for="code">
                6-digit verification code
            </label>
            <input type="text"
                   id="code"
                   name="code"
                   pattern="[0-9]{6}"
                   maxlength="6"
                   required
                   placeholder="123456"
                   autocomplete="one-time-code"
                   inputmode="numeric"
                   style="letter-spacing: 2px; font-size: 1.2rem; text-align: center;">

            <button type="submit" style="margin-top: 1rem;">
                Verify Code
            </button>

            <div style="margin-top: 1rem; text-align: center;">
                <a href="/icloud/connect" style="color: #667eea; text-decoration: none;">
                    ← Back to login
                </a>
            </div>

            <input type="hidden" name="_csrf"
                   value="<?= htmlspecialchars($viewModel->csrf) ?>">
        </form>

    <?php else: ?>
        <form method="POST" action="/icloud/connect" class="login-form">
            <label for="apple_id">
                Apple ID
            </label>
            <input type="email"
                   id="apple_id"
                   name="apple_id"
                   required
                   placeholder="name@example.com"
                   autocomplete="username">

            <label for="password">
                Password
            </label>
            <input type="password"
                   id="password"
                   name="password"
                   required
                   placeholder="Enter your password"
                   autocomplete="current-password">

            <button type="submit">
                Connect iCloud
            </button>

            <input type="hidden" name="_csrf"
                   value="<?= htmlspecialchars($viewModel->csrf) ?>">
        </form>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const codeInput = document.getElementById('code');
        if (codeInput) {
            codeInput.addEventListener('keypress', function (e) {
                const char = String.fromCharCode(e.which);
                if (!/^\d$/.test(char)) {
                    e.preventDefault();
                }
            });
            codeInput.addEventListener('input', function () {
                if (this.value.length === 6) {
                    this.form.submit();
                }
            });
        }
    });
</script>

<style>
    @media (prefers-color-scheme: dark) {
        .info-message {
            background: rgba(66, 153, 225, 0.15) !important;
            border-left-color: #4299e1 !important;
            color: #cbd5e1 !important;
        }
    }
</style>

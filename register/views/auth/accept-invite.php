<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="glass-card" style="width: 100%; max-width: 420px; padding: 40px;">
        <div style="text-align: center; margin-bottom: 32px;">
            <svg class="mx-auto mb-3" style="width: 40px; height: 40px; color: var(--risk-low);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h1 style="font-size: 24px; font-weight: 700; margin: 0;" class="gradient-text">Accept Invitation</h1>
            <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">
                Join <strong style="color: var(--text-primary);"><?= htmlspecialchars($invite['school_name']) ?></strong>
            </p>
        </div>

        <div style="background: var(--bg-tertiary); border-radius: 12px; padding: 16px; margin-bottom: 24px;">
            <p style="font-size: 13px; color: var(--text-secondary); margin: 0;">
                <strong style="color: var(--text-primary);"><?= htmlspecialchars($invite['first_name'] . ' ' . $invite['last_name']) ?></strong><br>
                <?= htmlspecialchars($invite['email']) ?>
            </p>
        </div>

        <form method="POST" action="/invite/<?= htmlspecialchars($token) ?>">
            <?= Auth::csrfField() ?>

            <div style="margin-bottom: 16px;">
                <label class="form-label" for="password">Set Your Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Min 8 characters" required autofocus>
            </div>

            <div style="margin-bottom: 24px;">
                <label class="form-label" for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-input" placeholder="Confirm" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Activate Account
            </button>
        </form>
    </div>
</div>

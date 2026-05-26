<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="glass-card" style="width: 100%; max-width: 480px; padding: 40px;">
        <div style="text-align: center; margin-bottom: 32px;">
            <svg class="mx-auto mb-3" style="width: 40px; height: 40px; color: var(--accent);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <h1 style="font-size: 24px; font-weight: 700; margin: 0;" class="gradient-text">Register Your School</h1>
            <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Set up your school's risk prediction system</p>
        </div>

        <form method="POST" action="/register">
            <?= Auth::csrfField() ?>

            <div style="margin-bottom: 16px;">
                <label class="form-label" for="school_name">School Name</label>
                <input type="text" id="school_name" name="school_name" class="form-input" placeholder="Elizabeth School of London" required autofocus value="<?= htmlspecialchars($_POST['school_name'] ?? '') ?>">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-input" placeholder="John" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-input" placeholder="Smith" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label class="form-label" for="email">Admin Email</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="admin@school.ac.uk" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
                <div>
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Min 8 characters" required>
                </div>
                <div>
                    <label class="form-label" for="password_confirm">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-input" placeholder="Confirm" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Create School Account
            </button>
        </form>

        <p style="text-align: center; margin-top: 24px; font-size: 13px; color: var(--text-muted);">
            Already have an account?
            <a href="/login" style="color: var(--accent-light); text-decoration: none;">Sign in</a>
        </p>
    </div>
</div>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="glass-card" style="width: 100%; max-width: 420px; padding: 40px;">
        <div style="text-align: center; margin-bottom: 32px;">
            <svg class="mx-auto mb-3" style="width: 40px; height: 40px; color: var(--accent);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <h1 style="font-size: 24px; font-weight: 700; margin: 0;" class="gradient-text">Student Risk Prediction</h1>
            <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Sign in to your school</p>
        </div>

        <form method="POST" action="/login">
            <?= Auth::csrfField() ?>

            <div style="margin-bottom: 16px;">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="you@school.ac.uk" required autofocus>
            </div>

            <div style="margin-bottom: 24px;">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Sign In
            </button>
        </form>

        <p style="text-align: center; margin-top: 24px; font-size: 13px; color: var(--text-muted);">
            Don't have an account?
            <a href="/register" style="color: var(--accent-light); text-decoration: none;">Register your school</a>
        </p>
    </div>
</div>

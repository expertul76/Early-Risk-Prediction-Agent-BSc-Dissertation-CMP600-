<div style="margin-bottom: 24px;">
    <a href="/groups" style="color: var(--text-muted); text-decoration: none; font-size: 13px;">&larr; Back to Groups</a>
    <h1 style="font-size: 24px; font-weight: 700; margin: 8px 0 0 0;">Create Student Group</h1>
</div>

<div class="glass-card" style="padding: 24px; max-width: 480px;">
    <form method="POST" action="/groups/create">
        <?= Auth::csrfField() ?>
        <div style="margin-bottom: 16px;">
            <label class="form-label">Group Name</label>
            <input type="text" name="name" class="form-input" placeholder="e.g. Monday AM Group, Cohort A" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary">Create Group</button>
    </form>
</div>

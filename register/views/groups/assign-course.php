<div style="margin-bottom: 24px;">
    <a href="/groups/<?= $group['id'] ?>" style="color: var(--text-muted); text-decoration: none; font-size: 13px;">&larr; Back to <?= htmlspecialchars($group['name']) ?></a>
    <h1 style="font-size: 24px; font-weight: 700; margin: 8px 0 0 0;">Assign to Course</h1>
    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Assign <strong style="color: var(--text-primary);"><?= htmlspecialchars($group['name']) ?></strong> to a course and set the schedule</p>
</div>

<div class="glass-card" style="padding: 24px; max-width: 600px;">
    <?php if (empty($courses)): ?>
    <p style="color: var(--text-muted);">No courses available. <a href="/courses/create" style="color: var(--accent-light);">Create a course</a> first.</p>
    <?php else: ?>
    <form method="POST" action="/groups/<?= $group['id'] ?>/assign-course">
        <?= Auth::csrfField() ?>

        <div style="margin-bottom: 16px;">
            <label class="form-label">Course</label>
            <select name="course_id" class="form-select" required>
                <option value="">Select a course...</option>
                <?php foreach ($courses as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>) - <?= $c['num_weeks'] ?> weeks</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom: 20px;">
            <label class="form-label">Session Days</label>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day): ?>
                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px 14px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text-secondary);">
                    <input type="checkbox" name="days[]" value="<?= $day ?>">
                    <?= $day ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Assign &amp; Generate Sessions</button>
    </form>
    <?php endif; ?>
</div>

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 24px; font-weight: 700; margin: 0;">Courses</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Manage your courses and assessments</p>
    </div>
    <?php if ($user['role'] === 'admin'): ?>
    <a href="/courses/create" class="btn btn-primary">+ New Course</a>
    <?php endif; ?>
</div>

<?php if (empty($courses)): ?>
<div class="glass-card" style="padding: 60px; text-align: center;">
    <svg style="width: 48px; height: 48px; color: var(--text-muted); margin: 0 auto 12px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
    <p style="color: var(--text-muted); font-size: 14px;">No courses yet.</p>
    <?php if ($user['role'] === 'admin'): ?>
    <a href="/courses/create" class="btn btn-secondary btn-sm" style="margin-top: 12px;">Create your first course</a>
    <?php endif; ?>
</div>
<?php else: ?>
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
    <?php foreach ($courses as $course): ?>
    <a href="/courses/<?= $course['id'] ?>" class="glass-card" style="padding: 24px; text-decoration: none; display: block;">
        <div style="display: flex; align-items: start; justify-content: space-between; margin-bottom: 12px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 600; color: var(--text-primary); margin: 0;"><?= htmlspecialchars($course['name']) ?></h3>
                <span style="font-family: monospace; font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($course['code']) ?></span>
            </div>
            <?php if ($course['is_active']): ?>
            <span class="badge-low">Active</span>
            <?php endif; ?>
        </div>
        <div style="display: flex; gap: 16px; font-size: 13px; color: var(--text-secondary);">
            <span><?= $course['num_weeks'] ?> weeks</span>
            <span>Starts <?= date('j M Y', strtotime($course['start_date'])) ?></span>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

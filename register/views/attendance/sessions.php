<div style="margin-bottom: 24px;">
    <a href="/courses/<?= $course['id'] ?>" style="color: var(--text-muted); text-decoration: none; font-size: 13px;">← Back to <?= htmlspecialchars($course['name']) ?></a>
    <h1 style="font-size: 24px; font-weight: 700; margin: 8px 0 0 0;">Attendance</h1>
    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;"><?= htmlspecialchars($course['name']) ?> (<?= htmlspecialchars($course['code']) ?>)</p>
</div>

<?php if (empty($sessions)): ?>
<div class="glass-card" style="padding: 60px; text-align: center;">
    <p style="color: var(--text-muted); font-size: 14px;">No sessions scheduled. <a href="/groups" style="color: var(--accent-light);">Assign a student group</a> to generate sessions.</p>
</div>
<?php else: ?>

<?php
// Group sessions by week
$byWeek = [];
foreach ($sessions as $s) {
    $byWeek[$s['week_number']][] = $s;
}
?>

<div style="display: flex; flex-direction: column; gap: 16px;">
    <?php foreach ($byWeek as $weekNum => $weekSessions): ?>
    <div class="glass-card" style="padding: 20px;">
        <h3 style="font-size: 14px; font-weight: 600; color: var(--text-secondary); margin: 0 0 12px 0;">Week <?= $weekNum ?></h3>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <?php foreach ($weekSessions as $s): ?>
            <a href="/attendance/session/<?= $s['id'] ?>"
               style="text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg-tertiary); transition: all 0.2s;"
               class="<?= $s['is_completed'] ? '' : 'glass-card' ?>">
                <span style="font-size: 13px; font-weight: 500; color: var(--text-primary);"><?= $s['day_of_week'] ?></span>
                <span style="font-size: 12px; color: var(--text-muted);"><?= date('j M', strtotime($s['session_date'])) ?></span>
                <?php if ($s['is_completed']): ?>
                <span class="badge-low" style="font-size: 10px;">Done</span>
                <?php else: ?>
                <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--accent);"></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

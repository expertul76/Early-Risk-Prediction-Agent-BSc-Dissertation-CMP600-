<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 24px; font-weight: 700; margin: 0;">Student Groups</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Manage groups of students and assign them to courses</p>
    </div>
    <a href="/groups/create" class="btn btn-primary">+ New Group</a>
</div>

<?php if (empty($groups)): ?>
<div class="glass-card" style="padding: 60px; text-align: center;">
    <p style="color: var(--text-muted); font-size: 14px;">No student groups yet.</p>
    <a href="/groups/create" class="btn btn-secondary btn-sm" style="margin-top: 12px;">Create your first group</a>
</div>
<?php else: ?>
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
    <?php foreach ($groups as $g): ?>
    <a href="/groups/<?= $g['id'] ?>" class="glass-card" style="padding: 20px; text-decoration: none; display: block;">
        <h3 style="font-size: 16px; font-weight: 600; color: var(--text-primary); margin: 0 0 8px 0;"><?= htmlspecialchars($g['name']) ?></h3>
        <span style="font-size: 13px; color: var(--text-secondary);"><?= $g['student_count'] ?> student<?= $g['student_count'] !== '1' ? 's' : '' ?></span>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

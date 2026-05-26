<div style="margin-bottom: 24px;">
    <a href="/courses/<?= $course['id'] ?>" style="color: var(--text-muted); text-decoration: none; font-size: 13px;">← Back to <?= htmlspecialchars($course['name']) ?></a>
    <h1 style="font-size: 24px; font-weight: 700; margin: 8px 0 0 0;">Assessment Results</h1>
    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;"><?= htmlspecialchars($course['name']) ?> (<?= htmlspecialchars($course['code']) ?>)</p>
</div>

<?php if (empty($assessments)): ?>
<div class="glass-card" style="padding: 60px; text-align: center;">
    <p style="color: var(--text-muted); font-size: 14px;">No assessments defined for this course.
    <?php if ($user['role'] === 'admin'): ?>
    <a href="/courses/<?= $course['id'] ?>/edit" style="color: var(--accent-light);">Edit course</a> to add assessments.
    <?php endif; ?>
    </p>
</div>
<?php elseif (empty($students)): ?>
<div class="glass-card" style="padding: 60px; text-align: center;">
    <p style="color: var(--text-muted); font-size: 14px;">No students enrolled. <a href="/groups" style="color: var(--accent-light);">Assign a group</a> first.</p>
</div>
<?php else: ?>

<form method="POST" action="/assessments/<?= $course['id'] ?>">
    <?= Auth::csrfField() ?>

    <div class="glass-card" style="padding: 24px; overflow-x: auto; margin-bottom: 24px;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="position: sticky; left: 0; background: var(--bg-secondary); z-index: 1;">Student</th>
                    <?php foreach ($assessments as $a): ?>
                    <th style="min-width: 200px;">
                        <?= htmlspecialchars($a['name']) ?>
                        <br><span style="font-weight: 400; color: var(--text-muted);"><?= ucfirst($a['type']) ?> · Wk<?= $a['week_due'] ?></span>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td style="position: sticky; left: 0; background: var(--bg-primary); z-index: 1; color: var(--text-primary); font-weight: 500;">
                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                    </td>
                    <?php foreach ($assessments as $a):
                        $key = $s['id'] . '-' . $a['id'];
                        $r = $resultMap[$key] ?? null;
                    ?>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <select name="result_status[<?= $key ?>]" class="form-select" style="font-size: 12px; padding: 4px 8px;">
                                <option value="not_given" <?= (!$r || $r['status'] === 'not_given') ? 'selected' : '' ?>>Not Given</option>
                                <option value="completed" <?= ($r && $r['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                                <option value="late" <?= ($r && $r['status'] === 'late') ? 'selected' : '' ?>>Late</option>
                                <option value="not_completed" <?= ($r && $r['status'] === 'not_completed') ? 'selected' : '' ?>>Not Submitted</option>
                            </select>
                            <input type="number" name="result_grade[<?= $key ?>]" class="form-input"
                                   placeholder="Grade %"
                                   min="0" max="100" step="1"
                                   value="<?= $r ? $r['grade'] : '' ?>"
                                   style="font-size: 12px; padding: 4px 8px;">
                        </div>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button type="submit" class="btn btn-primary">Save Results & Update Risk Scores</button>
</form>
<?php endif; ?>

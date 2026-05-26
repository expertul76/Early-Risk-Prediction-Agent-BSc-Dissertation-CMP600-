<div style="margin-bottom: 24px;">
    <a href="/groups" style="color: var(--text-muted); text-decoration: none; font-size: 13px;">&larr; Back to Groups</a>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 700; margin: 0;"><?= htmlspecialchars($group['name']) ?></h1>
            <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;"><?= count($students) ?> students</p>
        </div>
        <a href="/groups/<?= $group['id'] ?>/assign-course" class="btn btn-primary btn-sm">Assign to Course</a>
    </div>
</div>

<!-- Course Assignments -->
<?php if (!empty($assignments)): ?>
<div class="glass-card" style="padding: 20px; margin-bottom: 24px;">
    <h3 style="font-size: 14px; font-weight: 600; margin: 0 0 12px 0; color: var(--text-secondary);">Assigned Courses</h3>
    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        <?php foreach ($assignments as $a): ?>
        <a href="/courses/<?= $a['course_id'] ?>" class="btn btn-secondary btn-sm">
            <?= htmlspecialchars($a['course_name']) ?> (<?= htmlspecialchars($a['course_code']) ?>)
            &middot; <?= implode(', ', json_decode($a['days_of_week'], true)) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Add Student Form -->
<div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
    <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Add Student</h2>
    <form method="POST" action="/groups/<?= $group['id'] ?>/add-student" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 12px; align-items: end;">
        <?= Auth::csrfField() ?>
        <div>
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" class="form-input" placeholder="John" required>
        </div>
        <div>
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" class="form-input" placeholder="Smith" required>
        </div>
        <div>
            <label class="form-label">Email (optional)</label>
            <input type="email" name="email" class="form-input" placeholder="john@email.com">
        </div>
        <div>
            <label class="form-label">Student ID (optional)</label>
            <input type="text" name="student_id" class="form-input" placeholder="STU-001">
        </div>
        <button type="submit" class="btn btn-primary">Add</button>
    </form>
</div>

<!-- CSV Import -->
<div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
    <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 8px 0;">Import from CSV</h2>
    <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 16px;">Upload a CSV with columns: first_name, last_name, email (optional), student_id (optional)</p>
    <form method="POST" action="/groups/<?= $group['id'] ?>/import-csv" enctype="multipart/form-data" style="display: flex; gap: 12px; align-items: end;">
        <?= Auth::csrfField() ?>
        <input type="file" name="csv_file" accept=".csv" class="form-input" required style="flex: 1;">
        <button type="submit" class="btn btn-secondary">Import CSV</button>
    </form>
</div>

<!-- Student List -->
<div class="glass-card" style="padding: 24px;">
    <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Students</h2>
    <?php if (empty($students)): ?>
    <p style="color: var(--text-muted); font-size: 14px; text-align: center; padding: 40px 0;">No students in this group yet.</p>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Student ID</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $s): ?>
            <tr>
                <td style="color: var(--text-primary); font-weight: 500;"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                <td style="color: var(--text-secondary); font-size: 13px;"><?= htmlspecialchars($s['email'] ?? '-') ?></td>
                <td style="color: var(--text-muted); font-family: monospace; font-size: 12px;"><?= htmlspecialchars($s['student_ext_id'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

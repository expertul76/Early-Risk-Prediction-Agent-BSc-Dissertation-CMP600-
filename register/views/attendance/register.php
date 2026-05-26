<div style="margin-bottom: 24px;">
    <a href="/attendance/<?= $session['course_id'] ?>" style="color: var(--text-muted); text-decoration: none; font-size: 13px;">← Back to Sessions</a>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 700; margin: 0;">
                Register - Week <?= $session['week_number'] ?> <?= $session['day_of_week'] ?>
            </h1>
            <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">
                <?= htmlspecialchars($session['course_name']) ?> · <?= htmlspecialchars($session['group_name']) ?> · <?= date('l j F Y', strtotime($session['session_date'])) ?>
            </p>
        </div>
        <?php if ($session['is_completed']): ?>
        <span class="badge-low" style="font-size: 14px; padding: 6px 16px;">Completed</span>
        <?php endif; ?>
    </div>
</div>

<form method="POST" action="/attendance/session/<?= $session['id'] ?>">
    <?= Auth::csrfField() ?>

    <div class="glass-card" style="padding: 24px; margin-bottom: 24px;">
        <?php if (empty($students)): ?>
        <p style="color: var(--text-muted); text-align: center; padding: 40px 0;">No students in this group.</p>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 200px;">Student</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td style="color: var(--text-primary); font-weight: 500;">
                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 4px;">
                            <?php
                            $currentStatus = $s['status'] ?? '';
                            $statusOptions = [
                                'attending' => 'Present',
                                'late' => 'Late',
                                'not_attending' => 'Absent',
                                'authorised_absence' => 'Authorised',
                            ];
                            foreach ($statusOptions as $val => $label):
                            ?>
                            <label class="status-btn <?= $currentStatus === $val ? 'active-' . $val : '' ?>" style="cursor: pointer;">
                                <input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $val ?>"
                                       <?= $currentStatus === $val ? 'checked' : '' ?>
                                       style="display: none;"
                                       onchange="this.closest('.status-btn').parentElement.querySelectorAll('.status-btn').forEach(b => b.className = 'status-btn'); this.closest('.status-btn').classList.add('status-btn', 'active-<?= $val ?>')">
                                <?= $label ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td>
                        <input type="text" name="notes[<?= $s['id'] ?>]" class="form-input"
                               placeholder="Optional notes..."
                               value="<?= htmlspecialchars($s['notes'] ?? '') ?>"
                               style="font-size: 12px; padding: 6px 10px;">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div style="display: flex; gap: 12px;">
        <button type="submit" class="btn btn-secondary">Save Attendance</button>
        <?php if (!$session['is_completed']): ?>
        <button type="submit" formaction="/attendance/session/<?= $session['id'] ?>/complete" class="btn btn-primary">
            Save & Complete Session
        </button>
        <?php endif; ?>
    </div>
</form>

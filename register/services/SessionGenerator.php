<?php
class SessionGenerator {
    public static function generate(int $assignmentId, array $course): int {
        $days = Database::fetch(
            'SELECT days_of_week FROM group_course_assignments WHERE id = ?',
            [$assignmentId]
        );

        $daysOfWeek = json_decode($days['days_of_week'], true);
        $startDate = new DateTime($course['start_date']);
        $numWeeks = (int)$course['num_weeks'];
        $courseId = (int)$course['id'];

        $dayMap = ['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 7];

        $count = 0;
        for ($week = 1; $week <= $numWeeks; $week++) {
            foreach ($daysOfWeek as $day) {
                $dayNum = $dayMap[$day] ?? 1;

                // Calculate date: start of week + day offset
                $date = clone $startDate;
                $date->modify('+' . (($week - 1) * 7) . ' days');

                // Find the correct day of the week
                $currentDayNum = (int)$date->format('N'); // 1=Mon, 7=Sun
                $diff = $dayNum - $currentDayNum;
                if ($diff !== 0) {
                    $date->modify(($diff > 0 ? '+' : '') . $diff . ' days');
                }

                Database::insert('scheduled_sessions', [
                    'assignment_id' => $assignmentId,
                    'course_id' => $courseId,
                    'session_date' => $date->format('Y-m-d'),
                    'week_number' => $week,
                    'day_of_week' => $day,
                ]);
                $count++;
            }
        }

        return $count;
    }
}

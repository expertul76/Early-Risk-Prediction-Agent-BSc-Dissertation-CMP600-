import type { Student } from '../context/types'

export function exportStudentsCSV(students: Student[]): string {
  const headers = [
    'Student ID',
    'First Name',
    'Last Name',
    'Email',
    'Course',
    'Course Length (weeks)',
    'Attendance %',
    'Attendance Score',
    'Assessment Score',
    'Engagement Score',
    'Sentiment Score',
    'Composite Score',
    'Risk Classification',
    'Teacher Agrees',
    'Teacher Notes',
    'Reviewed At',
  ]

  const rows = students.map(s => {
    const presentCount = s.attendance.filter(w => w.status === 'present').length
    const attendancePct = s.attendance.length > 0
      ? Math.round((presentCount / s.attendance.length) * 100)
      : ''

    return [
      s.id,
      s.firstName,
      s.lastName,
      s.email,
      s.courseId,
      s.courseLength,
      attendancePct,
      s.riskScore?.factors.attendance ?? '',
      s.riskScore?.factors.assessment ?? '',
      s.riskScore?.factors.engagement ?? '',
      s.riskScore?.factors.sentiment ?? '',
      s.riskScore?.composite ?? '',
      s.riskScore?.classification?.toUpperCase() ?? '',
      s.teacherReview?.agrees !== null && s.teacherReview?.agrees !== undefined
        ? (s.teacherReview.agrees ? 'Yes' : 'No')
        : '',
      s.teacherReview?.notes ?? '',
      s.teacherReview?.reviewedAt ?? '',
    ].map(v => {
      const str = String(v)
      return str.includes(',') || str.includes('"') || str.includes('\n')
        ? `"${str.replace(/"/g, '""')}"`
        : str
    }).join(',')
  })

  return [headers.join(','), ...rows].join('\n')
}

import type { Student, WeekRecord, Assessment, ExistingMetrics, AttendanceStatus, AssessmentStatus, DetectedSchema } from '../context/types';

export function mapToStudents(data: Record<string, string>[], schema: DetectedSchema): Student[] {
  if (schema.type === 'modern') {
    return data.map((row, idx) => mapModernRow(row, schema.headers, idx));
  }
  return data.map((row, idx) => mapLegacyRow(row, schema.headers, idx));
}

function mapModernRow(row: Record<string, string>, headers: string[], idx: number): Student {
  const lower = headers.map(h => h.trim().toLowerCase());

  const firstName = findValue(row, headers, lower, ['first name', 'firstname']) || '';
  const lastName = findValue(row, headers, lower, ['last name', 'lastname']) || '';
  const email = findValue(row, headers, lower, ['email', 'email address']) || '';
  const studentId = findValue(row, headers, lower, ['student id']) || `GEN-${idx + 1}`;
  const courseId = findValue(row, headers, lower, ['course id', 'course']) || '';
  const courseLengthStr = findValue(row, headers, lower, ['course length (weeks)']);
  const courseLength = courseLengthStr ? parseInt(courseLengthStr) : 16;

  // Parse attendance weeks (alternating: Week N, Engagement Notes)
  const attendance: WeekRecord[] = [];
  for (let w = 1; w <= 16; w++) {
    const weekKey = headers.find((_, i) => lower[i] === `week ${w}`);
    if (!weekKey) continue;
    const value = row[weekKey]?.trim();
    if (!value) continue;

    // Find corresponding engagement notes
    const weekIdx = headers.indexOf(weekKey);
    const noteKey = weekIdx + 1 < headers.length && lower[weekIdx + 1].includes('engagement')
      ? headers[weekIdx + 1] : null;

    attendance.push({
      week: w,
      status: parseModernAttendance(value),
      engagementNote: noteKey ? row[noteKey]?.trim() : undefined,
    });
  }

  // Parse assessments (4 groups of 4 columns each)
  const assessments: Assessment[] = [];
  const assessmentNames = [
    'Formative Assessment 1',
    'Formative Assessment 2',
    'Summative Assessment 1',
    'Summative Assessment 2',
  ];

  for (const name of assessmentNames) {
    const type = name.toLowerCase().includes('formative') ? 'formative' as const : 'summative' as const;
    const uploadedKey = headers.find((_, i) => lower[i] === `${name.toLowerCase()} uploaded`);
    const statusKey = headers.find((_, i) => lower[i] === `${name.toLowerCase()} status`);
    const gradeKey = headers.find((_, i) => lower[i] === `${name.toLowerCase()} grade`);
    // Find the feedback column that follows this assessment's grade column
    let feedbackCol: string | undefined;
    if (gradeKey) {
      const gradeIdx = headers.indexOf(gradeKey);
      if (gradeIdx + 1 < headers.length && lower[gradeIdx + 1].includes('feedback')) {
        feedbackCol = headers[gradeIdx + 1];
      }
    }

    const uploaded = uploadedKey ? row[uploadedKey]?.trim() : '';
    const status = statusKey ? row[statusKey]?.trim() : '';
    const grade = gradeKey ? row[gradeKey]?.trim() : '';

    if (!uploaded && !status && !grade) {
      assessments.push({ name, type, uploaded: false, status: 'not_given', grade: null });
      continue;
    }

    assessments.push({
      name,
      type,
      uploaded: uploaded?.toLowerCase() === 'yes',
      status: parseAssessmentStatus(status || uploaded),
      grade: parseGradeValue(grade),
      feedback: feedbackCol ? row[feedbackCol]?.trim() : undefined,
    });
  }

  return {
    id: studentId,
    firstName,
    lastName,
    email,
    courseId,
    courseLength,
    attendance,
    assessments,
  };
}

function mapLegacyRow(row: Record<string, string>, headers: string[], idx: number): Student {
  const lower = headers.map(h => h.trim().toLowerCase());

  const firstName = findValue(row, headers, lower, ['first name', 'firstname']) || '';
  const lastName = findValue(row, headers, lower, ['last name', 'lastname']) || '';
  const email = findValue(row, headers, lower, ['email', 'email address']) || '';
  const courseId = findValue(row, headers, lower, ['course']) || '';

  // Parse attendance weeks (numeric: 0/50/100)
  const attendance: WeekRecord[] = [];

  // Check for semester-based weeks first
  const hasSemesters = lower.some(h => h.includes('semester'));

  if (hasSemesters) {
    for (let sem = 1; sem <= 2; sem++) {
      for (let w = 1; w <= 10; w++) {
        const key = headers.find((_, i) => lower[i] === `semester ${sem} - week${w}` || lower[i] === `semester ${sem} - week ${w}`);
        if (!key) continue;
        const value = row[key]?.trim();
        if (!value && value !== '0') continue;

        const weekNum = (sem - 1) * 10 + w;
        attendance.push({
          week: weekNum,
          status: parseLegacyAttendance(value),
        });
      }
    }
  } else {
    // Simple week columns
    for (let w = 1; w <= 20; w++) {
      const key = headers.find((_, i) => lower[i] === `week${w}` || lower[i] === `week ${w}` || lower[i] === `week${w}`);
      if (!key) continue;
      const value = row[key]?.trim();
      if (!value && value !== '0') continue;

      attendance.push({
        week: w,
        status: parseLegacyAttendance(value),
      });
    }
  }

  // Parse assessments
  const assessments: Assessment[] = [];

  if (hasSemesters) {
    // Semester-based assessments
    for (let sem = 1; sem <= 2; sem++) {
      const maxAssess = sem === 1 ? 3 : 4;
      for (let a = 1; a <= maxAssess; a++) {
        const key = headers.find((_, i) => lower[i].startsWith(`semester ${sem} - assessment ${a}`));
        if (!key) continue;
        const value = row[key]?.trim();
        if (!value) continue;

        const parsed = parseLegacyAssessmentValue(value);
        assessments.push({
          name: `Semester ${sem} Assessment ${a}`,
          type: (a === 1 || a === 3) ? 'formative' : 'summative',
          uploaded: parsed.status !== 'not_completed',
          status: parsed.status,
          grade: parsed.grade,
        });
      }
    }
  } else {
    // Simple assessment columns
    for (let a = 1; a <= 7; a++) {
      const key = headers.find((_, i) => lower[i].startsWith(`assessment ${a}`));
      if (!key) continue;
      const value = row[key]?.trim();
      if (!value) continue;

      const parsed = parseLegacyAssessmentValue(value);
      assessments.push({
        name: `Assessment ${a}`,
        type: a <= 2 ? 'formative' : 'summative',
        uploaded: parsed.status !== 'not_completed',
        status: parsed.status,
        grade: parsed.grade,
      });
    }
  }

  // Existing metrics
  const existingMetrics: ExistingMetrics = {};
  const overallPct = findValue(row, headers, lower, ['overall assessment %']);
  if (overallPct) existingMetrics.overallAssessmentPct = parseFloat(overallPct);

  const attPct = findValue(row, headers, lower, ['attendance %']);
  if (attPct) existingMetrics.attendancePct = parseFloat(attPct);

  const perf = findValue(row, headers, lower, ['assessment performance']);
  if (perf) existingMetrics.assessmentPerformance = perf;

  const engComment = findValue(row, headers, lower, ['engagement comment']);
  if (engComment) existingMetrics.engagementComment = engComment;

  const risk = findValue(row, headers, lower, ['risk rank']);
  if (risk) existingMetrics.riskRank = risk;

  const trend = findValue(row, headers, lower, ['engagement trend']);
  if (trend) existingMetrics.engagementTrend = trend;

  const dropout = findValue(row, headers, lower, ['dropout probability']);
  if (dropout) existingMetrics.dropoutProbability = parseFloat(dropout.replace(',', '.'));

  const tutorNotes = findValue(row, headers, lower, ['tutor intervention notes', 'tutor notes']);
  if (tutorNotes) existingMetrics.tutorNotes = tutorNotes;

  // Course length inferred from weeks
  const courseLength = attendance.length > 0 ? Math.max(...attendance.map(a => a.week)) : 10;

  return {
    id: `STU-${String(idx + 1).padStart(4, '0')}`,
    firstName,
    lastName,
    email,
    courseId,
    courseLength,
    attendance,
    assessments,
    existingMetrics: Object.keys(existingMetrics).length > 0 ? existingMetrics : undefined,
  };
}

// Helper functions
function findValue(row: Record<string, string>, headers: string[], lower: string[], patterns: string[]): string | undefined {
  for (const p of patterns) {
    const idx = lower.findIndex(h => h === p);
    if (idx >= 0) return row[headers[idx]]?.trim();
  }
  return undefined;
}

function parseModernAttendance(value: string): AttendanceStatus {
  const v = value.toLowerCase().trim();
  if (v === 'present') return 'present';
  if (v === 'late') return 'late';
  return 'absent';
}

function parseLegacyAttendance(value: string): AttendanceStatus {
  const num = parseInt(value);
  if (num >= 100) return 'present';
  if (num >= 50) return 'late';
  return 'absent';
}

function parseAssessmentStatus(value: string): AssessmentStatus {
  const v = value.toLowerCase().trim();
  if (v === 'completed' || v === 'yes') return 'completed';
  if (v === 'late') return 'late';
  if (v === 'not completed' || v === 'no' || v === 'not uploaded') return 'not_completed';
  return 'not_given';
}

function parseGradeValue(value: string): number | null {
  if (!value) return null;
  const v = value.trim();
  if (v.toLowerCase() === 'pass') return 60;
  if (v.toLowerCase() === 'refer') return 30;
  if (v.toLowerCase() === 'merit') return 70;
  if (v.toLowerCase() === 'distinction') return 85;
  const match = v.match(/(\d+)/);
  return match ? parseInt(match[1]) : null;
}

function parseLegacyAssessmentValue(value: string): { status: AssessmentStatus; grade: number | null } {
  if (!value) return { status: 'not_given', grade: null };
  const v = value.trim();

  // "Yes" / "No" (formative)
  if (v.toLowerCase() === 'yes') return { status: 'completed', grade: null };
  if (v.toLowerCase() === 'no') return { status: 'not_completed', grade: null };

  // "Completed (100%)", "Late (40%)", "Not Uploaded (0%)"
  const match = v.match(/(Completed|Late|Not Uploaded)\s*\((\d+)%\)/i);
  if (match) {
    const statusMap: Record<string, AssessmentStatus> = {
      'completed': 'completed',
      'late': 'late',
      'not uploaded': 'not_completed',
    };
    return {
      status: statusMap[match[1].toLowerCase()] || 'not_given',
      grade: parseInt(match[2]),
    };
  }

  return { status: 'not_given', grade: null };
}

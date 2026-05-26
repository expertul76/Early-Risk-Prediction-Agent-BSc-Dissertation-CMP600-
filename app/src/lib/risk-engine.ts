import type { Student, RiskScore, RiskLevel } from '../context/types';

export interface RiskWeights {
  attendance: number;
  assessment: number;
  engagement: number;
  sentiment: number;
}

const WEIGHTS_WITH_AI: RiskWeights = { attendance: 0.30, assessment: 0.30, engagement: 0.20, sentiment: 0.20 };
const WEIGHTS_WITHOUT_AI: RiskWeights = { attendance: 0.35, assessment: 0.35, engagement: 0.30, sentiment: 0 };

// ===== ATTENDANCE SCORE (0-100) =====
export function calculateAttendanceScore(student: Student): { score: number; explanations: string[] } {
  const weeks = student.attendance;
  const explanations: string[] = [];

  if (weeks.length === 0) {
    return { score: 50, explanations: ['No attendance data available'] };
  }

  // Base score from attendance values
  const values: number[] = weeks.map(w => w.status === 'present' ? 100 : w.status === 'late' ? 50 : 0);
  const average = values.reduce((a, b) => a + b, 0) / values.length;

  const presentCount = values.filter(v => v === 100).length;
  const lateCount = values.filter(v => v === 50).length;
  const absentCount = values.filter(v => v === 0).length;
  const attendancePct = Math.round((presentCount / weeks.length) * 100);

  explanations.push(`${attendancePct}% full attendance (${presentCount}/${weeks.length} weeks present, ${lateCount} late, ${absentCount} absent)`);

  // Trend analysis: compare first half vs second half
  let trendPenalty = 0;
  if (weeks.length >= 4) {
    const mid = Math.floor(values.length / 2);
    const earlyAvg = values.slice(0, mid).reduce((a, b) => a + b, 0) / mid;
    const recentAvg = values.slice(mid).reduce((a, b) => a + b, 0) / (values.length - mid);

    if (recentAvg < earlyAvg - 10) {
      trendPenalty = Math.min(15, (earlyAvg - recentAvg) * 0.3);
      explanations.push(`Declining attendance trend (-${Math.round(trendPenalty)} penalty)`);
    } else if (recentAvg > earlyAvg + 10) {
      explanations.push('Improving attendance trend in recent weeks');
    }
  }

  // Consecutive absence streak penalty
  let maxStreak = 0;
  let currentStreak = 0;
  for (const v of values) {
    if (v === 0) {
      currentStreak++;
      maxStreak = Math.max(maxStreak, currentStreak);
    } else {
      currentStreak = 0;
    }
  }

  let streakPenalty = 0;
  if (maxStreak >= 3) {
    streakPenalty = (maxStreak - 2) * 5;
    explanations.push(`${maxStreak} consecutive absences detected (-${streakPenalty} penalty)`);
  }

  const score = Math.max(0, Math.min(100, average - trendPenalty - streakPenalty));
  return { score: Math.round(score * 10) / 10, explanations };
}

// ===== ASSESSMENT SCORE (0-100) =====
export function calculateAssessmentScore(student: Student): { score: number; explanations: string[] } {
  const given = student.assessments.filter(a => a.status !== 'not_given');
  const explanations: string[] = [];

  if (given.length === 0) {
    return { score: 50, explanations: ['No assessment data available'] };
  }

  let totalPoints = 0;
  let totalWeight = 0;
  let completedCount = 0;
  let lateCount = 0;
  let missingCount = 0;

  for (const a of given) {
    const weight = a.type === 'summative' ? 2 : 1;
    let points = 0;

    if (a.status === 'not_completed') {
      points = 0;
      missingCount++;
    } else if (a.status === 'late') {
      const grade = a.grade ?? 40;
      points = grade * 0.85; // 15% late penalty
      lateCount++;
    } else if (a.status === 'completed') {
      points = a.grade ?? 70;
      completedCount++;
    }

    totalPoints += points * weight;
    totalWeight += 100 * weight;
  }

  const score = totalWeight > 0 ? (totalPoints / totalWeight) * 100 : 50;

  explanations.push(`${completedCount}/${given.length} assessments completed on time`);
  if (lateCount > 0) explanations.push(`${lateCount} late submission${lateCount > 1 ? 's' : ''} (15% penalty applied)`);
  if (missingCount > 0) explanations.push(`${missingCount} assessment${missingCount > 1 ? 's' : ''} not submitted`);

  // Average grade for completed assessments
  const gradedAssessments = given.filter(a => a.grade != null && a.status !== 'not_completed');
  if (gradedAssessments.length > 0) {
    const avgGrade = gradedAssessments.reduce((sum, a) => sum + (a.grade || 0), 0) / gradedAssessments.length;
    explanations.push(`Average grade: ${Math.round(avgGrade)}%`);
  }

  return { score: Math.round(Math.max(0, Math.min(100, score)) * 10) / 10, explanations };
}

// ===== ENGAGEMENT SCORE (0-100) =====
export function calculateEngagementScore(student: Student): { score: number; explanations: string[] } {
  let score = 50;
  const explanations: string[] = [];

  // Factor 1: Attendance consistency (low variance = more engaged)
  if (student.attendance.length >= 3) {
    const values: number[] = student.attendance.map(w => w.status === 'present' ? 100 : w.status === 'late' ? 50 : 0);
    const mean = values.reduce((a, b) => a + b, 0) / values.length;
    const variance = values.reduce((sum, v) => sum + Math.pow(v - mean, 2), 0) / values.length;

    if (variance < 500) {
      score += 15;
      explanations.push('Consistent attendance pattern (+15)');
    } else if (variance < 1500) {
      score += 5;
      explanations.push('Moderately consistent attendance (+5)');
    } else {
      score -= 10;
      explanations.push('Irregular attendance pattern (-10)');
    }
  }

  // Factor 2: Submission behaviour
  const givenAssessments = student.assessments.filter(a => a.status !== 'not_given');
  const lateCount = givenAssessments.filter(a => a.status === 'late').length;
  const missingCount = givenAssessments.filter(a => a.status === 'not_completed').length;

  if (lateCount > 0) {
    const penalty = lateCount * 5;
    score -= penalty;
    explanations.push(`${lateCount} late submission${lateCount > 1 ? 's' : ''} (-${penalty})`);
  }
  if (missingCount > 0) {
    const penalty = missingCount * 15;
    score -= penalty;
    explanations.push(`${missingCount} missing assessment${missingCount > 1 ? 's' : ''} (-${penalty})`);
  }

  // Factor 3: Engagement notes keyword analysis
  const allNotes = student.attendance
    .filter(w => w.engagementNote)
    .map(w => w.engagementNote!.toLowerCase())
    .join(' ');

  if (allNotes.length > 0) {
    const positiveKeywords = ['excellent', 'outstanding', 'actively', 'confident', 'insightful',
      'strong', 'engaged', 'improved', 'growth', 'encouraging', 'well-prepared', 'motivated',
      'enthusiastic', 'participating', 'collaborative'];
    const negativeKeywords = ['absent', 'struggling', 'disengaged', 'distracted', 'declined',
      'limited', 'cautious', 'difficulty', 'confused', 'hesitant', 'detached', 'withdrawn',
      'unresponsive', 'passive', 'concerning'];

    const posCount = positiveKeywords.filter(k => allNotes.includes(k)).length;
    const negCount = negativeKeywords.filter(k => allNotes.includes(k)).length;
    const netSentiment = (posCount - negCount) * 3;

    if (netSentiment !== 0) {
      score += netSentiment;
      if (netSentiment > 0) {
        explanations.push(`Positive engagement language detected (+${netSentiment})`);
      } else {
        explanations.push(`Concerning engagement language detected (${netSentiment})`);
      }
    }
  }

  // Factor 4: Existing engagement comment (legacy files)
  if (student.existingMetrics?.engagementComment) {
    const comment = student.existingMetrics.engagementComment.toLowerCase();
    if (comment.includes('high')) {
      score += 15;
      explanations.push('Pre-existing "high engagement" rating (+15)');
    } else if (comment.includes('low')) {
      score -= 15;
      explanations.push('Pre-existing "low engagement" rating (-15)');
    }
  }

  // Factor 5: Engagement trend (legacy files)
  if (student.existingMetrics?.engagementTrend) {
    const trend = student.existingMetrics.engagementTrend.toLowerCase();
    if (trend.includes('improving')) {
      score += 10;
      explanations.push('Improving engagement trend (+10)');
    } else if (trend.includes('declining')) {
      score -= 10;
      explanations.push('Declining engagement trend (-10)');
    }
  }

  return { score: Math.round(Math.max(0, Math.min(100, score)) * 10) / 10, explanations };
}

// ===== COMPOSITE RISK SCORE =====
export function calculateRiskScore(student: Student, useSentiment: boolean = false): RiskScore {
  const attendanceResult = calculateAttendanceScore(student);
  const assessmentResult = calculateAssessmentScore(student);
  const engagementResult = calculateEngagementScore(student);

  const sentimentScore = useSentiment && student.sentimentAnalysis
    ? student.sentimentAnalysis.score
    : null;

  const weights = (useSentiment && sentimentScore !== null) ? WEIGHTS_WITH_AI : WEIGHTS_WITHOUT_AI;

  const composite =
    attendanceResult.score * weights.attendance +
    assessmentResult.score * weights.assessment +
    engagementResult.score * weights.engagement +
    (sentimentScore ?? 0) * weights.sentiment;

  const classification = classifyRisk(composite);

  const explanations = [
    `Attendance: ${attendanceResult.score}/100 (weight: ${Math.round(weights.attendance * 100)}%)`,
    ...attendanceResult.explanations.map(e => `  - ${e}`),
    `Assessment: ${assessmentResult.score}/100 (weight: ${Math.round(weights.assessment * 100)}%)`,
    ...assessmentResult.explanations.map(e => `  - ${e}`),
    `Engagement: ${engagementResult.score}/100 (weight: ${Math.round(weights.engagement * 100)}%)`,
    ...engagementResult.explanations.map(e => `  - ${e}`),
  ];

  if (sentimentScore !== null) {
    explanations.push(
      `AI Sentiment: ${sentimentScore}/100 (weight: ${Math.round(weights.sentiment * 100)}%)`,
      `  - ${student.sentimentAnalysis?.summary || 'AI analysis completed'}`,
    );
  }

  explanations.push(``, `Composite Score: ${Math.round(composite * 10) / 10}/100 → ${classification.toUpperCase()} RISK`);

  return {
    composite: Math.round(composite * 10) / 10,
    classification,
    factors: {
      attendance: attendanceResult.score,
      assessment: assessmentResult.score,
      engagement: engagementResult.score,
      sentiment: sentimentScore,
    },
    explanations,
  };
}

function classifyRisk(composite: number): RiskLevel {
  if (composite >= 65) return 'low';
  if (composite >= 40) return 'medium';
  return 'high';
}

// ===== BATCH PROCESSING =====
export function calculateAllRiskScores(students: Student[], useSentiment: boolean = false): Student[] {
  return students.map(student => ({
    ...student,
    riskScore: calculateRiskScore(student, useSentiment),
  }));
}

import { useState } from 'react'
import { useApp } from '../../context/AppContext'
import type { Student } from '../../context/types'
import { User, BookOpen, CheckCircle2, Clock, XCircle, MessageSquare } from 'lucide-react'

interface Props {
  student: Student
  onClose: () => void
}

export default function StudentDetailPanel({ student }: Props) {
  const { dispatch } = useApp()
  const [reviewAgrees, setReviewAgrees] = useState<boolean | null>(
    student.teacherReview?.agrees ?? null
  )
  const [reviewNotes, setReviewNotes] = useState(student.teacherReview?.notes ?? '')

  const handleSaveReview = () => {
    dispatch({
      type: 'UPDATE_STUDENT',
      payload: {
        id: student.id,
        updates: {
          teacherReview: {
            agrees: reviewAgrees,
            notes: reviewNotes,
            reviewedAt: new Date().toISOString(),
          },
        },
      },
    })
  }

  const risk = student.riskScore

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex items-start gap-4">
        <div className="w-12 h-12 rounded-full bg-bg-tertiary flex items-center justify-center">
          <User className="w-6 h-6 text-text-muted" />
        </div>
        <div className="flex-1">
          <h3 className="text-lg font-semibold text-text-primary">
            {student.firstName} {student.lastName}
          </h3>
          <p className="text-sm text-text-muted">{student.email}</p>
          <div className="flex items-center gap-2 mt-1">
            <span className="text-xs text-text-secondary font-mono">{student.courseId}</span>
            <span className="text-xs text-text-muted">|</span>
            <span className="text-xs text-text-muted">{student.courseLength} weeks</span>
            <span className="text-xs text-text-muted">|</span>
            <span className="text-xs text-text-muted font-mono">{student.id}</span>
          </div>
        </div>
        {risk && (
          <div className="text-right">
            <span className={`badge-${risk.classification} text-sm px-3 py-1 rounded-full font-semibold`}>
              {risk.classification.toUpperCase()}
            </span>
            <p className="text-2xl font-bold text-text-primary mt-1">{risk.composite}</p>
            <p className="text-xs text-text-muted">/ 100</p>
          </div>
        )}
      </div>

      {/* Risk Factors */}
      {risk && (
        <div className="glass-card p-4 space-y-3">
          <h4 className="text-sm font-medium text-text-primary">Risk Factor Breakdown</h4>
          {([
            { label: 'Attendance', value: risk.factors.attendance, color: 'bg-blue-500' },
            { label: 'Assessment', value: risk.factors.assessment, color: 'bg-purple-500' },
            { label: 'Engagement', value: risk.factors.engagement, color: 'bg-cyan-500' },
            ...(risk.factors.sentiment != null
              ? [{ label: 'AI Sentiment', value: risk.factors.sentiment, color: 'bg-amber-500' }]
              : []),
          ]).map(f => (
            <div key={f.label}>
              <div className="flex items-center justify-between text-xs mb-1">
                <span className="text-text-secondary">{f.label}</span>
                <span className="text-text-primary font-medium">{f.value}/100</span>
              </div>
              <div className="h-2 bg-bg-tertiary rounded-full overflow-hidden">
                <div
                  className={`h-full rounded-full transition-all ${f.color}`}
                  style={{ width: `${f.value}%`, opacity: 0.7 }}
                />
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Attendance Heatmap */}
      <div className="glass-card p-4">
        <h4 className="text-sm font-medium text-text-primary mb-3">
          Attendance ({student.attendance.length} weeks)
        </h4>
        <div className="flex flex-wrap gap-1.5">
          {student.attendance.map(w => (
            <div
              key={w.week}
              title={`Week ${w.week}: ${w.status}${w.engagementNote ? ` - ${w.engagementNote}` : ''}`}
              className={`w-8 h-8 rounded-lg flex items-center justify-center text-xs font-medium cursor-help ${
                w.status === 'present'
                  ? 'bg-risk-low/20 text-risk-low'
                  : w.status === 'late'
                  ? 'bg-risk-medium/20 text-risk-medium'
                  : 'bg-risk-high/20 text-risk-high'
              }`}
            >
              {w.week}
            </div>
          ))}
        </div>
        <div className="flex items-center gap-4 mt-3 text-xs text-text-muted">
          <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded bg-risk-low/40" /> Present</span>
          <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded bg-risk-medium/40" /> Late</span>
          <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded bg-risk-high/40" /> Absent</span>
        </div>
      </div>

      {/* Assessments */}
      <div className="glass-card p-4">
        <h4 className="text-sm font-medium text-text-primary mb-3">
          <BookOpen className="w-4 h-4 inline mr-1" />
          Assessments
        </h4>
        <div className="space-y-2">
          {student.assessments.filter(a => a.status !== 'not_given').map((a, i) => (
            <div key={i} className="flex items-center justify-between py-2 border-b border-border/30 last:border-0">
              <div className="flex items-center gap-2">
                {a.status === 'completed' && <CheckCircle2 className="w-4 h-4 text-risk-low" />}
                {a.status === 'late' && <Clock className="w-4 h-4 text-risk-medium" />}
                {a.status === 'not_completed' && <XCircle className="w-4 h-4 text-risk-high" />}
                <div>
                  <p className="text-sm text-text-primary">{a.name}</p>
                  <p className="text-xs text-text-muted capitalize">{a.type}</p>
                </div>
              </div>
              <div className="text-right">
                <p className="text-sm font-medium text-text-primary">
                  {a.grade != null ? `${a.grade}%` : a.status === 'completed' ? 'Pass' : '-'}
                </p>
                <p className={`text-xs capitalize ${
                  a.status === 'completed' ? 'text-risk-low' : a.status === 'late' ? 'text-risk-medium' : 'text-risk-high'
                }`}>
                  {a.status.replace('_', ' ')}
                </p>
              </div>
            </div>
          ))}
          {student.assessments.filter(a => a.status !== 'not_given').length === 0 && (
            <p className="text-sm text-text-muted text-center py-4">No assessments recorded</p>
          )}
        </div>
      </div>

      {/* Engagement Notes */}
      {student.attendance.some(w => w.engagementNote) && (
        <div className="glass-card p-4">
          <h4 className="text-sm font-medium text-text-primary mb-3">
            <MessageSquare className="w-4 h-4 inline mr-1" />
            Engagement Notes
          </h4>
          <div className="space-y-2 max-h-48 overflow-y-auto">
            {student.attendance.filter(w => w.engagementNote).map(w => (
              <div key={w.week} className="text-xs">
                <span className="text-text-muted font-mono">Week {w.week}:</span>{' '}
                <span className="text-text-secondary">{w.engagementNote}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* AI Sentiment */}
      {student.sentimentAnalysis && (
        <div className="glass-card p-4">
          <h4 className="text-sm font-medium text-text-primary mb-2">AI Sentiment Analysis</h4>
          <div className="flex items-center gap-3">
            <div className={`text-lg font-bold ${
              student.sentimentAnalysis.score >= 65 ? 'text-risk-low' :
              student.sentimentAnalysis.score >= 40 ? 'text-risk-medium' : 'text-risk-high'
            }`}>
              {student.sentimentAnalysis.score}/100
            </div>
            <p className="text-xs text-text-secondary">{student.sentimentAnalysis.summary}</p>
          </div>
        </div>
      )}

      {/* Risk Explanations */}
      {risk && risk.explanations.length > 0 && (
        <div className="glass-card p-4">
          <h4 className="text-sm font-medium text-text-primary mb-3">Risk Analysis Detail</h4>
          <div className="text-xs text-text-secondary space-y-0.5 font-mono leading-relaxed">
            {risk.explanations.map((e, i) => (
              <p key={i} className={e.startsWith('  ') ? 'pl-4 text-text-muted' : 'font-medium text-text-secondary mt-2 first:mt-0'}>
                {e}
              </p>
            ))}
          </div>
        </div>
      )}

      {/* Teacher Review */}
      <div className="glass-card p-4">
        <h4 className="text-sm font-medium text-text-primary mb-3">Teacher Review</h4>
        <p className="text-xs text-text-muted mb-3">
          Do you agree with this risk classification?
        </p>
        <div className="flex gap-3 mb-3">
          <button
            onClick={() => setReviewAgrees(true)}
            className={`flex-1 py-2 text-sm rounded-xl border transition-colors ${
              reviewAgrees === true
                ? 'bg-risk-low/15 border-risk-low/30 text-risk-low'
                : 'border-border text-text-muted hover:border-border-hover'
            }`}
          >
            Agree
          </button>
          <button
            onClick={() => setReviewAgrees(false)}
            className={`flex-1 py-2 text-sm rounded-xl border transition-colors ${
              reviewAgrees === false
                ? 'bg-risk-high/15 border-risk-high/30 text-risk-high'
                : 'border-border text-text-muted hover:border-border-hover'
            }`}
          >
            Disagree
          </button>
        </div>
        <textarea
          value={reviewNotes}
          onChange={e => setReviewNotes(e.target.value)}
          placeholder="Optional notes about this student..."
          rows={3}
          className="w-full bg-bg-tertiary border border-border rounded-xl px-3 py-2 text-sm text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent/50 resize-none"
        />
        <button
          onClick={handleSaveReview}
          disabled={reviewAgrees === null}
          className="mt-3 w-full py-2 bg-accent hover:bg-accent-light text-white text-sm font-medium rounded-xl transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
        >
          Save Review
        </button>
      </div>
    </div>
  )
}

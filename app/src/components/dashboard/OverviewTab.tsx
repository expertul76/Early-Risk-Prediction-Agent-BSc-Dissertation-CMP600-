import { useMemo } from 'react'
import { useApp } from '../../context/AppContext'
import { Users, AlertTriangle, AlertCircle, TrendingUp, Brain } from 'lucide-react'
import RiskDistributionChart from '../charts/RiskDistributionChart'
import AttendanceTrendChart from '../charts/AttendanceTrendChart'
import { calculateAllRiskScores } from '../../lib/risk-engine'
import { analyseSentimentBatch } from '../../lib/sentiment'
import type { Student } from '../../context/types'

interface Props {
  students: Student[]
}

export default function OverviewTab({ students }: Props) {
  const { state, dispatch } = useApp()

  const stats = useMemo(() => {
    const high = students.filter(s => s.riskScore?.classification === 'high').length
    const medium = students.filter(s => s.riskScore?.classification === 'medium').length
    const low = students.filter(s => s.riskScore?.classification === 'low').length

    const avgAttendance = students.length > 0
      ? Math.round(students.reduce((sum, s) => {
          const present = s.attendance.filter(w => w.status === 'present').length
          return sum + (s.attendance.length > 0 ? (present / s.attendance.length) * 100 : 0)
        }, 0) / students.length)
      : 0

    return { total: students.length, high, medium, low, avgAttendance }
  }, [students])

  const topRiskStudents = useMemo(() => {
    return [...students]
      .filter(s => s.riskScore)
      .sort((a, b) => (a.riskScore!.composite) - (b.riskScore!.composite))
      .slice(0, 8)
  }, [students])

  const hasEngagementNotes = useMemo(() => {
    return state.students.some(s => s.attendance.some(w => w.engagementNote))
  }, [state.students])

  const hasSentiment = useMemo(() => {
    return state.students.some(s => s.sentimentAnalysis)
  }, [state.students])

  const handleRunAI = async () => {
    dispatch({ type: 'SET_PROCESSING', payload: true })
    try {
      const updated = await analyseSentimentBatch(state.students)
      const scored = calculateAllRiskScores(updated, true)
      dispatch({ type: 'SET_STUDENTS', payload: scored })
    } catch (err) {
      dispatch({ type: 'SET_ERROR', payload: err instanceof Error ? err.message : 'AI analysis failed' })
    } finally {
      dispatch({ type: 'SET_PROCESSING', payload: false })
    }
  }

  return (
    <div className="space-y-6">
      {/* Stat Cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { label: 'Total Students', value: stats.total, icon: Users, color: 'text-accent-light' },
          { label: 'High Risk', value: stats.high, icon: AlertCircle, color: 'text-risk-high' },
          { label: 'Medium Risk', value: stats.medium, icon: AlertTriangle, color: 'text-risk-medium' },
          { label: 'Avg Attendance', value: `${stats.avgAttendance}%`, icon: TrendingUp, color: 'text-risk-low' },
        ].map(card => {
          const Icon = card.icon
          return (
            <div key={card.label} className="glass-card p-5">
              <div className="flex items-center justify-between mb-2">
                <span className="text-xs text-text-muted">{card.label}</span>
                <Icon className={`w-4 h-4 ${card.color}`} />
              </div>
              <p className={`text-2xl font-bold ${card.color}`}>{card.value}</p>
            </div>
          )
        })}
      </div>

      {/* AI Enhancement Banner */}
      {hasEngagementNotes && !hasSentiment && (
        <div className="glass-card p-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Brain className="w-5 h-5 text-accent" />
            <div>
              <p className="text-sm font-medium text-text-primary">AI Sentiment Analysis</p>
              <p className="text-xs text-text-muted">
                {state.isProcessing
                  ? 'Analysing engagement notes with Gemini AI...'
                  : 'AI analysis is running automatically. Results will update shortly.'}
              </p>
            </div>
          </div>
          {!state.isProcessing && (
            <button
              onClick={handleRunAI}
              className="px-4 py-2 bg-accent hover:bg-accent-light text-white text-sm font-medium rounded-xl transition-colors"
            >
              Re-run Analysis
            </button>
          )}
          {state.isProcessing && (
            <div className="w-5 h-5 border-2 border-accent border-t-transparent rounded-full animate-spin" />
          )}
        </div>
      )}

      {hasSentiment && (
        <div className="glass-card p-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Brain className="w-5 h-5 text-risk-low" />
            <div>
              <p className="text-sm font-medium text-text-primary">AI Sentiment Analysis Complete</p>
              <p className="text-xs text-text-muted">
                Risk scores have been enhanced with Gemini AI engagement analysis.
              </p>
            </div>
          </div>
          <button
            onClick={handleRunAI}
            disabled={state.isProcessing}
            className="px-4 py-2 text-sm text-text-secondary hover:text-text-primary border border-border hover:border-accent/30 rounded-xl transition-all disabled:opacity-50"
          >
            Re-run
          </button>
        </div>
      )}

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <div className="lg:col-span-2 glass-card p-5">
          <h3 className="text-sm font-medium text-text-primary mb-4">Risk Distribution</h3>
          <RiskDistributionChart
            low={stats.low}
            medium={stats.medium}
            high={stats.high}
          />
        </div>

        <div className="lg:col-span-3 glass-card p-5">
          <h3 className="text-sm font-medium text-text-primary mb-4">Attendance Trend (Cohort Average)</h3>
          <AttendanceTrendChart students={students} />
        </div>
      </div>

      {/* Top Risk Students */}
      <div className="glass-card p-5">
        <h3 className="text-sm font-medium text-text-primary mb-4">Highest Risk Students</h3>
        {topRiskStudents.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-text-muted text-xs border-b border-border">
                  <th className="pb-2 font-medium">Student</th>
                  <th className="pb-2 font-medium">Course</th>
                  <th className="pb-2 font-medium">Attendance</th>
                  <th className="pb-2 font-medium">Assessment</th>
                  <th className="pb-2 font-medium">Engagement</th>
                  <th className="pb-2 font-medium">Composite</th>
                  <th className="pb-2 font-medium">Risk</th>
                </tr>
              </thead>
              <tbody>
                {topRiskStudents.map(s => (
                  <tr key={s.id} className="border-b border-border/50 hover:bg-bg-card">
                    <td className="py-2.5 text-text-primary">
                      {s.firstName} {s.lastName}
                    </td>
                    <td className="py-2.5 text-text-secondary font-mono text-xs">{s.courseId}</td>
                    <td className="py-2.5 text-text-secondary">{s.riskScore?.factors.attendance ?? '-'}</td>
                    <td className="py-2.5 text-text-secondary">{s.riskScore?.factors.assessment ?? '-'}</td>
                    <td className="py-2.5 text-text-secondary">{s.riskScore?.factors.engagement ?? '-'}</td>
                    <td className="py-2.5 font-medium text-text-primary">{s.riskScore?.composite ?? '-'}</td>
                    <td className="py-2.5">
                      <span className={`badge-${s.riskScore?.classification || 'medium'} text-xs px-2 py-0.5 rounded-full font-medium`}>
                        {s.riskScore?.classification?.toUpperCase() || 'N/A'}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <p className="text-sm text-text-muted text-center py-6">No students match the current filters</p>
        )}
      </div>

      {state.error && (
        <div className="flex items-center gap-2 px-4 py-3 rounded-xl bg-risk-high/10 border border-risk-high/20 text-risk-high text-sm">
          <AlertCircle className="w-4 h-4 shrink-0" />
          {state.error}
        </div>
      )}
    </div>
  )
}

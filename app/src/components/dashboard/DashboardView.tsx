import { useState, useMemo } from 'react'
import { useApp } from '../../context/AppContext'
import { BarChart3, Users, Download, X, Filter, Save, RefreshCw, Check } from 'lucide-react'
import { apiSaveReport, apiUpdateReport } from '../../lib/api'
import OverviewTab from './OverviewTab'
import StudentsTab from './StudentsTab'
import ExportTab from './ExportTab'
import type { RiskLevel, Student } from '../../context/types'

type Tab = 'overview' | 'students' | 'export'

const tabs: { id: Tab; label: string; icon: typeof BarChart3 }[] = [
  { id: 'overview', label: 'Overview', icon: BarChart3 },
  { id: 'students', label: 'Students', icon: Users },
  { id: 'export', label: 'Export', icon: Download },
]

export interface DashboardFilters {
  course: string
  risk: RiskLevel | 'all'
  weekUpTo: number | null
}

export default function DashboardView() {
  const { state, dispatch } = useApp()
  const [activeTab, setActiveTab] = useState<Tab>('overview')
  const [filters, setFilters] = useState<DashboardFilters>({
    course: 'all',
    risk: 'all',
    weekUpTo: null,
  })

  // Save report state
  const [showSaveDialog, setShowSaveDialog] = useState(false)
  const [saveName, setSaveName] = useState('')
  const [saving, setSaving] = useState(false)
  const [saveSuccess, setSaveSuccess] = useState(false)

  const courses = useMemo(() => {
    const set = new Set(state.students.map(s => s.courseId))
    return Array.from(set).sort()
  }, [state.students])

  const maxWeek = useMemo(() => {
    return Math.max(...state.students.flatMap(s => s.attendance.map(w => w.week)), 0)
  }, [state.students])

  const filtered: Student[] = useMemo(() => {
    let result = state.students

    if (filters.course !== 'all') {
      result = result.filter(s => s.courseId === filters.course)
    }

    if (filters.risk !== 'all') {
      result = result.filter(s => s.riskScore?.classification === filters.risk)
    }

    if (filters.weekUpTo !== null) {
      result = result.map(s => ({
        ...s,
        attendance: s.attendance.filter(w => w.week <= filters.weekUpTo!),
      }))
    }

    return result
  }, [state.students, filters])

  const hasActiveFilters = filters.course !== 'all' || filters.risk !== 'all' || filters.weekUpTo !== null
  const clearFilters = () => setFilters({ course: 'all', risk: 'all', weekUpTo: null })

  // Save as new report
  const handleSaveNew = async () => {
    if (!saveName.trim()) return
    setSaving(true)
    try {
      const result = await apiSaveReport(saveName.trim(), state.students)
      dispatch({ type: 'SET_SAVED', payload: { id: result.reportId, name: saveName.trim() } })
      setShowSaveDialog(false)
      setSaveName('')
      flashSaveSuccess()
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to save report')
    } finally {
      setSaving(false)
    }
  }

  // Update existing report
  const handleUpdateExisting = async () => {
    if (!state.currentReportId) return
    setSaving(true)
    try {
      const result = await apiUpdateReport(state.currentReportId, state.students)
      dispatch({ type: 'SET_SAVED', payload: { id: state.currentReportId, name: state.currentReportName! } })
      flashSaveSuccess()
      if (result.matched_courses.length > 0 || result.new_courses.length > 0) {
        const parts: string[] = []
        if (result.matched_courses.length > 0) parts.push(`Updated: ${result.matched_courses.join(', ')}`)
        if (result.new_courses.length > 0) parts.push(`New: ${result.new_courses.join(', ')}`)
        console.log('Report update:', parts.join(' | '))
      }
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to update report')
    } finally {
      setSaving(false)
    }
  }

  const flashSaveSuccess = () => {
    setSaveSuccess(true)
    setTimeout(() => setSaveSuccess(false), 2000)
  }

  return (
    <div>
      {/* Save Bar */}
      <div className="glass-card p-4 mb-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            {state.currentReportName ? (
              <span className="text-sm text-text-primary font-medium">
                {state.currentReportName}
              </span>
            ) : (
              <span className="text-sm text-text-muted italic">Unsaved report</span>
            )}
            {state.isSaved && (
              <span className="text-xs px-2 py-0.5 rounded-full bg-risk-low/15 text-risk-low">Saved</span>
            )}
            {!state.isSaved && state.currentReportId && (
              <span className="text-xs px-2 py-0.5 rounded-full bg-yellow-500/15 text-yellow-400">Unsaved changes</span>
            )}
          </div>

          <div className="flex items-center gap-2">
            {saveSuccess && (
              <span className="flex items-center gap-1 text-xs text-risk-low">
                <Check className="w-3.5 h-3.5" />
                Saved
              </span>
            )}

            {/* Update existing report */}
            {state.currentReportId && (
              <button
                onClick={handleUpdateExisting}
                disabled={saving}
                className="flex items-center gap-1.5 px-3 py-1.5 text-xs text-text-secondary hover:text-text-primary bg-bg-secondary border border-border-primary rounded-lg hover:border-accent-purple/40 transition-colors disabled:opacity-50"
              >
                {saving ? <RefreshCw className="w-3.5 h-3.5 animate-spin" /> : <Save className="w-3.5 h-3.5" />}
                Save
              </button>
            )}

            {/* Save as new */}
            <button
              onClick={() => setShowSaveDialog(true)}
              className="flex items-center gap-1.5 px-3 py-1.5 text-xs text-white bg-accent-purple rounded-lg hover:bg-accent-purple/90 transition-colors"
            >
              <Save className="w-3.5 h-3.5" />
              {state.currentReportId ? 'Save as New' : 'Save Report'}
            </button>
          </div>
        </div>

        {/* Save dialog */}
        {showSaveDialog && (
          <div className="mt-3 pt-3 border-t border-border-primary flex items-center gap-3">
            <input
              type="text"
              value={saveName}
              onChange={e => setSaveName(e.target.value)}
              placeholder="Report name (e.g. Term 1 Week 8 Analysis)"
              className="flex-1 px-3 py-2 rounded-lg bg-bg-secondary border border-border-primary text-text-primary text-sm placeholder:text-text-muted focus:outline-none focus:border-accent-purple"
              autoFocus
              onKeyDown={e => e.key === 'Enter' && handleSaveNew()}
            />
            <button
              onClick={handleSaveNew}
              disabled={saving || !saveName.trim()}
              className="px-4 py-2 text-sm bg-accent-purple text-white rounded-lg hover:bg-accent-purple/90 disabled:opacity-50 transition-colors"
            >
              {saving ? 'Saving...' : 'Save'}
            </button>
            <button
              onClick={() => { setShowSaveDialog(false); setSaveName('') }}
              className="px-3 py-2 text-sm text-text-muted hover:text-text-primary transition-colors"
            >
              Cancel
            </button>
          </div>
        )}
      </div>

      {/* Filter Bar */}
      <div className="glass-card p-4 mb-6">
        <div className="flex flex-wrap items-center gap-3">
          <Filter className="w-4 h-4 text-text-muted shrink-0" />

          <div className="flex flex-col gap-1">
            <label className="text-xs text-text-muted">Course</label>
            <select
              value={filters.course}
              onChange={e => setFilters(f => ({ ...f, course: e.target.value }))}
              className="bg-bg-tertiary border border-border rounded-lg px-3 py-1.5 text-sm text-text-secondary focus:outline-none focus:border-accent/50 min-w-[140px]"
            >
              <option value="all">All Courses</option>
              {courses.map(c => <option key={c} value={c}>{c}</option>)}
            </select>
          </div>

          <div className="flex flex-col gap-1">
            <label className="text-xs text-text-muted">Risk Level</label>
            <select
              value={filters.risk}
              onChange={e => setFilters(f => ({ ...f, risk: e.target.value as RiskLevel | 'all' }))}
              className="bg-bg-tertiary border border-border rounded-lg px-3 py-1.5 text-sm text-text-secondary focus:outline-none focus:border-accent/50 min-w-[140px]"
            >
              <option value="all">All Levels</option>
              <option value="high">High Risk</option>
              <option value="medium">Medium Risk</option>
              <option value="low">Low Risk</option>
            </select>
          </div>

          <div className="flex flex-col gap-1">
            <label className="text-xs text-text-muted">
              Up to Week {filters.weekUpTo ?? maxWeek}
            </label>
            <div className="flex items-center gap-2">
              <input
                type="range"
                min={1}
                max={maxWeek}
                value={filters.weekUpTo ?? maxWeek}
                onChange={e => {
                  const val = parseInt(e.target.value)
                  setFilters(f => ({ ...f, weekUpTo: val === maxWeek ? null : val }))
                }}
                className="w-[120px] accent-accent"
              />
              <span className="text-xs text-text-secondary font-mono w-6 text-center">
                {filters.weekUpTo ?? maxWeek}
              </span>
            </div>
          </div>

          <div className="flex items-center gap-2 ml-auto">
            {hasActiveFilters && (
              <>
                <span className="text-xs text-accent-light">
                  {filtered.length}/{state.students.length} students
                </span>
                <button
                  onClick={clearFilters}
                  className="flex items-center gap-1 px-3 py-1.5 text-xs text-risk-high bg-risk-high/10 border border-risk-high/20 rounded-lg hover:bg-risk-high/20 transition-colors"
                >
                  <X className="w-3 h-3" />
                  Clear Filters
                </button>
              </>
            )}
            {!hasActiveFilters && (
              <span className="text-xs text-text-muted">
                {state.students.length} students (no filters)
              </span>
            )}
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="flex items-center gap-1 mb-6 border-b border-border">
        {tabs.map(tab => {
          const Icon = tab.icon
          return (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px ${
                activeTab === tab.id
                  ? 'border-accent text-accent-light'
                  : 'border-transparent text-text-muted hover:text-text-secondary'
              }`}
            >
              <Icon className="w-4 h-4" />
              {tab.label}
            </button>
          )
        })}
      </div>

      {activeTab === 'overview' && <OverviewTab students={filtered} />}
      {activeTab === 'students' && <StudentsTab students={filtered} />}
      {activeTab === 'export' && <ExportTab />}
    </div>
  )
}

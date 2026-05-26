import { useState, useEffect } from 'react'
import { apiListReports, apiDeleteReport, type ReportSummary } from '../../lib/api'
import { useAuth } from '../../context/AuthContext'
import {
  FileText, Upload, Trash2, Clock, Users, AlertTriangle,
  BookOpen, RefreshCw, LogOut, ChevronRight
} from 'lucide-react'

interface Props {
  onUploadNew: () => void
  onLoadReport: (reportId: number) => void
}

export default function ReportsView({ onUploadNew, onLoadReport }: Props) {
  const { user, logout } = useAuth()
  const [reports, setReports] = useState<ReportSummary[]>([])
  const [loading, setLoading] = useState(true)
  const [deleting, setDeleting] = useState<number | null>(null)

  const fetchReports = async () => {
    setLoading(true)
    try {
      const data = await apiListReports()
      setReports(data)
    } catch (err) {
      console.error('Failed to load reports:', err)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    fetchReports()
  }, [])

  const handleDelete = async (id: number, name: string) => {
    if (!confirm(`Delete report "${name}"? This cannot be undone.`)) return
    setDeleting(id)
    try {
      await apiDeleteReport(id)
      setReports(prev => prev.filter(r => r.id !== id))
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to delete')
    } finally {
      setDeleting(null)
    }
  }

  const handleLoad = async (id: number) => {
    try {
      setLoading(true)
      onLoadReport(id)
    } finally {
      setLoading(false)
    }
  }

  const formatDate = (dateStr: string) => {
    const d = new Date(dateStr)
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
  }

  const formatTime = (dateStr: string) => {
    const d = new Date(dateStr)
    return d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
  }

  return (
    <div className="max-w-4xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-2xl font-bold text-text-primary">My Reports</h1>
          <p className="text-text-secondary mt-1">
            Welcome back, <span className="text-accent-purple">{user?.name}</span>
          </p>
        </div>
        <div className="flex items-center gap-3">
          <button
            onClick={onUploadNew}
            className="flex items-center gap-2 px-4 py-2 rounded-lg bg-accent-purple text-white font-medium hover:bg-accent-purple/90 transition-colors"
          >
            <Upload className="w-4 h-4" />
            Upload CSV
          </button>
          <button
            onClick={logout}
            className="flex items-center gap-2 px-3 py-2 rounded-lg bg-bg-secondary border border-border-primary text-text-secondary hover:text-text-primary transition-colors"
            title="Sign out"
          >
            <LogOut className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Reports List */}
      {loading ? (
        <div className="flex items-center justify-center py-20">
          <RefreshCw className="w-6 h-6 text-accent-purple animate-spin" />
        </div>
      ) : reports.length === 0 ? (
        <div className="glass-card p-12 text-center">
          <FileText className="w-12 h-12 text-text-muted mx-auto mb-4" />
          <h2 className="text-lg font-semibold text-text-primary mb-2">No reports yet</h2>
          <p className="text-text-secondary mb-6">Upload a CSV file to create your first risk analysis report.</p>
          <button
            onClick={onUploadNew}
            className="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-accent-purple text-white font-medium hover:bg-accent-purple/90 transition-colors"
          >
            <Upload className="w-4 h-4" />
            Upload CSV
          </button>
        </div>
      ) : (
        <div className="space-y-3">
          {reports.map(report => (
            <div
              key={report.id}
              className="glass-card p-4 hover:border-accent-purple/40 transition-colors group"
            >
              <div className="flex items-center justify-between">
                <div className="flex-1 min-w-0">
                  <button
                    onClick={() => handleLoad(report.id)}
                    className="text-left w-full"
                  >
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-lg bg-accent-purple/20 flex items-center justify-center shrink-0">
                        <FileText className="w-5 h-5 text-accent-purple" />
                      </div>
                      <div className="min-w-0">
                        <h3 className="font-semibold text-text-primary truncate group-hover:text-accent-purple transition-colors">
                          {report.name}
                        </h3>
                        <div className="flex items-center gap-4 mt-1 text-xs text-text-muted">
                          <span className="flex items-center gap-1">
                            <Clock className="w-3 h-3" />
                            {formatDate(report.updated_at)} at {formatTime(report.updated_at)}
                          </span>
                          <span className="flex items-center gap-1">
                            <BookOpen className="w-3 h-3" />
                            {report.course_count} course{report.course_count !== 1 ? 's' : ''}
                          </span>
                          <span className="flex items-center gap-1">
                            <Users className="w-3 h-3" />
                            {report.student_count} student{report.student_count !== 1 ? 's' : ''}
                          </span>
                          {Number(report.high_risk_count) > 0 && (
                            <span className="flex items-center gap-1 text-red-400">
                              <AlertTriangle className="w-3 h-3" />
                              {report.high_risk_count} high risk
                            </span>
                          )}
                        </div>
                      </div>
                    </div>

                    {/* Course tags */}
                    {report.courses && report.courses.length > 0 && (
                      <div className="flex flex-wrap gap-1.5 mt-2 ml-[52px]">
                        {report.courses.map(c => (
                          <span
                            key={c.course_code}
                            className="px-2 py-0.5 text-xs rounded bg-bg-secondary border border-border-primary text-text-secondary"
                          >
                            {c.course_code} ({c.weeks_loaded}/{c.course_length}w)
                          </span>
                        ))}
                      </div>
                    )}
                  </button>
                </div>

                <div className="flex items-center gap-2 ml-4 shrink-0">
                  <button
                    onClick={() => handleDelete(report.id, report.name)}
                    disabled={deleting === report.id}
                    className="p-2 rounded-lg text-text-muted hover:text-red-400 hover:bg-red-500/10 transition-colors"
                    title="Delete report"
                  >
                    {deleting === report.id ? (
                      <RefreshCw className="w-4 h-4 animate-spin" />
                    ) : (
                      <Trash2 className="w-4 h-4" />
                    )}
                  </button>
                  <button
                    onClick={() => handleLoad(report.id)}
                    className="p-2 rounded-lg text-text-muted hover:text-accent-purple transition-colors"
                  >
                    <ChevronRight className="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

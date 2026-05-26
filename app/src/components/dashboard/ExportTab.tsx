import { useApp } from '../../context/AppContext'
import { exportStudentsCSV } from '../../lib/export-csv'
import { exportStudentsPDF } from '../../lib/export-pdf'
import { FileSpreadsheet, FileText, Download } from 'lucide-react'

export default function ExportTab() {
  const { state } = useApp()

  const handleCSV = () => {
    const csv = exportStudentsCSV(state.students)
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `risk-analysis-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
  }

  const handlePDF = () => {
    exportStudentsPDF(state.students)
  }

  const reviewedCount = state.students.filter(s => s.teacherReview?.agrees !== null && s.teacherReview?.agrees !== undefined).length
  const highRisk = state.students.filter(s => s.riskScore?.classification === 'high').length
  const hasSentiment = state.students.some(s => s.sentimentAnalysis)

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <div className="text-center mb-8">
        <h2 className="text-xl font-semibold text-text-primary mb-2">Export Results</h2>
        <p className="text-sm text-text-secondary">
          Download the analysis results for reporting or further review.
        </p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {/* CSV Export */}
        <div className="glass-card p-6 glass-card-hover">
          <FileSpreadsheet className="w-8 h-8 text-risk-low mb-3" />
          <h3 className="text-sm font-semibold text-text-primary mb-1">CSV Spreadsheet</h3>
          <p className="text-xs text-text-muted mb-4">
            All students with risk scores, factor breakdowns, and teacher reviews.
            Opens in Excel, Google Sheets, etc.
          </p>
          <button
            onClick={handleCSV}
            className="flex items-center gap-2 px-4 py-2 bg-risk-low/15 text-risk-low border border-risk-low/20 rounded-xl text-sm font-medium hover:bg-risk-low/25 transition-colors"
          >
            <Download className="w-4 h-4" />
            Download CSV
          </button>
        </div>

        {/* PDF Export */}
        <div className="glass-card p-6 glass-card-hover">
          <FileText className="w-8 h-8 text-accent mb-3" />
          <h3 className="text-sm font-semibold text-text-primary mb-1">PDF Report</h3>
          <p className="text-xs text-text-muted mb-4">
            Formatted report with risk summary and detailed profiles for high-risk students.
            Suitable for sharing with stakeholders.
          </p>
          <button
            onClick={handlePDF}
            className="flex items-center gap-2 px-4 py-2 bg-accent/15 text-accent-light border border-accent/20 rounded-xl text-sm font-medium hover:bg-accent/25 transition-colors"
          >
            <Download className="w-4 h-4" />
            Download PDF
          </button>
        </div>
      </div>

      {/* Summary */}
      <div className="glass-card p-5">
        <h4 className="text-sm font-medium text-text-primary mb-3">Export Summary</h4>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
          <div>
            <p className="text-lg font-bold text-text-primary">{state.students.length}</p>
            <p className="text-xs text-text-muted">Students</p>
          </div>
          <div>
            <p className="text-lg font-bold text-risk-high">{highRisk}</p>
            <p className="text-xs text-text-muted">High Risk</p>
          </div>
          <div>
            <p className="text-lg font-bold text-accent-light">{reviewedCount}</p>
            <p className="text-xs text-text-muted">Reviewed</p>
          </div>
          <div>
            <p className="text-lg font-bold text-text-primary">{hasSentiment ? 'Yes' : 'No'}</p>
            <p className="text-xs text-text-muted">AI Analysis</p>
          </div>
        </div>
      </div>
    </div>
  )
}

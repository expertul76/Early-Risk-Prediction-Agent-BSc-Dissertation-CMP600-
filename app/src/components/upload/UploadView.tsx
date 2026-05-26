import { useCallback, useState } from 'react'
import { useApp } from '../../context/AppContext'
import { parseCSV } from '../../lib/csv-parser'
import { Upload, FileSpreadsheet, Database, AlertCircle } from 'lucide-react'

export default function UploadView() {
  const { dispatch } = useApp()
  const [isDragging, setIsDragging] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [isLoading, setIsLoading] = useState(false)

  const handleFile = useCallback(async (file: File) => {
    if (!file.name.endsWith('.csv')) {
      setError('Please upload a CSV file')
      return
    }

    setError(null)
    setIsLoading(true)

    try {
      const result = await parseCSV(file)
      if (result.data.length === 0) {
        setError('The CSV file appears to be empty')
        return
      }
      dispatch({ type: 'SET_RAW_DATA', payload: { data: result.data, schema: result.schema } })
      dispatch({ type: 'SET_STEP', payload: 'mapping' })
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to parse CSV')
    } finally {
      setIsLoading(false)
    }
  }, [dispatch])

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    setIsDragging(false)
    const file = e.dataTransfer.files[0]
    if (file) handleFile(file)
  }, [handleFile])

  const handleFileInput = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) handleFile(file)
  }, [handleFile])

  const loadSampleData = useCallback(async () => {
    setIsLoading(true)
    setError(null)
    try {
      const { getSampleCSV } = await import('../../data/sample-data')
      const blob = new Blob([getSampleCSV()], { type: 'text/csv' })
      const file = new File([blob], 'SampleData_30.csv', { type: 'text/csv' })
      await handleFile(file)
    } catch (err) {
      setError('Failed to load sample data')
    } finally {
      setIsLoading(false)
    }
  }, [handleFile])

  return (
    <div className="flex flex-col items-center justify-center min-h-[70vh] max-w-xl mx-auto">
      <div className="text-center mb-8">
        <h2 className="text-3xl font-bold gradient-text mb-3">
          Student Risk Analysis
        </h2>
        <p className="text-text-secondary text-sm leading-relaxed max-w-md mx-auto">
          Upload a student data CSV to analyse attendance, assessments, and engagement patterns.
          The system will identify students at risk of underperforming.
        </p>
      </div>

      <div
        onDragOver={e => { e.preventDefault(); setIsDragging(true) }}
        onDragLeave={() => setIsDragging(false)}
        onDrop={handleDrop}
        className={`w-full glass-card p-12 text-center cursor-pointer transition-all ${
          isDragging
            ? 'border-accent bg-accent-glow shadow-[0_0_60px_rgba(99,102,241,0.15)]'
            : 'hover:border-border-hover'
        } ${isLoading ? 'pointer-events-none opacity-60' : ''}`}
        onClick={() => document.getElementById('csv-input')?.click()}
      >
        <input
          id="csv-input"
          type="file"
          accept=".csv"
          onChange={handleFileInput}
          className="hidden"
        />

        {isLoading ? (
          <div className="flex flex-col items-center gap-3">
            <div className="w-10 h-10 border-2 border-accent border-t-transparent rounded-full animate-spin" />
            <p className="text-sm text-text-secondary">Parsing CSV...</p>
          </div>
        ) : (
          <>
            <Upload className={`w-12 h-12 mx-auto mb-4 ${isDragging ? 'text-accent' : 'text-text-muted'}`} />
            <p className="text-text-primary font-medium mb-1">
              Drop your CSV file here
            </p>
            <p className="text-text-muted text-sm">
              or click to browse
            </p>
            <div className="flex items-center justify-center gap-4 mt-4 text-xs text-text-muted">
              <span className="flex items-center gap-1">
                <FileSpreadsheet className="w-3.5 h-3.5" />
                .csv files
              </span>
              <span>Comma or semicolon delimited</span>
            </div>
          </>
        )}
      </div>

      {error && (
        <div className="w-full mt-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-risk-high/10 border border-risk-high/20 text-risk-high text-sm">
          <AlertCircle className="w-4 h-4 shrink-0" />
          {error}
        </div>
      )}

      <div className="mt-6">
        <button
          onClick={loadSampleData}
          disabled={isLoading}
          className="flex items-center gap-2 px-4 py-2 text-sm text-text-secondary hover:text-accent-light border border-border hover:border-accent/30 rounded-xl transition-all hover:bg-accent-glow"
        >
          <Database className="w-4 h-4" />
          Try with sample data (30 students)
        </button>
      </div>

      <div className="mt-12 grid grid-cols-3 gap-6 w-full text-center">
        {[
          { label: 'Attendance', desc: 'Weekly patterns & trends' },
          { label: 'Assessments', desc: 'Grades & submission status' },
          { label: 'Engagement', desc: 'AI sentiment analysis' },
        ].map(item => (
          <div key={item.label} className="glass-card p-4">
            <p className="text-sm font-medium text-text-primary">{item.label}</p>
            <p className="text-xs text-text-muted mt-1">{item.desc}</p>
          </div>
        ))}
      </div>
    </div>
  )
}

import { useMemo, useState } from 'react'
import { useApp } from '../../context/AppContext'
import { mapToStudents } from '../../lib/column-mapper'
import { calculateAllRiskScores } from '../../lib/risk-engine'
import { analyseSentimentBatch } from '../../lib/sentiment'
import { ArrowLeft, ArrowRight, CheckCircle2, FileSpreadsheet, Columns3 } from 'lucide-react'

export default function ColumnMappingView() {
  const { state, dispatch } = useApp()
  const schema = state.detectedSchema

  const schemaLabel = useMemo(() => {
    if (!schema) return 'Unknown'
    switch (schema.type) {
      case 'modern': return 'Modern Format (Template-based)'
      case 'legacy': return 'Legacy Format (Semester-based)'
      default: return 'Unknown Format'
    }
  }, [schema])

  const requiredMappings = useMemo(() => {
    return schema?.mappings.filter(m => m.required) || []
  }, [schema])

  const optionalMappings = useMemo(() => {
    return schema?.mappings.filter(m => !m.required) || []
  }, [schema])

  const missingRequired = requiredMappings.filter(m => !m.csvColumn)

  const [processingStage, setProcessingStage] = useState<string | null>(null)

  const handleConfirm = async () => {
    if (!schema) return
    dispatch({ type: 'SET_PROCESSING', payload: true })
    setProcessingStage('Parsing student data...')

    try {
      const students = mapToStudents(state.rawData, schema)

      setProcessingStage('Calculating risk scores...')
      await new Promise(r => setTimeout(r, 300))
      const scored = calculateAllRiskScores(students, false)

      // Run AI sentiment analysis before showing dashboard
      const hasNotes = students.some(s =>
        s.attendance.some(w => w.engagementNote) ||
        s.existingMetrics?.tutorNotes ||
        s.existingMetrics?.engagementComment
      )

      let finalStudents = scored
      if (hasNotes) {
        setProcessingStage('Running Gemini AI sentiment analysis...')
        const withSentiment = await analyseSentimentBatch(scored)
        setProcessingStage('Recalculating risk scores with AI insights...')
        await new Promise(r => setTimeout(r, 200))
        finalStudents = calculateAllRiskScores(withSentiment, true)
      }

      setProcessingStage('Preparing dashboard...')
      await new Promise(r => setTimeout(r, 200))
      dispatch({ type: 'SET_STUDENTS', payload: finalStudents })
      dispatch({ type: 'SET_STEP', payload: 'dashboard' })
    } catch (err) {
      dispatch({ type: 'SET_ERROR', payload: err instanceof Error ? err.message : 'Failed to process data' })
    } finally {
      dispatch({ type: 'SET_PROCESSING', payload: false })
      setProcessingStage(null)
    }
  }

  if (!schema) return null

  if (processingStage) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[70vh]">
        <div className="glass-card p-12 text-center max-w-md w-full">
          {/* Animated rings */}
          <div className="relative w-24 h-24 mx-auto mb-8">
            <div className="absolute inset-0 rounded-full border-2 border-accent/20 animate-[ping_2s_ease-in-out_infinite]" />
            <div className="absolute inset-2 rounded-full border-2 border-accent/30 animate-[ping_2s_ease-in-out_0.3s_infinite]" />
            <div className="absolute inset-4 rounded-full border-2 border-accent/40 animate-[ping_2s_ease-in-out_0.6s_infinite]" />
            <div className="absolute inset-0 flex items-center justify-center">
              <div className="w-12 h-12 rounded-full border-3 border-accent border-t-transparent animate-spin" />
            </div>
          </div>

          <h3 className="text-lg font-semibold text-text-primary mb-2">
            Analysing Students
          </h3>
          <p className="text-sm text-accent-light mb-6 min-h-[20px] transition-all">
            {processingStage}
          </p>

          {/* Progress dots */}
          <div className="flex items-center justify-center gap-4">
            {[
              { label: 'Parse', done: processingStage !== 'Parsing student data...' },
              { label: 'Score', done: processingStage !== 'Parsing student data...' && processingStage !== 'Calculating risk scores...' },
              { label: 'AI', done: processingStage === 'Recalculating risk scores with AI insights...' || processingStage === 'Preparing dashboard...' },
              { label: 'Done', done: processingStage === 'Preparing dashboard...' },
            ].map(step => (
              <div key={step.label} className="flex flex-col items-center gap-1.5">
                <div className={`w-3 h-3 rounded-full transition-all ${
                  step.done ? 'bg-risk-low scale-110' : 'bg-bg-tertiary'
                }`} />
                <span className={`text-xs ${step.done ? 'text-risk-low' : 'text-text-muted'}`}>
                  {step.label}
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="max-w-3xl mx-auto">
      <button
        onClick={() => dispatch({ type: 'SET_STEP', payload: 'upload' })}
        className="flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary mb-6 transition-colors"
      >
        <ArrowLeft className="w-4 h-4" />
        Back to upload
      </button>

      <div className="glass-card p-6 mb-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold text-text-primary">Column Mapping</h2>
          <span className={`text-xs px-3 py-1 rounded-full ${
            schema.type === 'modern' ? 'bg-accent/15 text-accent-light' : 'bg-risk-medium/15 text-risk-medium'
          }`}>
            {schemaLabel}
          </span>
        </div>

        <div className="grid grid-cols-3 gap-4 text-center">
          <div className="glass-card p-3">
            <FileSpreadsheet className="w-5 h-5 text-accent mx-auto mb-1" />
            <p className="text-lg font-bold text-text-primary">{schema.rowCount}</p>
            <p className="text-xs text-text-muted">Students</p>
          </div>
          <div className="glass-card p-3">
            <Columns3 className="w-5 h-5 text-accent mx-auto mb-1" />
            <p className="text-lg font-bold text-text-primary">{schema.columnCount}</p>
            <p className="text-xs text-text-muted">Columns</p>
          </div>
          <div className="glass-card p-3">
            <CheckCircle2 className="w-5 h-5 text-risk-low mx-auto mb-1" />
            <p className="text-lg font-bold text-text-primary">
              {requiredMappings.filter(m => m.csvColumn).length}/{requiredMappings.length}
            </p>
            <p className="text-xs text-text-muted">Mapped</p>
          </div>
        </div>
      </div>

      {/* Required Mappings */}
      <div className="glass-card p-6 mb-6">
        <h3 className="text-sm font-medium text-text-primary mb-4">Required Fields</h3>
        <div className="space-y-3">
          {requiredMappings.map(m => (
            <div key={m.field} className="flex items-center justify-between gap-4">
              <span className="text-sm text-text-secondary min-w-[160px]">{m.label}</span>
              <div className="flex-1 flex items-center gap-2">
                {m.csvColumn ? (
                  <>
                    <CheckCircle2 className="w-4 h-4 text-risk-low shrink-0" />
                    <code className="text-xs text-accent-light bg-bg-tertiary px-2 py-1 rounded font-mono truncate max-w-[300px]">
                      {m.field === 'weekColumns' || m.field === 'assessmentColumns' || m.field === 'engagementNoteColumns'
                        ? `${m.csvColumn.split('|').length} columns detected`
                        : m.csvColumn}
                    </code>
                  </>
                ) : (
                  <span className="text-xs text-risk-high">Not found</span>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Optional Mappings */}
      {optionalMappings.length > 0 && (
        <div className="glass-card p-6 mb-6">
          <h3 className="text-sm font-medium text-text-primary mb-4">Additional Fields Detected</h3>
          <div className="space-y-2">
            {optionalMappings.filter(m => m.csvColumn).map(m => (
              <div key={m.field} className="flex items-center justify-between gap-4">
                <span className="text-sm text-text-muted">{m.label}</span>
                <code className="text-xs text-text-secondary bg-bg-tertiary px-2 py-1 rounded font-mono truncate max-w-[300px]">
                  {m.csvColumn}
                </code>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Actions */}
      <div className="flex items-center justify-between">
        {missingRequired.length > 0 && (
          <p className="text-xs text-risk-medium">
            {missingRequired.length} required field{missingRequired.length > 1 ? 's' : ''} not mapped.
            Analysis will proceed with available data.
          </p>
        )}
        <div className="ml-auto">
          <button
            onClick={handleConfirm}
            disabled={state.isProcessing}
            className="flex items-center gap-2 px-6 py-2.5 bg-accent hover:bg-accent-light text-white font-medium text-sm rounded-xl transition-colors disabled:opacity-50"
          >
            {state.isProcessing ? (
              <>
                <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                Processing...
              </>
            ) : (
              <>
                Analyse & Score
                <ArrowRight className="w-4 h-4" />
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  )
}

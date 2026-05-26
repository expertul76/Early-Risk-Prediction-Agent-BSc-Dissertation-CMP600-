import { useState } from 'react'
import { useApp } from '../../context/AppContext'
import { useAuth } from '../../context/AuthContext'
import { Shield, Settings, Eye, EyeOff, RotateCcw, FolderOpen } from 'lucide-react'

export default function Header() {
  const { state, dispatch } = useApp()
  const { user } = useAuth()
  const [showSettings, setShowSettings] = useState(false)
  const [showKey, setShowKey] = useState(false)

  const goToReports = () => {
    dispatch({ type: 'RESET' })
  }

  return (
    <header className="fixed top-0 left-0 right-0 z-40 bg-bg-primary/80 backdrop-blur-xl border-b border-border">
      <div className="max-w-[1400px] mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        <button onClick={user ? goToReports : undefined} className="flex items-center gap-3 hover:opacity-80 transition-opacity">
          <Shield className="w-6 h-6 text-accent" />
          <div>
            <h1 className="text-sm font-semibold text-text-primary leading-tight">
              Early Risk Prediction Agent
            </h1>
            <p className="text-xs text-text-muted leading-tight">
              CMP600 - Marcel Bucur
            </p>
          </div>
        </button>

        {user && (
          <div className="flex items-center gap-2">
            {(state.step === 'dashboard' || state.step === 'upload' || state.step === 'mapping') && (
              <button
                onClick={goToReports}
                className="flex items-center gap-1.5 px-3 py-1.5 text-xs text-text-secondary hover:text-text-primary rounded-lg hover:bg-bg-card transition-colors"
              >
                <FolderOpen className="w-3.5 h-3.5" />
                My Reports
              </button>
            )}

            {state.step === 'dashboard' && (
              <button
                onClick={() => {
                  dispatch({ type: 'RESET' })
                  dispatch({ type: 'SET_STEP', payload: 'upload' })
                }}
                className="flex items-center gap-1.5 px-3 py-1.5 text-xs text-text-secondary hover:text-text-primary rounded-lg hover:bg-bg-card transition-colors"
              >
                <RotateCcw className="w-3.5 h-3.5" />
                New Analysis
              </button>
            )}

            <button
              onClick={() => setShowSettings(!showSettings)}
              className={`p-2 rounded-lg transition-colors ${
                showSettings ? 'bg-accent/20 text-accent-light' : 'text-text-muted hover:text-text-primary hover:bg-bg-card'
              }`}
            >
              <Settings className="w-4 h-4" />
            </button>
          </div>
        )}
      </div>

      {showSettings && (
        <div className="border-t border-border bg-bg-secondary/90 backdrop-blur-xl">
          <div className="max-w-[1400px] mx-auto px-4 sm:px-6 py-4">
            <div className="flex items-center gap-4 max-w-lg">
              <label className="text-xs text-text-secondary whitespace-nowrap">
                OpenAI API Key
              </label>
              <div className="relative flex-1">
                <input
                  type={showKey ? 'text' : 'password'}
                  value={state.settings.openaiApiKey}
                  onChange={e => dispatch({ type: 'SET_SETTINGS', payload: { openaiApiKey: e.target.value } })}
                  placeholder="sk-..."
                  className="w-full bg-bg-tertiary border border-border rounded-lg px-3 py-1.5 text-sm text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent/50 font-mono"
                />
                <button
                  onClick={() => setShowKey(!showKey)}
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary"
                >
                  {showKey ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
                </button>
              </div>
              <div className={`text-xs px-2 py-0.5 rounded-full ${
                state.settings.openaiApiKey
                  ? 'bg-risk-low/15 text-risk-low'
                  : 'bg-bg-tertiary text-text-muted'
              }`}>
                {state.settings.openaiApiKey ? 'Ready' : 'Optional'}
              </div>
            </div>
            <p className="text-xs text-text-muted mt-2">
              Stored in session only. Used for AI sentiment analysis of engagement notes. Never sent to any server except OpenAI.
            </p>
          </div>
        </div>
      )}
    </header>
  )
}

import { useCallback } from 'react'
import { useAuth } from './context/AuthContext'
import { useApp } from './context/AppContext'
import { apiLoadReport } from './lib/api'
import Header from './components/layout/Header'
import AuthView from './components/auth/AuthView'
import ReportsView from './components/reports/ReportsView'
import UploadView from './components/upload/UploadView'
import ColumnMappingView from './components/mapping/ColumnMappingView'
import DashboardView from './components/dashboard/DashboardView'

export default function App() {
  const { user, loading: authLoading } = useAuth()
  const { state, dispatch } = useApp()

  const handleUploadNew = useCallback(() => {
    dispatch({ type: 'RESET' })
    dispatch({ type: 'SET_STEP', payload: 'upload' })
  }, [dispatch])

  const handleLoadReport = useCallback(async (reportId: number) => {
    try {
      dispatch({ type: 'SET_PROCESSING', payload: true })
      const report = await apiLoadReport(reportId)
      dispatch({
        type: 'LOAD_REPORT',
        payload: {
          id: report.id,
          name: report.name,
          students: report.students,
        },
      })
    } catch (err) {
      dispatch({ type: 'SET_ERROR', payload: err instanceof Error ? err.message : 'Failed to load report' })
    }
  }, [dispatch])

  // Show loading spinner while checking auth
  if (authLoading) {
    return (
      <div className="min-h-screen bg-bg-primary flex items-center justify-center">
        <div className="noise-overlay" />
        <div className="w-8 h-8 border-2 border-accent-purple/30 border-t-accent-purple rounded-full animate-spin" />
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-bg-primary">
      <div className="noise-overlay" />
      <Header />
      <main className="max-w-[1400px] mx-auto px-4 sm:px-6 pt-20 pb-12">
        {!user ? (
          <AuthView />
        ) : (
          <>
            {state.step === 'reports' && (
              <ReportsView onUploadNew={handleUploadNew} onLoadReport={handleLoadReport} />
            )}
            {state.step === 'upload' && <UploadView />}
            {state.step === 'mapping' && <ColumnMappingView />}
            {state.step === 'dashboard' && <DashboardView />}
          </>
        )}
      </main>
    </div>
  )
}

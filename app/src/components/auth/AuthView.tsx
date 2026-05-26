import { useState } from 'react'
import { useAuth } from '../../context/AuthContext'
import { Shield, LogIn, UserPlus, AlertCircle } from 'lucide-react'

export default function AuthView() {
  const { login, register } = useAuth()
  const [mode, setMode] = useState<'login' | 'register'>('login')
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')

    if (mode === 'register' && password !== confirmPassword) {
      setError('Passwords do not match')
      return
    }

    setLoading(true)
    try {
      if (mode === 'login') {
        await login(email, password)
      } else {
        await register(name, email, password)
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Something went wrong')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-[70vh] flex items-center justify-center">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-accent-purple/20 mb-4">
            <Shield className="w-8 h-8 text-accent-purple" />
          </div>
          <h1 className="text-3xl font-bold">
            <span className="text-accent-purple">Student</span>{' '}
            <span className="text-text-primary">Risk Analysis</span>
          </h1>
          <p className="text-text-secondary mt-2">
            {mode === 'login' ? 'Sign in to access your reports' : 'Create an account to get started'}
          </p>
        </div>

        <form onSubmit={handleSubmit} className="glass-card p-6 space-y-4">
          {error && (
            <div className="flex items-center gap-2 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
              <AlertCircle className="w-4 h-4 shrink-0" />
              {error}
            </div>
          )}

          {mode === 'register' && (
            <div>
              <label className="block text-sm text-text-secondary mb-1.5">Full Name</label>
              <input
                type="text"
                value={name}
                onChange={e => setName(e.target.value)}
                required
                className="w-full px-3 py-2.5 rounded-lg bg-bg-secondary border border-border-primary text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent-purple transition-colors"
                placeholder="Marcel Bucur"
              />
            </div>
          )}

          <div>
            <label className="block text-sm text-text-secondary mb-1.5">Email</label>
            <input
              type="email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              required
              className="w-full px-3 py-2.5 rounded-lg bg-bg-secondary border border-border-primary text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent-purple transition-colors"
              placeholder="you@example.com"
            />
          </div>

          <div>
            <label className="block text-sm text-text-secondary mb-1.5">Password</label>
            <input
              type="password"
              value={password}
              onChange={e => setPassword(e.target.value)}
              required
              minLength={8}
              className="w-full px-3 py-2.5 rounded-lg bg-bg-secondary border border-border-primary text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent-purple transition-colors"
              placeholder="Min. 8 characters"
            />
          </div>

          {mode === 'register' && (
            <div>
              <label className="block text-sm text-text-secondary mb-1.5">Confirm Password</label>
              <input
                type="password"
                value={confirmPassword}
                onChange={e => setConfirmPassword(e.target.value)}
                required
                minLength={8}
                className="w-full px-3 py-2.5 rounded-lg bg-bg-secondary border border-border-primary text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent-purple transition-colors"
                placeholder="Repeat password"
              />
            </div>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-accent-purple text-white font-medium hover:bg-accent-purple/90 disabled:opacity-50 transition-colors"
          >
            {loading ? (
              <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            ) : mode === 'login' ? (
              <>
                <LogIn className="w-4 h-4" />
                Sign In
              </>
            ) : (
              <>
                <UserPlus className="w-4 h-4" />
                Create Account
              </>
            )}
          </button>

          <div className="text-center text-sm text-text-secondary">
            {mode === 'login' ? (
              <>
                Don't have an account?{' '}
                <button type="button" onClick={() => { setMode('register'); setError('') }} className="text-accent-purple hover:underline">
                  Register
                </button>
              </>
            ) : (
              <>
                Already have an account?{' '}
                <button type="button" onClick={() => { setMode('login'); setError('') }} className="text-accent-purple hover:underline">
                  Sign In
                </button>
              </>
            )}
          </div>
        </form>
      </div>
    </div>
  )
}

import { useState } from 'react'
import { Navigate, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../../auth/AuthContext.jsx'
import FieldError from '../../components/FieldError.jsx'

export default function LoginPage() {
  const { user, signIn } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [errors, setErrors] = useState({})
  const [message, setMessage] = useState(null)
  const [busy, setBusy] = useState(false)

  if (user) {
    return <Navigate to="/admin/pages" replace />
  }

  async function handleSubmit(event) {
    event.preventDefault()

    setBusy(true)
    setErrors({})
    setMessage(null)

    try {
      await signIn(email, password)
      navigate(location.state?.from ?? '/admin/pages', { replace: true })
    } catch (problem) {
      setErrors(problem.errors ?? {})
      setMessage(problem.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="auth">
      <div className="panel">
        <div className="card">
          <div className="mark">CM</div>

          <h1>Sign in</h1>
          <p className="lede">Manage pages, the menu and who may touch them.</p>

          {message && (
            <p className="notice error" style={{ marginTop: '1.25rem' }}>
              {message}
            </p>
          )}

          <form onSubmit={handleSubmit} style={{ marginTop: '1.35rem' }}>
            <div className="field">
              <label htmlFor="email">Email</label>
              <input
                id="email"
                type="email"
                autoComplete="username"
                placeholder="you@example.com"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                required
              />
              <FieldError errors={errors} name="email" />
            </div>

            <div className="field">
              <label htmlFor="password">Password</label>
              <input
                id="password"
                type="password"
                autoComplete="current-password"
                placeholder="••••••••"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                required
              />
              <FieldError errors={errors} name="password" />
            </div>

            <button
              type="submit"
              className="primary"
              disabled={busy}
              style={{ width: '100%', justifyContent: 'center' }}
            >
              {busy ? 'Signing in…' : 'Sign in'}
            </button>
          </form>

          <div className="hint">
            <span className="overline">Seeded accounts</span>
            <code>admin@cms.test</code> — full access
            <br />
            <code>moderator@cms.test</code> — pages only
            <br />
            Password for both: <code>password</code>
          </div>
        </div>
      </div>
    </div>
  )
}

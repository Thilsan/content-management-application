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
    <div className="shell reading">
      <div className="card">
        <h1>Sign in</h1>

        {message && <p className="notice error">{message}</p>}

        <form onSubmit={handleSubmit}>
          <div className="field">
            <label htmlFor="email">Email</label>
            <input
              id="email"
              type="email"
              autoComplete="username"
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
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              required
            />
            <FieldError errors={errors} name="password" />
          </div>

          <button type="submit" className="primary" disabled={busy}>
            {busy ? 'Signing in…' : 'Sign in'}
          </button>
        </form>

        <p className="muted" style={{ marginTop: '1.25rem' }}>
          Seeded accounts: <code>admin@cms.test</code> and <code>moderator@cms.test</code>, both with
          the password <code>password</code>.
        </p>
      </div>
    </div>
  )
}

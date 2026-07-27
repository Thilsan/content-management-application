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
    <div className="grid min-h-screen place-items-center p-6">
      <div className="w-full max-w-96">
        <div className="card p-6">
          <div className="mb-4 grid size-8.5 place-items-center rounded-[10px] bg-linear-to-br from-accent to-[#6d4bf0] text-[0.9rem] font-bold text-white">
            CM
          </div>

          <h1 className="text-2xl">Sign in</h1>
          <p className="lede">Manage pages, the menu and who may touch them.</p>

          {message && <p className="notice notice-error mt-5">{message}</p>}

          <form onSubmit={handleSubmit} className="mt-5">
            <div className="mb-4">
              <label className="label" htmlFor="email">
                Email
              </label>
              <input
                id="email"
                className="input"
                type="email"
                autoComplete="username"
                placeholder="you@example.com"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                required
              />
              <FieldError errors={errors} name="email" />
            </div>

            <div className="mb-4">
              <label className="label" htmlFor="password">
                Password
              </label>
              <input
                id="password"
                className="input"
                type="password"
                autoComplete="current-password"
                placeholder="••••••••"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                required
              />
              <FieldError errors={errors} name="password" />
            </div>

            <button type="submit" className="btn btn-primary w-full justify-center" disabled={busy}>
              {busy ? 'Signing in…' : 'Sign in'}
            </button>
          </form>

          <div className="mt-5 rounded-panel border border-line bg-wash px-3.5 py-3 text-[0.82rem] leading-7 text-ink-soft">
            <span className="eyebrow mb-1 block">Seeded accounts</span>
            <code className="code">admin@cms.test</code> — full access
            <br />
            <code className="code">moderator@cms.test</code> — pages only
            <br />
            Password for both: <code className="code">password</code>
          </div>
        </div>
      </div>
    </div>
  )
}

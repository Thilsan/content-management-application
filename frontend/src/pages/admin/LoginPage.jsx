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
    <div className="grid min-h-screen lg:grid-cols-2">
      {/* Brand side. Hidden below lg so the form gets the full screen on a phone. */}
      <div className="relative hidden flex-col justify-between overflow-hidden bg-black p-12 text-white lg:flex">
        <div className="flex items-center gap-2.5 text-sm font-semibold tracking-tight">
          <span className="grid size-7 place-items-center rounded-lg bg-white text-[0.75rem] font-bold text-black">
            CM
          </span>
          Content
        </div>

        <div className="max-w-sm">
          <p className="text-3xl leading-tight font-semibold tracking-tight text-balance">
            Pages, menus and permissions, all in one place.
          </p>
          <p className="mt-4 text-sm leading-relaxed text-white/60">
            Every action here is enforced by the same privilege the API checks. What a role can do
            is a row in a table, not a line of code.
          </p>
        </div>

        <p className="text-xs text-white/40">Content management system</p>

        {/* A quiet grid, purely decorative, so the panel is not just a flat block. */}
        <div
          className="pointer-events-none absolute inset-0 opacity-[0.07]"
          style={{
            backgroundImage:
              'linear-gradient(to right, white 1px, transparent 1px), linear-gradient(to bottom, white 1px, transparent 1px)',
            backgroundSize: '40px 40px',
          }}
          aria-hidden="true"
        />
      </div>

      {/* Form side. min-w-0 matters here: a grid item won't shrink below its
          content's intrinsic width otherwise, and this row overflows on a
          narrow phone as a result. */}
      <div className="flex min-w-0 items-center justify-center bg-white p-6">
        <div className="w-full min-w-0 max-w-sm">
          <div className="mb-8 grid size-9 place-items-center rounded-lg bg-black text-[0.85rem] font-bold text-white lg:hidden">
            CM
          </div>

          <h1 className="text-2xl font-semibold tracking-tight text-black">Sign in</h1>
          <p className="mt-1.5 text-[0.92rem] text-neutral-500">
            Manage pages, the menu and who may touch them.
          </p>

          {message && (
            <p className="mt-5 rounded-lg border border-neutral-200 bg-neutral-50 px-3.5 py-2.5 text-[0.89rem] text-red-600">
              {message}
            </p>
          )}

          <form onSubmit={handleSubmit} className="mt-6">
            <div className="mb-4">
              <label className="mb-1.5 block text-[0.83rem] font-medium text-black" htmlFor="email">
                Email
              </label>
              <input
                id="email"
                className="w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-[0.92rem] text-black transition-colors placeholder:text-neutral-400 hover:border-neutral-400 focus:border-black focus:ring-3 focus:ring-black/10 focus:outline-none"
                type="email"
                autoComplete="username"
                placeholder="you@example.com"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                required
              />
              <FieldError errors={errors} name="email" />
            </div>

            <div className="mb-5">
              <label
                className="mb-1.5 block text-[0.83rem] font-medium text-black"
                htmlFor="password"
              >
                Password
              </label>
              <input
                id="password"
                className="w-full rounded-lg border border-neutral-300 px-3 py-2.5 text-[0.92rem] text-black transition-colors placeholder:text-neutral-400 hover:border-neutral-400 focus:border-black focus:ring-3 focus:ring-black/10 focus:outline-none"
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
              disabled={busy}
              className="flex w-full items-center justify-center gap-2 rounded-lg bg-black px-4 py-2.5 text-[0.92rem] font-medium text-white transition-colors hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {busy ? 'Signing in…' : 'Sign in'}
            </button>
          </form>

          <div className="mt-6 rounded-lg border border-neutral-200 px-3.5 py-3 text-[0.82rem] text-neutral-600">
            <span className="mb-2 block text-[0.7rem] font-semibold tracking-[0.09em] text-neutral-400 uppercase">
              Seeded accounts
            </span>

            <p className="mb-1 flex flex-wrap items-baseline gap-x-1.5">
              <code className="rounded border border-neutral-200 bg-neutral-100 px-1 py-px font-mono text-[0.84em] text-black">
                admin@cms.com
              </code>
              <span>full access</span>
            </p>

            <p className="mb-2 flex flex-wrap items-baseline gap-x-1.5">
              <code className="rounded border border-neutral-200 bg-neutral-100 px-1 py-px font-mono text-[0.84em] text-black">
                moderator@cms.com
              </code>
              <span>pages only</span>
            </p>

            <p className="flex flex-wrap items-baseline gap-x-1.5">
              <span>Password for both:</span>
              <code className="rounded border border-neutral-200 bg-neutral-100 px-1 py-px font-mono text-[0.84em] text-black">
                12345
              </code>
            </p>
          </div>
        </div>
      </div>
    </div>
  )
}

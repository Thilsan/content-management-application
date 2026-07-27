import { Link, NavLink, Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext.jsx'

/*
 * Only the sections the signed in user holds a privilege for are offered. The
 * API refuses the rest regardless, so this list is about not showing dead ends.
 */
const SECTIONS = [
  { to: '/admin/pages', label: 'Pages', privilege: 'pages.view' },
  { to: '/admin/trash', label: 'Trash', privilege: 'pages.restore' },
  { to: '/admin/menu', label: 'Menu', privilege: 'menus.view' },
  { to: '/admin/users', label: 'Users', privilege: 'users.view' },
  { to: '/admin/roles', label: 'Roles', privilege: 'roles.view' },
  { to: '/admin/privileges', label: 'Privileges', privilege: 'privileges.view' },
]

const TAB = 'rounded-field px-2.5 py-1.5 text-[0.9rem] font-medium whitespace-nowrap'

function initials(name) {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('')
}

export default function AdminLayout() {
  const { user, loading, signOut, can } = useAuth()
  const location = useLocation()

  if (loading) {
    return <p className="mx-auto max-w-280 px-6 pt-8 text-[0.88rem] text-muted">Loading…</p>
  }

  if (!user) {
    return <Navigate to="/admin/login" replace state={{ from: location.pathname }} />
  }

  return (
    <>
      <header className="sticky top-0 z-20 flex h-14 items-center gap-5 border-b border-line bg-surface/85 px-6 backdrop-blur-md backdrop-saturate-150">
        <Link
          to="/admin/pages"
          className="flex items-center gap-2 text-[0.95rem] font-semibold tracking-tight whitespace-nowrap text-ink hover:text-ink"
        >
          <span className="grid size-6 place-items-center rounded-[7px] bg-linear-to-br from-accent to-[#6d4bf0] text-[0.72rem] font-bold text-white">
            CM
          </span>
          Back office
        </Link>

        <nav className="flex gap-0.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          {SECTIONS.filter((section) => can(section.privilege)).map((section) => (
            <NavLink
              key={section.to}
              to={section.to}
              className={({ isActive }) =>
                isActive
                  ? `${TAB} bg-accent-wash text-accent-strong`
                  : `${TAB} text-ink-soft hover:bg-wash hover:text-ink`
              }
            >
              {section.label}
            </NavLink>
          ))}
        </nav>

        <span className="ml-auto" />

        <Link to="/" className="text-[0.88rem] font-medium text-ink-soft hover:text-ink">
          View site
        </Link>

        <span className="flex min-w-0 items-center gap-2">
          <span className="grid size-6.5 flex-none place-items-center rounded-full border border-line-strong bg-wash text-[0.7rem] font-semibold text-ink-soft">
            {initials(user.name)}
          </span>
          <span className="hidden min-w-0 leading-tight sm:block">
            <span className="block truncate text-[0.85rem] font-medium">{user.name}</span>
            <span className="block text-[0.72rem] whitespace-nowrap text-muted">
              {user.roles.map((role) => role.name).join(', ') || 'No role'}
            </span>
          </span>
        </span>

        <button type="button" className="btn btn-tiny" onClick={signOut}>
          Sign out
        </button>
      </header>

      <div className="mx-auto max-w-280 px-6 pt-8 pb-16">
        <Outlet />
      </div>
    </>
  )
}

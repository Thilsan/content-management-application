import { Link, NavLink, Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext.jsx'

function Icon({ paths }) {
  return (
    <svg
      className="size-4 flex-none"
      viewBox="0 0 16 16"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.4"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      {paths.map((d) => (
        <path key={d} d={d} />
      ))}
    </svg>
  )
}

const ICONS = {
  pages: ['M4 1.8h5l3.2 3.2v9A1 1 0 0 1 11.2 15H4a1 1 0 0 1-1-1V2.8a1 1 0 0 1 1-1z', 'M9 1.8V5h3.2'],
  trash: ['M2.8 4.2h10.4', 'M6.2 4.2V2.8h3.6v1.4', 'M4.4 4.2 5 13.2h6l.6-9'],
  menu: ['M3 4h10', 'M6 8h7', 'M6 12h7', 'M3 8h.01', 'M3 12h.01'],
  users: ['M11 13.5v-1a2.5 2.5 0 0 0-2.5-2.5h-3A2.5 2.5 0 0 0 3 12.5v1', 'M7 7.5a2.3 2.3 0 1 0 0-4.6 2.3 2.3 0 0 0 0 4.6', 'M13.2 13.5v-1a2.5 2.5 0 0 0-1.9-2.4'],
  roles: ['M8 1.8 3.2 3.6v3.9c0 3 2 5.6 4.8 6.7 2.8-1.1 4.8-3.7 4.8-6.7V3.6L8 1.8z'],
  privileges: ['M9.6 6.4a2.6 2.6 0 1 0-3.7 2.4L2.5 12.1v1.4h1.9v-1.3h1.3v-1.3h1.2l1-1a2.6 2.6 0 0 0 1.7-3.5z', 'M10.6 5.4h.01'],
}

/*
 * Only the sections the signed in user holds a privilege for are offered. The
 * API refuses the rest regardless, so this list is about not showing dead ends.
 */
const SECTIONS = [
  { to: '/admin/pages', label: 'Pages', privilege: 'pages.view', icon: 'pages' },
  { to: '/admin/trash', label: 'Trash', privilege: 'pages.restore', icon: 'trash' },
  { to: '/admin/menu', label: 'Menu', privilege: 'menus.view', icon: 'menu' },
  { to: '/admin/users', label: 'Users', privilege: 'users.view', icon: 'users' },
  { to: '/admin/roles', label: 'Roles', privilege: 'roles.view', icon: 'roles' },
  { to: '/admin/privileges', label: 'Privileges', privilege: 'privileges.view', icon: 'privileges' },
]

const NAV =
  'flex items-center gap-2.5 rounded-field px-2.5 py-2 text-[0.89rem] font-medium whitespace-nowrap transition-colors'

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
    return <p className="px-6 pt-8 text-[0.88rem] text-muted">Loading…</p>
  }

  if (!user) {
    return <Navigate to="/admin/login" replace state={{ from: location.pathname }} />
  }

  const sections = SECTIONS.filter((section) => can(section.privilege))

  return (
    <div className="min-h-screen lg:grid lg:grid-cols-[236px_minmax(0,1fr)]">
      {/*
        A rail on large screens; on anything narrower it collapses to a strip
        across the top with the same links scrolling horizontally.
      */}
      <aside className="flex flex-col gap-3 border-b border-line bg-sidebar px-3 py-3 lg:sticky lg:top-0 lg:h-screen lg:gap-0 lg:border-r lg:border-b-0 lg:px-3 lg:py-4">
        <Link
          to="/admin/pages"
          className="flex items-center gap-2 px-1.5 text-[0.95rem] font-semibold tracking-tight text-ink hover:text-ink lg:mb-5"
        >
          <span className="grid size-6 place-items-center rounded-[7px] bg-linear-to-br from-accent to-[#6d4bf0] text-[0.72rem] font-bold text-white">
            CM
          </span>
          Back office
        </Link>

        <nav className="flex gap-1 overflow-x-auto scrollbar-none lg:flex-1 lg:flex-col lg:gap-0.5 lg:overflow-visible [&::-webkit-scrollbar]:hidden">
          {sections.map((section) => (
            <NavLink
              key={section.to}
              to={section.to}
              className={({ isActive }) =>
                isActive
                  ? `${NAV} bg-surface text-ink shadow-card`
                  : `${NAV} text-ink-soft hover:bg-surface/70 hover:text-ink`
              }
            >
              <Icon paths={ICONS[section.icon]} />
              {section.label}
            </NavLink>
          ))}
        </nav>

        <div className="hidden lg:block">
          <Link
            to="/"
            className={`${NAV} text-ink-soft hover:bg-surface/70 hover:text-ink`}
            title="Open the public site"
          >
            <Icon paths={['M8 1.8a6.2 6.2 0 1 0 0 12.4A6.2 6.2 0 0 0 8 1.8z', 'M1.8 8h12.4', 'M8 1.8c1.6 1.7 2.5 3.9 2.5 6.2S9.6 12.5 8 14.2C6.4 12.5 5.5 10.3 5.5 8S6.4 3.5 8 1.8z']} />
            View site
          </Link>

          <div className="mt-3 flex items-center gap-2 border-t border-line-strong/60 pt-3">
            <span className="grid size-7 flex-none place-items-center rounded-full border border-line-strong bg-surface text-[0.7rem] font-semibold text-ink-soft">
              {initials(user.name)}
            </span>
            <span className="min-w-0 flex-1 leading-tight">
              <span className="block truncate text-[0.85rem] font-medium">{user.name}</span>
              <span className="block truncate text-[0.72rem] text-muted">
                {user.roles.map((role) => role.name).join(', ') || 'No role'}
              </span>
            </span>
          </div>

          <button type="button" className="btn btn-tiny mt-2 w-full justify-center" onClick={signOut}>
            Sign out
          </button>
        </div>

        {/* The same controls, inline, while the rail is collapsed. */}
        <div className="flex items-center gap-2 lg:hidden">
          <Link to="/" className="text-[0.85rem] font-medium text-ink-soft hover:text-ink">
            View site
          </Link>
          <span className="ml-auto text-[0.8rem] text-muted">{user.name}</span>
          <button type="button" className="btn btn-tiny" onClick={signOut}>
            Sign out
          </button>
        </div>
      </aside>

      <main className="mx-auto w-full max-w-260 px-6 pt-8 pb-16">
        <Outlet />
      </main>
    </div>
  )
}

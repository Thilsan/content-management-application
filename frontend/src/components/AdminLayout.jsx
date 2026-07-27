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
    return <p className="shell muted">Loading…</p>
  }

  if (!user) {
    return <Navigate to="/admin/login" replace state={{ from: location.pathname }} />
  }

  return (
    <>
      <header className="topbar">
        <Link to="/admin/pages" className="brand">
          <span className="mark">CM</span>
          Back office
        </Link>

        <nav>
          {SECTIONS.filter((section) => can(section.privilege)).map((section) => (
            <NavLink key={section.to} to={section.to}>
              {section.label}
            </NavLink>
          ))}
        </nav>

        <span className="spacer" />

        <Link to="/" className="quiet-link">
          View site
        </Link>

        <span className="who">
          <span className="avatar">{initials(user.name)}</span>
          <span className="lines">
            <span className="name">{user.name}</span>
            <span className="role">
              {user.roles.map((role) => role.name).join(', ') || 'No role'}
            </span>
          </span>
        </span>

        <button type="button" className="tiny" onClick={signOut}>
          Sign out
        </button>
      </header>

      <div className="shell">
        <Outlet />
      </div>
    </>
  )
}

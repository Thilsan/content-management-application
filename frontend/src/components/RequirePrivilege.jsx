import { useAuth } from '../auth/AuthContext.jsx'

/**
 * Hides a screen the signed in user has no privilege for. The API enforces the
 * same rule, so this only keeps the interface honest about what is on offer.
 */
export default function RequirePrivilege({ privilege, children }) {
  const { can } = useAuth()

  if (!can(privilege)) {
    return (
      <div className="card">
        <h1>Not available</h1>
        <p className="muted">
          This screen needs the <code>{privilege}</code> privilege, which none of your roles grant.
        </p>
      </div>
    )
  }

  return children
}

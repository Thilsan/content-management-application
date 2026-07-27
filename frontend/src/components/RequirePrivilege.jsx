import { useAuth } from '../auth/AuthContext.jsx'
import EmptyState from './EmptyState.jsx'

/**
 * Hides a screen the signed in user has no privilege for. The API enforces the
 * same rule, so this only keeps the interface honest about what is on offer.
 */
export default function RequirePrivilege({ privilege, children }) {
  const { can } = useAuth()

  if (!can(privilege)) {
    return (
      <div className="card">
        <EmptyState title="Not available">
          This screen needs the <code className="code">{privilege}</code> privilege, which none of
          your roles grant.
        </EmptyState>
      </div>
    )
  }

  return children
}

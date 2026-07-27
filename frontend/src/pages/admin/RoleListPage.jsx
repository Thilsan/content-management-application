import { useCallback, useEffect, useMemo, useState } from 'react'
import { useAuth } from '../../auth/AuthContext.jsx'
import FieldError from '../../components/FieldError.jsx'
import { api } from '../../lib/api'

const BLANK = { id: null, name: '', description: '', privileges: [] }

export default function RoleListPage() {
  const { can } = useAuth()

  const [roles, setRoles] = useState([])
  const [privileges, setPrivileges] = useState([])
  const [form, setForm] = useState(null)
  const [errors, setErrors] = useState({})
  const [error, setError] = useState(null)
  const [notice, setNotice] = useState(null)
  const [loading, setLoading] = useState(true)

  const load = useCallback(() => {
    setLoading(true)

    api
      .get('/roles')
      .then((response) => setRoles(response.data))
      .catch((problem) => setError(problem.message))
      .finally(() => setLoading(false))
  }, [])

  useEffect(load, [load])

  useEffect(() => {
    if (!can('privileges.view')) {
      return
    }

    api
      .get('/privileges')
      .then((response) => setPrivileges(response.data))
      .catch(() => setPrivileges([]))
  }, [can])

  const grouped = useMemo(() => {
    const groups = new Map()

    privileges.forEach((privilege) => {
      const bucket = groups.get(privilege.group) ?? []
      bucket.push(privilege)
      groups.set(privilege.group, bucket)
    })

    return [...groups.entries()]
  }, [privileges])

  function togglePrivilege(id) {
    setForm((current) => ({
      ...current,
      privileges: current.privileges.includes(id)
        ? current.privileges.filter((privilegeId) => privilegeId !== id)
        : [...current.privileges, id],
    }))
  }

  async function submit(event) {
    event.preventDefault()

    setErrors({})
    setError(null)

    const payload = {
      name: form.name,
      description: form.description,
      privileges: form.privileges,
    }

    try {
      if (form.id) {
        await api.put(`/roles/${form.id}`, payload)
        setNotice(`${form.name} updated. Anyone holding it picks up the change on their next request.`)
      } else {
        await api.post('/roles', payload)
        setNotice(`${form.name} added.`)
      }

      setForm(null)
      load()
    } catch (problem) {
      setErrors(problem.errors ?? {})
      setError(problem.message)
    }
  }

  async function destroy(role) {
    if (!window.confirm(`Delete the ${role.name} role?`)) {
      return
    }

    setError(null)

    try {
      const response = await api.delete(`/roles/${role.id}`)
      setNotice(response.message)
      load()
    } catch (problem) {
      setError(problem.message)
    }
  }

  return (
    <>
      <div className="between">
        <h1>Roles</h1>
        {can('roles.create') && (
          <button type="button" className="primary" onClick={() => setForm({ ...BLANK })}>
            Add role
          </button>
        )}
      </div>

      <p className="muted">
        A role is a bundle of privileges. The API checks the privilege, never the role name, so what
        a role may do is entirely a matter of which boxes are ticked here.
      </p>

      {error && <p className="notice error">{error}</p>}
      {notice && <p className="notice success">{notice}</p>}

      {form && (
        <form className="card" onSubmit={submit}>
          <h2>{form.id ? 'Edit role' : 'Add role'}</h2>

          <div className="row">
            <div className="field">
              <label htmlFor="name">Name</label>
              <input
                id="name"
                type="text"
                value={form.name}
                onChange={(event) => setForm({ ...form, name: event.target.value })}
                required
              />
              <FieldError errors={errors} name="name" />
            </div>

            <div className="field">
              <label htmlFor="description">Description</label>
              <input
                id="description"
                type="text"
                value={form.description ?? ''}
                onChange={(event) => setForm({ ...form, description: event.target.value })}
              />
              <FieldError errors={errors} name="description" />
            </div>
          </div>

          <div className="field">
            <label>Privileges</label>

            {grouped.length === 0 ? (
              <p className="muted">No privileges to choose from.</p>
            ) : (
              grouped.map(([group, items]) => (
                <div key={group} style={{ marginBottom: '0.75rem' }}>
                  <div className="muted" style={{ textTransform: 'capitalize' }}>
                    {group}
                  </div>
                  {items.map((privilege) => (
                    <label key={privilege.id} className="checkbox">
                      <input
                        type="checkbox"
                        checked={form.privileges.includes(privilege.id)}
                        onChange={() => togglePrivilege(privilege.id)}
                      />
                      {privilege.label}
                      <code className="muted">{privilege.name}</code>
                    </label>
                  ))}
                </div>
              ))
            )}

            <FieldError errors={errors} name="privileges" />
          </div>

          <div className="actions">
            <button type="submit" className="primary">
              Save
            </button>
            <button type="button" onClick={() => setForm(null)}>
              Cancel
            </button>
          </div>
        </form>
      )}

      <div className="card">
        {loading ? (
          <p className="muted">Loading…</p>
        ) : (
          <table>
            <thead>
              <tr>
                <th>Role</th>
                <th>Users</th>
                <th>Privileges</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {roles.map((role) => (
                <tr key={role.id}>
                  <td>
                    <strong>{role.name}</strong>
                    <div className="muted">{role.description}</div>
                  </td>
                  <td className="muted">{role.users_count}</td>
                  <td>
                    <div className="actions">
                      {role.privileges.map((privilege) => (
                        <span key={privilege.id} className="tag">
                          {privilege.name}
                        </span>
                      ))}
                    </div>
                  </td>
                  <td>
                    <div className="actions">
                      {can('roles.update') && (
                        <button
                          type="button"
                          className="tiny"
                          onClick={() =>
                            setForm({
                              id: role.id,
                              name: role.name,
                              description: role.description,
                              privileges: role.privileges.map((privilege) => privilege.id),
                            })
                          }
                        >
                          Edit
                        </button>
                      )}
                      {can('roles.delete') && (
                        <button type="button" className="tiny danger" onClick={() => destroy(role)}>
                          Delete
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </>
  )
}

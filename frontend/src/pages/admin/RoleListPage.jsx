import { useCallback, useEffect, useMemo, useState } from 'react'
import { useAuth } from '../../auth/AuthContext.jsx'
import EmptyState from '../../components/EmptyState.jsx'
import FieldError from '../../components/FieldError.jsx'
import PageHeader from '../../components/PageHeader.jsx'
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
        setNotice(
          `${form.name} updated. Anyone holding it picks up the change on their next request.`,
        )
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
      <PageHeader
        eyebrow="Access"
        title="Roles"
        lede="A role is a bundle of privileges. The API checks the privilege, never the role name, so what a role may do is entirely a matter of which boxes are ticked here."
      >
        {can('roles.create') && (
          <button type="button" className="btn btn-primary" onClick={() => setForm({ ...BLANK })}>
            Add role
          </button>
        )}
      </PageHeader>

      {error && <p className="notice notice-error">{error}</p>}
      {notice && <p className="notice notice-success">{notice}</p>}

      {form && (
        <form className="card mb-4 p-5" onSubmit={submit}>
          <h2 className="mb-4 text-[1.12rem]">{form.id ? 'Edit role' : 'Add role'}</h2>

          <div className="mb-5 flex flex-wrap items-start gap-4">
            <div className="min-w-45 flex-1">
              <label className="label" htmlFor="name">
                Name
              </label>
              <input
                id="name"
                className="input"
                type="text"
                value={form.name}
                onChange={(event) => setForm({ ...form, name: event.target.value })}
                required
              />
              <FieldError errors={errors} name="name" />
            </div>

            <div className="min-w-45 flex-1">
              <label className="label" htmlFor="description">
                Description
              </label>
              <input
                id="description"
                className="input"
                type="text"
                value={form.description ?? ''}
                onChange={(event) => setForm({ ...form, description: event.target.value })}
              />
              <FieldError errors={errors} name="description" />
            </div>
          </div>

          <div className="mb-5">
            {grouped.length === 0 ? (
              <p className="hint">No privileges to choose from.</p>
            ) : (
              <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {grouped.map(([group, items]) => (
                  <div key={group}>
                    <span className="eyebrow mb-2 block">{group}</span>
                    {items.map((privilege) => (
                      <label key={privilege.id} className="checkbox">
                        <input
                          type="checkbox"
                          checked={form.privileges.includes(privilege.id)}
                          onChange={() => togglePrivilege(privilege.id)}
                        />
                        {privilege.label}
                      </label>
                    ))}
                  </div>
                ))}
              </div>
            )}

            <FieldError errors={errors} name="privileges" />
          </div>

          <div className="flex flex-wrap items-center gap-2">
            <button type="submit" className="btn btn-primary">
              Save
            </button>
            <button type="button" className="btn" onClick={() => setForm(null)}>
              Cancel
            </button>
          </div>
        </form>
      )}

      <div className="card overflow-hidden">
        {loading ? (
          <EmptyState>Loading…</EmptyState>
        ) : (
          <div className="overflow-x-auto">
            <table className="data-table">
              <thead>
                <tr>
                  <th>Role</th>
                  <th>Users</th>
                  <th>Grants</th>
                  <th className="text-right" />
                </tr>
              </thead>
              <tbody>
                {roles.map((role) => (
                  <tr key={role.id}>
                    <td className="min-w-45">
                      <strong className="font-medium">{role.name}</strong>
                      <span className="sub">{role.description}</span>
                    </td>
                    <td className="text-muted">{role.users_count}</td>
                    <td>
                      <div className="flex flex-wrap items-center gap-1.5">
                        {role.privileges.map((privilege) => (
                          <span key={privilege.id} className="tag tag-code">
                            {privilege.name}
                          </span>
                        ))}
                      </div>
                    </td>
                    <td className="text-right">
                      <div className="flex flex-wrap items-center justify-end gap-1.5">
                        {can('roles.update') && (
                          <button
                            type="button"
                            className="btn btn-tiny"
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
                          <button
                            type="button"
                            className="btn btn-tiny btn-danger"
                            onClick={() => destroy(role)}
                          >
                            Delete
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </>
  )
}

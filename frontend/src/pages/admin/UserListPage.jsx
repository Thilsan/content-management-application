import { useCallback, useEffect, useState } from 'react'
import { useAuth } from '../../auth/AuthContext.jsx'
import FieldError from '../../components/FieldError.jsx'
import Pagination from '../../components/Pagination.jsx'
import { api } from '../../lib/api'

const BLANK = { id: null, name: '', email: '', password: '', roles: [] }

export default function UserListPage() {
  const { can, user: currentUser } = useAuth()

  const [users, setUsers] = useState([])
  const [meta, setMeta] = useState(null)
  const [roles, setRoles] = useState([])
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [form, setForm] = useState(null)
  const [errors, setErrors] = useState({})
  const [error, setError] = useState(null)
  const [notice, setNotice] = useState(null)
  const [loading, setLoading] = useState(true)

  const load = useCallback(() => {
    setLoading(true)

    api
      .get('/users', { search, page, per_page: 10 })
      .then((response) => {
        setUsers(response.data)
        setMeta(response.meta)
      })
      .catch((problem) => setError(problem.message))
      .finally(() => setLoading(false))
  }, [search, page])

  useEffect(load, [load])

  useEffect(() => {
    if (!can('roles.view')) {
      return
    }

    api
      .get('/roles')
      .then((response) => setRoles(response.data))
      .catch(() => setRoles([]))
  }, [can])

  function toggleRole(id) {
    setForm((current) => ({
      ...current,
      roles: current.roles.includes(id)
        ? current.roles.filter((roleId) => roleId !== id)
        : [...current.roles, id],
    }))
  }

  async function submit(event) {
    event.preventDefault()

    setErrors({})
    setError(null)

    const payload = {
      name: form.name,
      email: form.email,
      roles: form.roles,
    }

    // An empty password field on an edit means "leave it as it is".
    if (form.password) {
      payload.password = form.password
    }

    try {
      if (form.id) {
        await api.put(`/users/${form.id}`, payload)
        setNotice(`${form.name} updated.`)
      } else {
        await api.post('/users', payload)
        setNotice(`${form.name} added.`)
      }

      setForm(null)
      load()
    } catch (problem) {
      setErrors(problem.errors ?? {})
      setError(problem.message)
    }
  }

  async function destroy(row) {
    if (!window.confirm(`Delete ${row.name}?`)) {
      return
    }

    setError(null)

    try {
      const response = await api.delete(`/users/${row.id}`)
      setNotice(response.message)
      load()
    } catch (problem) {
      setError(problem.message)
    }
  }

  return (
    <>
      <div className="between">
        <h1>Users</h1>
        {can('users.create') && (
          <button type="button" className="primary" onClick={() => setForm({ ...BLANK })}>
            Add user
          </button>
        )}
      </div>

      {error && <p className="notice error">{error}</p>}
      {notice && <p className="notice success">{notice}</p>}

      {form && (
        <form className="card" onSubmit={submit}>
          <h2>{form.id ? 'Edit user' : 'Add user'}</h2>

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
              <label htmlFor="email">Email</label>
              <input
                id="email"
                type="email"
                value={form.email}
                onChange={(event) => setForm({ ...form, email: event.target.value })}
                required
              />
              <FieldError errors={errors} name="email" />
            </div>

            <div className="field">
              <label htmlFor="password">
                Password {form.id && <span className="muted">(blank keeps the current one)</span>}
              </label>
              <input
                id="password"
                type="password"
                autoComplete="new-password"
                value={form.password}
                onChange={(event) => setForm({ ...form, password: event.target.value })}
                required={!form.id}
              />
              <FieldError errors={errors} name="password" />
            </div>
          </div>

          <div className="field">
            <label>Roles</label>
            {roles.length === 0 ? (
              <p className="muted">No roles to choose from.</p>
            ) : (
              roles.map((role) => (
                <label key={role.id} className="checkbox">
                  <input
                    type="checkbox"
                    checked={form.roles.includes(role.id)}
                    onChange={() => toggleRole(role.id)}
                  />
                  {role.name}
                  <span className="muted">
                    {role.privileges.length} {role.privileges.length === 1 ? 'privilege' : 'privileges'}
                  </span>
                </label>
              ))
            )}
            <FieldError errors={errors} name="roles" />
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
        <div className="field">
          <label htmlFor="search">Search</label>
          <input
            id="search"
            type="search"
            value={search}
            placeholder="name or email"
            onChange={(event) => {
              setPage(1)
              setSearch(event.target.value)
            }}
          />
        </div>

        {loading ? (
          <p className="muted">Loading…</p>
        ) : (
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Roles</th>
                <th>Privileges</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {users.map((row) => (
                <tr key={row.id}>
                  <td>
                    <strong>{row.name}</strong>
                    {row.id === currentUser.id && <span className="tag"> you</span>}
                  </td>
                  <td className="muted">{row.email}</td>
                  <td>{row.roles.map((role) => role.name).join(', ') || '—'}</td>
                  <td className="muted">{row.privileges.length}</td>
                  <td>
                    <div className="actions">
                      {can('users.update') && (
                        <button
                          type="button"
                          className="tiny"
                          onClick={() =>
                            setForm({
                              id: row.id,
                              name: row.name,
                              email: row.email,
                              password: '',
                              roles: row.roles.map((role) => role.id),
                            })
                          }
                        >
                          Edit
                        </button>
                      )}
                      {can('users.delete') && row.id !== currentUser.id && (
                        <button type="button" className="tiny danger" onClick={() => destroy(row)}>
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

        <Pagination meta={meta} onChange={setPage} />
      </div>
    </>
  )
}

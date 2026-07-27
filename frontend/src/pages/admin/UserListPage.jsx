import { useCallback, useEffect, useState } from 'react'
import { useAuth } from '../../auth/AuthContext.jsx'
import EmptyState from '../../components/EmptyState.jsx'
import FieldError from '../../components/FieldError.jsx'
import PageHeader from '../../components/PageHeader.jsx'
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
      <PageHeader
        overline="Access"
        title="Users"
        lede="A user may hold several roles. What they can actually do is the union of the privileges those roles grant."
      >
        {can('users.create') && (
          <button type="button" className="btn btn-primary" onClick={() => setForm({ ...BLANK })}>
            Add user
          </button>
        )}
      </PageHeader>

      {error && <p className="notice notice-error">{error}</p>}
      {notice && <p className="notice notice-success">{notice}</p>}

      {form && (
        <form className="card mb-4 p-5" onSubmit={submit}>
          <h2 className="mb-4 text-[1.12rem]">{form.id ? 'Edit user' : 'Add user'}</h2>

          <div className="mb-4 flex flex-wrap items-start gap-4">
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
              <label className="label" htmlFor="email">
                Email
              </label>
              <input
                id="email"
                className="input"
                type="email"
                value={form.email}
                onChange={(event) => setForm({ ...form, email: event.target.value })}
                required
              />
              <FieldError errors={errors} name="email" />
            </div>

            <div className="min-w-45 flex-1">
              <label className="label" htmlFor="password">
                Password{' '}
                {form.id && <span className="font-normal text-muted">(blank keeps the current one)</span>}
              </label>
              <input
                id="password"
                className="input"
                type="password"
                autoComplete="new-password"
                value={form.password}
                onChange={(event) => setForm({ ...form, password: event.target.value })}
                required={!form.id}
              />
              <FieldError errors={errors} name="password" />
            </div>
          </div>

          <div className="mb-4">
            <span className="overline mb-2 block">Roles</span>
            {roles.length === 0 ? (
              <p className="hint">No roles to choose from.</p>
            ) : (
              roles.map((role) => (
                <label key={role.id} className="checkbox">
                  <input
                    type="checkbox"
                    checked={form.roles.includes(role.id)}
                    onChange={() => toggleRole(role.id)}
                  />
                  {role.name}
                  <span className="text-[0.8rem] text-muted">
                    {role.privileges.length}{' '}
                    {role.privileges.length === 1 ? 'privilege' : 'privileges'}
                  </span>
                </label>
              ))
            )}
            <FieldError errors={errors} name="roles" />
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

      <div className="card mb-4 p-5">
        <div className="max-w-70">
          <label className="label" htmlFor="search">
            Search
          </label>
          <input
            id="search"
            className="input"
            type="search"
            value={search}
            placeholder="name or email"
            onChange={(event) => {
              setPage(1)
              setSearch(event.target.value)
            }}
          />
        </div>
      </div>

      <div className="card overflow-hidden">
        {loading ? (
          <EmptyState>Loading…</EmptyState>
        ) : users.length === 0 ? (
          <EmptyState title="No users match that search">Try a different name or email.</EmptyState>
        ) : (
          <div className="overflow-x-auto">
            <table className="data-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Roles</th>
                  <th>Privileges</th>
                  <th className="text-right" />
                </tr>
              </thead>
              <tbody>
                {users.map((row) => (
                  <tr key={row.id}>
                    <td>
                      <strong className="font-medium">{row.name}</strong>
                      {row.id === currentUser.id && <span className="tag ml-1.5">you</span>}
                    </td>
                    <td>{row.email}</td>
                    <td>
                      <div className="flex flex-wrap items-center gap-1.5">
                        {row.roles.length === 0 ? (
                          <span className="text-muted">—</span>
                        ) : (
                          row.roles.map((role) => (
                            <span key={role.id} className="tag">
                              {role.name}
                            </span>
                          ))
                        )}
                      </div>
                    </td>
                    <td className="text-muted">{row.privileges.length}</td>
                    <td className="text-right">
                      <div className="flex flex-wrap items-center justify-end gap-1.5">
                        {can('users.update') && (
                          <button
                            type="button"
                            className="btn btn-tiny"
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
                          <button
                            type="button"
                            className="btn btn-tiny btn-danger"
                            onClick={() => destroy(row)}
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

        {meta && meta.last_page > 1 && (
          <div className="px-5 pb-4">
            <Pagination meta={meta} onChange={setPage} />
          </div>
        )}
      </div>
    </>
  )
}

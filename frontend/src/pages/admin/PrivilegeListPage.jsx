import { useCallback, useEffect, useState } from 'react'
import { useAuth } from '../../auth/AuthContext.jsx'
import FieldError from '../../components/FieldError.jsx'
import { api } from '../../lib/api'

const BLANK = { id: null, name: '', label: '', group: '' }

export default function PrivilegeListPage() {
  const { can } = useAuth()

  const [privileges, setPrivileges] = useState([])
  const [form, setForm] = useState(null)
  const [errors, setErrors] = useState({})
  const [error, setError] = useState(null)
  const [notice, setNotice] = useState(null)
  const [loading, setLoading] = useState(true)

  const load = useCallback(() => {
    setLoading(true)

    api
      .get('/privileges')
      .then((response) => setPrivileges(response.data))
      .catch((problem) => setError(problem.message))
      .finally(() => setLoading(false))
  }, [])

  useEffect(load, [load])

  async function submit(event) {
    event.preventDefault()

    setErrors({})
    setError(null)

    try {
      if (form.id) {
        await api.put(`/privileges/${form.id}`, form)
        setNotice(`${form.name} updated.`)
      } else {
        await api.post('/privileges', form)
        setNotice(`${form.name} added. Grant it to a role to put it to use.`)
      }

      setForm(null)
      load()
    } catch (problem) {
      setErrors(problem.errors ?? {})
      setError(problem.message)
    }
  }

  async function destroy(privilege) {
    if (!window.confirm(`Delete ${privilege.name}? Every role loses it.`)) {
      return
    }

    setError(null)

    try {
      const response = await api.delete(`/privileges/${privilege.id}`)
      setNotice(response.message)
      load()
    } catch (problem) {
      setError(problem.message)
    }
  }

  return (
    <>
      <div className="page-head">
        <div>
          <p className="overline">Access</p>
          <h1>Privileges</h1>
          <p className="lede">
            Each name here doubles as the check the API performs, so a new privilege becomes
            enforceable the moment a role grants it. Names read as <code>group.action</code>.
          </p>
        </div>

        {can('privileges.create') && (
          <button type="button" className="primary" onClick={() => setForm({ ...BLANK })}>
            Add privilege
          </button>
        )}
      </div>

      {error && <p className="notice error">{error}</p>}
      {notice && <p className="notice success">{notice}</p>}

      {form && (
        <form className="card" onSubmit={submit}>
          <h2>{form.id ? 'Edit privilege' : 'Add privilege'}</h2>

          <div className="row">
            <div className="field">
              <label htmlFor="name">Name</label>
              <input
                id="name"
                type="text"
                value={form.name}
                placeholder="reports.export"
                onChange={(event) => setForm({ ...form, name: event.target.value })}
                required
              />
              <FieldError errors={errors} name="name" />
            </div>

            <div className="field">
              <label htmlFor="label">Label</label>
              <input
                id="label"
                type="text"
                value={form.label}
                placeholder="Export reports"
                onChange={(event) => setForm({ ...form, label: event.target.value })}
                required
              />
              <FieldError errors={errors} name="label" />
            </div>

            <div className="field">
              <label htmlFor="group">Group</label>
              <input
                id="group"
                type="text"
                value={form.group}
                placeholder="reports"
                onChange={(event) => setForm({ ...form, group: event.target.value })}
                required
              />
              <FieldError errors={errors} name="group" />
            </div>
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

      <div className="card flush">
        {loading ? (
          <p className="empty">Loading…</p>
        ) : (
          <div className="table-scroll">
            <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Label</th>
                <th>Group</th>
                <th className="end" />
              </tr>
            </thead>
            <tbody>
              {privileges.map((privilege) => (
                <tr key={privilege.id}>
                  <td>
                    <code>{privilege.name}</code>
                  </td>
                  <td>{privilege.label}</td>
                  <td className="muted">{privilege.group}</td>
                  <td className="end">
                    <div className="actions">
                      {can('privileges.update') && (
                        <button
                          type="button"
                          className="tiny"
                          onClick={() =>
                            setForm({
                              id: privilege.id,
                              name: privilege.name,
                              label: privilege.label,
                              group: privilege.group,
                            })
                          }
                        >
                          Edit
                        </button>
                      )}
                      {can('privileges.delete') && (
                        <button
                          type="button"
                          className="tiny danger"
                          onClick={() => destroy(privilege)}
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

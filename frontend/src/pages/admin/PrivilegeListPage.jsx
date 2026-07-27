import { useCallback, useEffect, useState } from 'react'
import { useAuth } from '../../auth/AuthContext.jsx'
import EmptyState from '../../components/EmptyState.jsx'
import FieldError from '../../components/FieldError.jsx'
import PageHeader from '../../components/PageHeader.jsx'
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
      <PageHeader
        eyebrow="Access"
        title="Privileges"
        lede="Each name here doubles as the check the API performs, so a new privilege becomes enforceable the moment a role grants it. Names read as group.action."
      >
        {can('privileges.create') && (
          <button type="button" className="btn btn-primary" onClick={() => setForm({ ...BLANK })}>
            Add privilege
          </button>
        )}
      </PageHeader>

      {error && <p className="notice notice-error">{error}</p>}
      {notice && <p className="notice notice-success">{notice}</p>}

      {form && (
        <form className="card mb-4 p-5" onSubmit={submit}>
          <h2 className="mb-4 text-[1.12rem]">{form.id ? 'Edit privilege' : 'Add privilege'}</h2>

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
                placeholder="reports.export"
                onChange={(event) => setForm({ ...form, name: event.target.value })}
                required
              />
              <FieldError errors={errors} name="name" />
            </div>

            <div className="min-w-45 flex-1">
              <label className="label" htmlFor="label">
                Label
              </label>
              <input
                id="label"
                className="input"
                type="text"
                value={form.label}
                placeholder="Export reports"
                onChange={(event) => setForm({ ...form, label: event.target.value })}
                required
              />
              <FieldError errors={errors} name="label" />
            </div>

            <div className="min-w-45 flex-1">
              <label className="label" htmlFor="group">
                Group
              </label>
              <input
                id="group"
                className="input"
                type="text"
                value={form.group}
                placeholder="reports"
                onChange={(event) => setForm({ ...form, group: event.target.value })}
                required
              />
              <FieldError errors={errors} name="group" />
            </div>
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
                  <th>Name</th>
                  <th>Label</th>
                  <th>Group</th>
                  <th className="text-right" />
                </tr>
              </thead>
              <tbody>
                {privileges.map((privilege) => (
                  <tr key={privilege.id}>
                    <td>
                      <code className="code">{privilege.name}</code>
                    </td>
                    <td>{privilege.label}</td>
                    <td className="text-muted">{privilege.group}</td>
                    <td className="text-right">
                      <div className="flex flex-wrap items-center justify-end gap-1.5">
                        {can('privileges.update') && (
                          <button
                            type="button"
                            className="btn btn-tiny"
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
                            className="btn btn-tiny btn-danger"
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

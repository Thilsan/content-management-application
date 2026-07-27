import { useCallback, useEffect, useState } from 'react'
import { useAuth } from '../../auth/AuthContext.jsx'
import FieldError from '../../components/FieldError.jsx'
import MenuTreeEditor from '../../components/MenuTreeEditor.jsx'
import { api } from '../../lib/api'
import { flattenTree, toReorderPayload } from '../../lib/tree'

export default function MenuPage() {
  const { can } = useAuth()
  const mayReorder = can('menus.reorder')

  const [rows, setRows] = useState([])
  const [saved, setSaved] = useState([])
  const [editing, setEditing] = useState(null)
  const [newTitle, setNewTitle] = useState('')
  const [errors, setErrors] = useState({})
  const [error, setError] = useState(null)
  const [notice, setNotice] = useState(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)

  const load = useCallback(() => {
    setLoading(true)

    api
      .get('/menus')
      .then((response) => {
        const flat = flattenTree(response.data)
        setRows(flat)
        setSaved(flat)
      })
      .catch((problem) => setError(problem.message))
      .finally(() => setLoading(false))
  }, [])

  useEffect(load, [load])

  const dirty = JSON.stringify(rows) !== JSON.stringify(saved)

  async function saveOrder() {
    setSaving(true)
    setError(null)
    setNotice(null)

    try {
      const response = await api.post('/menus/reorder', { items: toReorderPayload(rows) })
      const flat = flattenTree(response.data)

      setRows(flat)
      setSaved(flat)
      setNotice('The new order is live on the public site.')
    } catch (problem) {
      setError(problem.message)
    } finally {
      setSaving(false)
    }
  }

  async function addItem(event) {
    event.preventDefault()

    setErrors({})
    setError(null)

    try {
      await api.post('/menus', { title: newTitle })
      setNewTitle('')
      setNotice('Menu item added at the end of the top level.')
      load()
    } catch (problem) {
      setErrors(problem.errors ?? {})
      setError(problem.message)
    }
  }

  async function saveItem(event) {
    event.preventDefault()

    setErrors({})
    setError(null)

    try {
      await api.put(`/menus/${editing.id}`, {
        title: editing.title,
        is_active: editing.isActive,
      })

      setEditing(null)
      setNotice('Menu item updated.')
      load()
    } catch (problem) {
      setErrors(problem.errors ?? {})
      setError(problem.message)
    }
  }

  async function deleteItem(row) {
    if (!window.confirm(`Delete "${row.title}" and everything nested under it?`)) {
      return
    }

    setError(null)

    try {
      const response = await api.delete(`/menus/${row.id}`)
      setNotice(response.message)
      load()
    } catch (problem) {
      // A 422 here means pages are still filed under the branch.
      setError(problem.message)
    }
  }

  if (loading) {
    return <p className="muted">Loading…</p>
  }

  return (
    <>
      <div className="between">
        <h1>Menu</h1>
        {mayReorder && (
          <div className="actions">
            {dirty && (
              <button type="button" onClick={() => setRows(saved)} disabled={saving}>
                Discard changes
              </button>
            )}
            <button type="button" className="primary" onClick={saveOrder} disabled={!dirty || saving}>
              {saving ? 'Saving…' : 'Save order'}
            </button>
          </div>
        )}
      </div>

      <p className="muted">
        Drag a heading to move it, and use the arrows to nest it under the one above. Moving a
        heading takes its children with it. The public site follows this order.
      </p>

      {error && <p className="notice error">{error}</p>}
      {notice && !dirty && <p className="notice success">{notice}</p>}
      {dirty && <p className="notice">Unsaved order. Nothing changes for readers until you save.</p>}

      <MenuTreeEditor
        rows={rows}
        onChange={setRows}
        sortable={mayReorder}
        onEdit={can('menus.update') ? (row) => setEditing({ ...row }) : null}
        onDelete={can('menus.delete') ? deleteItem : null}
      />

      {editing && (
        <form className="card" onSubmit={saveItem}>
          <h2>Edit “{saved.find((row) => row.id === editing.id)?.title}”</h2>

          <div className="field">
            <label htmlFor="edit-title">Title</label>
            <input
              id="edit-title"
              type="text"
              value={editing.title}
              onChange={(event) => setEditing({ ...editing, title: event.target.value })}
              required
            />
            <FieldError errors={errors} name="title" />
          </div>

          <div className="field">
            <label className="checkbox">
              <input
                type="checkbox"
                checked={editing.isActive}
                onChange={(event) => setEditing({ ...editing, isActive: event.target.checked })}
              />
              Visible on the public site
            </label>
            <p className="muted">
              Hiding a heading also hides everything nested beneath it.
            </p>
          </div>

          <div className="actions">
            <button type="submit" className="primary">
              Save changes
            </button>
            <button type="button" onClick={() => setEditing(null)}>
              Cancel
            </button>
          </div>
        </form>
      )}

      {can('menus.create') && (
        <form className="card" onSubmit={addItem}>
          <h2>Add a heading</h2>

          <div className="row">
            <div className="field" style={{ marginBottom: 0 }}>
              <label htmlFor="new-title">Title</label>
              <input
                id="new-title"
                type="text"
                value={newTitle}
                placeholder="Contact"
                onChange={(event) => setNewTitle(event.target.value)}
                required
              />
              <FieldError errors={errors} name="title" />
            </div>

            <div className="shrink">
              <button type="submit" className="primary">
                Add
              </button>
            </div>
          </div>
        </form>
      )}
    </>
  )
}

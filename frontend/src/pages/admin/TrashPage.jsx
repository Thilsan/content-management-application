import { useCallback, useEffect, useState } from 'react'
import Pagination from '../../components/Pagination.jsx'
import { api } from '../../lib/api'

export default function TrashPage() {
  const [pages, setPages] = useState([])
  const [meta, setMeta] = useState(null)
  const [page, setPage] = useState(1)
  const [error, setError] = useState(null)
  const [notice, setNotice] = useState(null)
  const [loading, setLoading] = useState(true)

  const load = useCallback(() => {
    setLoading(true)
    setError(null)

    api
      .get('/pages/trashed', { page, per_page: 10 })
      .then((response) => {
        setPages(response.data)
        setMeta(response.meta)
      })
      .catch((problem) => setError(problem.message))
      .finally(() => setLoading(false))
  }, [page])

  useEffect(load, [load])

  async function restore(row) {
    try {
      await api.post(`/pages/${row.id}/restore`)
      setNotice(`"${row.title}" is back in the pages list.`)
      load()
    } catch (problem) {
      setError(problem.message)
    }
  }

  async function destroy(row) {
    if (!window.confirm(`Delete "${row.title}" for good? This cannot be undone.`)) {
      return
    }

    try {
      const response = await api.delete(`/pages/${row.id}/force`)
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
          <p className="overline">Content</p>
          <h1>Trash</h1>
          <p className="lede">
            Deleted pages are kept here. Restoring one puts it back exactly as it was, cover image
            included.
          </p>
        </div>
      </div>

      {error && <p className="notice error">{error}</p>}
      {notice && <p className="notice success">{notice}</p>}

      <div className="card flush">
        {loading ? (
          <p className="empty">Loading…</p>
        ) : pages.length === 0 ? (
          <div className="empty">
            <strong>The trash is empty</strong>
            Deleted pages will show up here, ready to be restored.
          </div>
        ) : (
          <div className="table-scroll">
            <table>
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Menu</th>
                  <th>Deleted</th>
                  <th>Created by</th>
                  <th className="end" />
                </tr>
              </thead>
              <tbody>
                {pages.map((row) => (
                  <tr key={row.id}>
                    <td>
                      <strong>{row.title}</strong>
                      <span className="sub">/{row.slug}</span>
                    </td>
                    <td>{row.menu?.title ?? '—'}</td>
                    <td>
                      {row.deleted_at ? new Date(row.deleted_at).toLocaleString() : '—'}
                    </td>
                    <td>{row.created_by?.name ?? '—'}</td>
                    <td className="end">
                      <div className="actions">
                        <button type="button" className="tiny" onClick={() => restore(row)}>
                          Restore
                        </button>
                        <button type="button" className="tiny danger" onClick={() => destroy(row)}>
                          Delete for good
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {meta && meta.last_page > 1 && (
          <div style={{ padding: '0 1.35rem 1.1rem' }}>
            <Pagination meta={meta} onChange={setPage} />
          </div>
        )}
      </div>
    </>
  )
}

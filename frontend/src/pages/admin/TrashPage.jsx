import { useCallback, useEffect, useState } from 'react'
import EmptyState from '../../components/EmptyState.jsx'
import PageHeader from '../../components/PageHeader.jsx'
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
      <PageHeader
        overline="Content"
        title="Trash"
        lede="Deleted pages are kept here. Restoring one puts it back exactly as it was, cover image included."
      />

      {error && <p className="notice notice-error">{error}</p>}
      {notice && <p className="notice notice-success">{notice}</p>}

      <div className="card overflow-hidden">
        {loading ? (
          <EmptyState>Loading…</EmptyState>
        ) : pages.length === 0 ? (
          <EmptyState title="The trash is empty">
            Deleted pages will show up here, ready to be restored.
          </EmptyState>
        ) : (
          <div className="overflow-x-auto">
            <table className="data-table">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Menu</th>
                  <th>Deleted</th>
                  <th>Created by</th>
                  <th className="text-right" />
                </tr>
              </thead>
              <tbody>
                {pages.map((row) => (
                  <tr key={row.id}>
                    <td>
                      <strong className="font-medium">{row.title}</strong>
                      <span className="sub">/{row.slug}</span>
                    </td>
                    <td>{row.menu?.title ?? '—'}</td>
                    <td>{row.deleted_at ? new Date(row.deleted_at).toLocaleString() : '—'}</td>
                    <td>{row.created_by?.name ?? '—'}</td>
                    <td className="text-right">
                      <div className="flex flex-wrap items-center justify-end gap-1.5">
                        <button type="button" className="btn btn-tiny" onClick={() => restore(row)}>
                          Restore
                        </button>
                        <button
                          type="button"
                          className="btn btn-tiny btn-danger"
                          onClick={() => destroy(row)}
                        >
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
          <div className="px-5 pb-4">
            <Pagination meta={meta} onChange={setPage} />
          </div>
        )}
      </div>
    </>
  )
}

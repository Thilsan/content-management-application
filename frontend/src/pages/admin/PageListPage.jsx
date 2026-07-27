import { useCallback, useEffect, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../../auth/AuthContext.jsx'
import EmptyState from '../../components/EmptyState.jsx'
import PageHeader from '../../components/PageHeader.jsx'
import Pagination from '../../components/Pagination.jsx'
import StatusTag from '../../components/StatusTag.jsx'
import { api } from '../../lib/api'
import { flattenTree } from '../../lib/tree'

export default function PageListPage() {
  const { can } = useAuth()
  const location = useLocation()

  const [pages, setPages] = useState([])
  const [meta, setMeta] = useState(null)
  const [menus, setMenus] = useState([])
  const [search, setSearch] = useState('')
  const [menuId, setMenuId] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [error, setError] = useState(null)
  const [notice, setNotice] = useState(
    location.state?.saved ? `"${location.state.saved}" saved.` : null,
  )
  const [loading, setLoading] = useState(true)

  const load = useCallback(() => {
    setLoading(true)
    setError(null)

    api
      .get('/pages', { search, menu_id: menuId, status, page, per_page: 10 })
      .then((response) => {
        setPages(response.data)
        setMeta(response.meta)
      })
      .catch((problem) => setError(problem.message))
      .finally(() => setLoading(false))
  }, [search, menuId, status, page])

  useEffect(load, [load])

  useEffect(() => {
    if (!can('menus.view')) {
      return
    }

    api
      .get('/menus')
      .then((response) => setMenus(flattenTree(response.data)))
      .catch(() => setMenus([]))
  }, [can])

  async function handleDelete(row) {
    if (!window.confirm(`Move "${row.title}" to the trash?`)) {
      return
    }

    try {
      const response = await api.delete(`/pages/${row.id}`)
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
        title="Pages"
        lede="Every page including drafts and anything scheduled for a later date."
      >
        {can('pages.create') && (
          <Link to="/admin/pages/new" className="btn btn-primary">
            Add page
          </Link>
        )}
      </PageHeader>

      {error && <p className="notice notice-error">{error}</p>}
      {notice && <p className="notice notice-success">{notice}</p>}

      <div className="card mb-4 p-5">
        <div className="flex flex-wrap items-end gap-4">
          <div className="min-w-45 flex-1">
            <label className="label" htmlFor="search">
              Search by title
            </label>
            <input
              id="search"
              className="input"
              type="search"
              value={search}
              placeholder="report"
              onChange={(event) => {
                setPage(1)
                setSearch(event.target.value)
              }}
            />
          </div>

          <div className="min-w-45 flex-1">
            <label className="label" htmlFor="menu">
              Menu
            </label>
            <select
              id="menu"
              className="input"
              value={menuId}
              onChange={(event) => {
                setPage(1)
                setMenuId(event.target.value)
              }}
            >
              <option value="">Any</option>
              {menus.map((item) => (
                <option key={item.id} value={item.id}>
                  {'— '.repeat(item.depth)}
                  {item.title}
                </option>
              ))}
            </select>
          </div>

          <div className="min-w-45 flex-1">
            <label className="label" htmlFor="status">
              Status
            </label>
            <select
              id="status"
              className="input"
              value={status}
              onChange={(event) => {
                setPage(1)
                setStatus(event.target.value)
              }}
            >
              <option value="">Any</option>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
            </select>
          </div>
        </div>
      </div>

      <div className="card overflow-hidden">
        {loading ? (
          <EmptyState>Loading…</EmptyState>
        ) : pages.length === 0 ? (
          <EmptyState title="No pages match those filters">
            Try clearing the search or choosing a different menu.
          </EmptyState>
        ) : (
          <div className="overflow-x-auto">
            <table className="data-table">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Menu</th>
                  <th>Status</th>
                  <th>Publish date</th>
                  <th>Last edited by</th>
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
                    <td>
                      <StatusTag page={row} />
                    </td>
                    <td>
                      {row.published_at ? (
                        new Date(row.published_at).toLocaleDateString(undefined, {
                          day: 'numeric',
                          month: 'short',
                          year: 'numeric',
                        })
                      ) : (
                        <span className="text-muted">Immediate</span>
                      )}
                    </td>
                    <td>{row.updated_by?.name ?? '—'}</td>
                    <td className="text-right">
                      <div className="flex flex-wrap items-center justify-end gap-1.5">
                        {can('pages.update') && (
                          <Link to={`/admin/pages/${row.id}/edit`} className="btn btn-tiny">
                            Edit
                          </Link>
                        )}
                        {can('pages.delete') && (
                          <button
                            type="button"
                            className="btn btn-tiny btn-danger"
                            onClick={() => handleDelete(row)}
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

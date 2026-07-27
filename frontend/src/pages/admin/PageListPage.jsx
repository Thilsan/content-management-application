import { useCallback, useEffect, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../../auth/AuthContext.jsx'
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
      <div className="page-head">
        <div>
          <p className="overline">Content</p>
          <h1>Pages</h1>
          <p className="lede">
            Every page including drafts and anything scheduled for a later date.
          </p>
        </div>

        {can('pages.create') && (
          <Link to="/admin/pages/new" className="button primary">
            Add page
          </Link>
        )}
      </div>

      {error && <p className="notice error">{error}</p>}
      {notice && <p className="notice success">{notice}</p>}

      <div className="card" style={{ marginBottom: '1rem' }}>
        <div className="row">
          <div>
            <label htmlFor="search">Search by title</label>
            <input
              id="search"
              type="search"
              value={search}
              placeholder="report"
              onChange={(event) => {
                setPage(1)
                setSearch(event.target.value)
              }}
            />
          </div>

          <div>
            <label htmlFor="menu">Menu</label>
            <select
              id="menu"
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

          <div>
            <label htmlFor="status">Status</label>
            <select
              id="status"
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

      <div className="card flush">
        {loading ? (
          <p className="empty">Loading…</p>
        ) : pages.length === 0 ? (
          <div className="empty">
            <strong>No pages match those filters</strong>
            Try clearing the search or choosing a different menu.
          </div>
        ) : (
          <div className="table-scroll">
            <table>
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Menu</th>
                  <th>Status</th>
                  <th>Publish date</th>
                  <th>Last edited by</th>
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
                        <span className="muted">Immediate</span>
                      )}
                    </td>
                    <td>{row.updated_by?.name ?? '—'}</td>
                    <td className="end">
                      <div className="actions">
                        {can('pages.update') && (
                          <Link to={`/admin/pages/${row.id}/edit`} className="button tiny">
                            Edit
                          </Link>
                        )}
                        {can('pages.delete') && (
                          <button
                            type="button"
                            className="tiny danger"
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
          <div style={{ padding: '0 1.35rem 1.1rem' }}>
            <Pagination meta={meta} onChange={setPage} />
          </div>
        )}
      </div>
    </>
  )
}

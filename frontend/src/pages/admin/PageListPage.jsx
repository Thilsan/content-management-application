import { useCallback, useEffect, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../../auth/AuthContext.jsx'
import EmptyState from '../../components/EmptyState.jsx'
import PageHeader from '../../components/PageHeader.jsx'
import Pagination from '../../components/Pagination.jsx'
import StatusTag from '../../components/StatusTag.jsx'
import { api } from '../../lib/api'
import { flattenTree } from '../../lib/tree'

const FIELD =
  'w-full rounded-lg border border-neutral-300 px-3 py-2 text-[0.9rem] text-black transition-colors placeholder:text-neutral-400 hover:border-neutral-400 focus:border-black focus:ring-3 focus:ring-black/10 focus:outline-none'

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
        eyebrow="Content"
        title="Pages"
        lede="Every page including drafts and anything scheduled for a later date."
      >
        {can('pages.create') && (
          <Link
            to="/admin/pages/new"
            className="rounded-lg bg-black px-4 py-2 text-[0.88rem] font-medium text-white transition-colors hover:bg-neutral-800"
          >
            Add page
          </Link>
        )}
      </PageHeader>

      {error && (
        <p className="mb-4 rounded-lg border border-neutral-200 bg-neutral-50 px-3.5 py-2.5 text-[0.89rem] text-red-600">
          {error}
        </p>
      )}
      {notice && (
        <p className="mb-4 rounded-lg border border-neutral-200 bg-neutral-50 px-3.5 py-2.5 text-[0.89rem] text-black">
          {notice}
        </p>
      )}

      <div className="mb-4 rounded-xl border border-neutral-200 p-5">
        <div className="flex flex-wrap items-end gap-4">
          <div className="min-w-45 flex-1">
            <label className="mb-1.5 block text-[0.83rem] font-medium text-black" htmlFor="search">
              Search by title
            </label>
            <input
              id="search"
              className={FIELD}
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
            <label className="mb-1.5 block text-[0.83rem] font-medium text-black" htmlFor="menu">
              Menu
            </label>
            <select
              id="menu"
              className={FIELD}
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
            <label className="mb-1.5 block text-[0.83rem] font-medium text-black" htmlFor="status">
              Status
            </label>
            <select
              id="status"
              className={FIELD}
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

      <div className="overflow-hidden rounded-xl border border-neutral-200">
        {loading ? (
          <EmptyState>Loading…</EmptyState>
        ) : pages.length === 0 ? (
          <EmptyState title="No pages match those filters">
            Try clearing the search or choosing a different menu.
          </EmptyState>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full border-collapse text-[0.9rem]">
              <thead>
                <tr className="border-b border-neutral-200 bg-neutral-50">
                  <th className="px-4 py-3 text-left text-[0.7rem] font-semibold tracking-[0.08em] text-neutral-500 uppercase">
                    Title
                  </th>
                  <th className="px-4 py-3 text-left text-[0.7rem] font-semibold tracking-[0.08em] text-neutral-500 uppercase">
                    Menu
                  </th>
                  <th className="px-4 py-3 text-left text-[0.7rem] font-semibold tracking-[0.08em] text-neutral-500 uppercase">
                    Status
                  </th>
                  <th className="px-4 py-3 text-left text-[0.7rem] font-semibold tracking-[0.08em] text-neutral-500 uppercase">
                    Publish date
                  </th>
                  <th className="px-4 py-3 text-left text-[0.7rem] font-semibold tracking-[0.08em] text-neutral-500 uppercase">
                    Last edited by
                  </th>
                  <th className="px-4 py-3" />
                </tr>
              </thead>
              <tbody>
                {pages.map((row) => (
                  <tr
                    key={row.id}
                    className="border-b border-neutral-100 last:border-b-0 hover:bg-neutral-50"
                  >
                    <td className="px-4 py-3.5">
                      <strong className="font-medium text-black">{row.title}</strong>
                      <span className="mt-0.5 block text-[0.79rem] text-neutral-400">
                        /{row.slug}
                      </span>
                    </td>
                    <td className="px-4 py-3.5 text-neutral-700">{row.menu?.title ?? '—'}</td>
                    <td className="px-4 py-3.5">
                      <StatusTag page={row} />
                    </td>
                    <td className="px-4 py-3.5 text-neutral-700">
                      {row.published_at ? (
                        new Date(row.published_at).toLocaleDateString(undefined, {
                          day: 'numeric',
                          month: 'short',
                          year: 'numeric',
                        })
                      ) : (
                        <span className="text-neutral-400">Immediate</span>
                      )}
                    </td>
                    <td className="px-4 py-3.5 text-neutral-700">{row.updated_by?.name ?? '—'}</td>
                    <td className="px-4 py-3.5 text-right">
                      <div className="flex flex-wrap items-center justify-end gap-1.5">
                        {can('pages.update') && (
                          <Link
                            to={`/admin/pages/${row.id}/edit`}
                            className="rounded-md border border-neutral-300 px-2.5 py-1 text-[0.8rem] font-medium text-black transition-colors hover:bg-neutral-100"
                          >
                            Edit
                          </Link>
                        )}
                        {can('pages.delete') && (
                          <button
                            type="button"
                            onClick={() => handleDelete(row)}
                            className="rounded-md border border-transparent px-2.5 py-1 text-[0.8rem] font-medium text-red-600 transition-colors hover:bg-red-50"
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
          <div className="border-t border-neutral-200 px-5 py-4">
            <Pagination meta={meta} onChange={setPage} />
          </div>
        )}
      </div>
    </>
  )
}

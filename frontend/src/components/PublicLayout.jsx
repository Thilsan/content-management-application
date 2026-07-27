import { useEffect, useState } from 'react'
import { Link, NavLink, Outlet } from 'react-router-dom'
import { api } from '../lib/api'

const LEAF = 'block rounded-field border-l-2 border-transparent px-2 py-1.5 text-[0.895rem]'

function MenuNav({ items, depth }) {
  if (items.length === 0) {
    return null
  }

  return (
    <ul className={depth === 0 ? '' : 'ml-2 border-l border-line pl-2.5'}>
      {items.map((item) => (
        <li key={item.id}>
          <span className="overline block px-2 pt-3.5 pb-1">{item.title}</span>

          {item.pages.map((page) => (
            <NavLink
              key={page.id}
              to={`/pages/${page.slug}`}
              className={({ isActive }) =>
                isActive
                  ? `${LEAF} border-l-accent bg-accent-wash font-medium text-accent-strong`
                  : `${LEAF} text-ink-soft hover:bg-wash hover:text-ink`
              }
            >
              {page.title}
            </NavLink>
          ))}

          <MenuNav items={item.children} depth={depth + 1} />
        </li>
      ))}
    </ul>
  )
}

export default function PublicLayout() {
  const [menu, setMenu] = useState([])
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api
      .get('/public/menu')
      .then((response) => setMenu(response.data))
      .catch((problem) => setError(problem.message))
      .finally(() => setLoading(false))
  }, [])

  return (
    <>
      <header className="sticky top-0 z-20 flex h-14 items-center gap-6 border-b border-line bg-surface/85 px-6 backdrop-blur-md backdrop-saturate-150">
        <Link
          to="/"
          className="flex items-center gap-2 text-[0.95rem] font-semibold tracking-tight whitespace-nowrap text-ink hover:text-ink"
        >
          <span className="grid size-6 place-items-center rounded-[7px] bg-linear-to-br from-accent to-[#6d4bf0] text-[0.72rem] font-bold text-white">
            CM
          </span>
          Content
        </Link>

        <span className="ml-auto" />

        <Link to="/admin" className="text-[0.88rem] font-medium text-ink-soft hover:text-ink">
          Back office
        </Link>
      </header>

      <div className="mx-auto grid max-w-[1120px] grid-cols-1 items-start gap-7 px-6 pt-8 pb-16 md:grid-cols-[216px_minmax(0,1fr)] md:gap-12">
        <aside className="md:sticky md:top-20">
          <MenuNav items={menu} depth={0} />
        </aside>

        <main>
          {error && <p className="notice notice-error">{error}</p>}
          <Outlet context={{ menu, loading }} />
        </main>
      </div>
    </>
  )
}

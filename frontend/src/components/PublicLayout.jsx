import { useEffect, useState } from 'react'
import { Link, NavLink, Outlet } from 'react-router-dom'
import { api } from '../lib/api'

function MenuNav({ items, depth }) {
  if (items.length === 0) {
    return null
  }

  return (
    <ul className={depth === 0 ? 'menu-tree' : 'branch'}>
      {items.map((item) => (
        <li key={item.id}>
          <span className="section">{item.title}</span>

          {item.pages.map((page) => (
            <NavLink key={page.id} to={`/pages/${page.slug}`} className="leaf">
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
      <header className="topbar">
        <Link to="/" className="brand">
          <span className="mark">CM</span>
          Content
        </Link>

        <span className="spacer" />

        <Link to="/admin" className="quiet-link">
          Back office
        </Link>
      </header>

      <div className="shell columns">
        <aside>
          <MenuNav items={menu} depth={0} />
        </aside>

        <main>
          {error && <p className="notice error">{error}</p>}
          <Outlet context={{ menu, loading }} />
        </main>
      </div>
    </>
  )
}

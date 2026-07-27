import { useEffect, useState } from 'react'
import { Link, Outlet } from 'react-router-dom'
import { api } from '../lib/api'

function MenuNav({ items }) {
  if (items.length === 0) {
    return null
  }

  return (
    <ul className="menu-tree">
      {items.map((item) => (
        <li key={item.id}>
          <span className="section">{item.title}</span>

          {item.pages.length > 0 && (
            <ul>
              {item.pages.map((page) => (
                <li key={page.id}>
                  <Link to={`/pages/${page.slug}`}>{page.title}</Link>
                </li>
              ))}
            </ul>
          )}

          <MenuNav items={item.children} />
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
          CMS
        </Link>
        <span className="spacer" />
        <Link to="/admin">Back office</Link>
      </header>

      <div className="shell columns">
        <aside>
          <MenuNav items={menu} />
        </aside>

        <main>
          {error && <p className="notice error">{error}</p>}
          <Outlet context={{ menu, loading }} />
        </main>
      </div>
    </>
  )
}

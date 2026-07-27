import { Link, useOutletContext } from 'react-router-dom'

function Teaser({ page }) {
  return (
    <Link to={`/pages/${page.slug}`} className="teaser">
      <div className="body">
        <span className="heading">{page.title}</span>
        <p className="excerpt">{page.excerpt}</p>
        {page.published_at && (
          <div className="meta">{new Date(page.published_at).toLocaleDateString()}</div>
        )}
      </div>

      {page.cover_image_url && <img className="thumb" src={page.cover_image_url} alt="" />}
    </Link>
  )
}

function Group({ item }) {
  return (
    <>
      {item.pages.length > 0 && (
        <section className="group">
          <div className="group-head">
            <h2>{item.title}</h2>
            <span className="count">
              {item.pages.length} {item.pages.length === 1 ? 'page' : 'pages'}
            </span>
          </div>

          <div className="teaser-list">
            {item.pages.map((page) => (
              <Teaser key={page.id} page={page} />
            ))}
          </div>
        </section>
      )}

      {item.children.map((child) => (
        <Group key={child.id} item={child} />
      ))}
    </>
  )
}

export default function HomePage() {
  const { menu, loading } = useOutletContext()

  if (loading) {
    return <p className="muted">Loading…</p>
  }

  const hasPages = (items) =>
    items.some((item) => item.pages.length > 0 || hasPages(item.children))

  if (!hasPages(menu)) {
    return (
      <div className="card">
        <div className="empty">
          <strong>Nothing published yet</strong>
          Pages appear here once they are published and their publish date has passed.
        </div>
      </div>
    )
  }

  return (
    <>
      <div className="page-head">
        <div>
          <p className="overline">Index</p>
          <h1>Published pages</h1>
          <p className="lede">
            Everything currently live, grouped under the menu item it belongs to.
          </p>
        </div>
      </div>

      {menu.map((item) => (
        <Group key={item.id} item={item} />
      ))}
    </>
  )
}

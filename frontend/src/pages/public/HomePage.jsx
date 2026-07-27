import { Link, useOutletContext } from 'react-router-dom'

function Section({ item, depth }) {
  return (
    <section>
      <h2 style={{ marginLeft: depth * 14 }}>{item.title}</h2>

      {item.pages.length === 0 ? (
        <p className="muted" style={{ marginLeft: depth * 14 }}>
          Nothing published under this heading yet.
        </p>
      ) : (
        item.pages.map((page) => (
          <article key={page.id} className="card">
            <div className="between">
              <div>
                <Link to={`/pages/${page.slug}`}>
                  <strong>{page.title}</strong>
                </Link>
                <p className="muted">{page.excerpt}</p>
              </div>

              {page.cover_image_url && (
                <img className="cover thumb" src={page.cover_image_url} alt="" />
              )}
            </div>
          </article>
        ))
      )}

      {item.children.map((child) => (
        <Section key={child.id} item={child} depth={depth + 1} />
      ))}
    </section>
  )
}

export default function HomePage() {
  const { menu, loading } = useOutletContext()

  if (loading) {
    return <p className="muted">Loading…</p>
  }

  if (menu.length === 0) {
    return (
      <div className="card">
        <h1>Nothing published yet</h1>
        <p className="muted">
          Pages appear here once they are published and their publish date has passed.
        </p>
      </div>
    )
  }

  return (
    <>
      <h1>Published pages</h1>
      {menu.map((item) => (
        <Section key={item.id} item={item} depth={0} />
      ))}
    </>
  )
}

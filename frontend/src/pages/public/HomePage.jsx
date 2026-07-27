import { Link, useOutletContext } from 'react-router-dom'
import EmptyState from '../../components/EmptyState.jsx'

function Teaser({ page }) {
  return (
    <Link
      to={`/pages/${page.slug}`}
      className="group flex items-start gap-4 rounded-card border border-line bg-surface px-5 py-4 shadow-card transition hover:-translate-y-px hover:border-line-strong hover:shadow-lift"
    >
      <div className="min-w-0 flex-1">
        <span className="block text-[1.02rem] font-semibold tracking-tight text-ink group-hover:text-accent-strong">
          {page.title}
        </span>

        <p className="mt-1 line-clamp-2 text-[0.9rem] text-ink-soft">{page.excerpt}</p>

        {page.published_at && (
          <div className="mt-2 text-[0.78rem] text-muted">
            {new Date(page.published_at).toLocaleDateString()}
          </div>
        )}
      </div>

      {page.cover_image_url && (
        <img
          className="hidden h-18 w-26 flex-none rounded-panel border border-line object-cover sm:block"
          src={page.cover_image_url}
          alt=""
        />
      )}
    </Link>
  )
}

function Group({ item }) {
  return (
    <>
      {item.pages.length > 0 && (
        <section className="mt-10 first:mt-0">
          <div className="mb-4 flex items-baseline gap-3 border-b border-line pb-2.5">
            <h2 className="text-[1.12rem]">{item.title}</h2>
            <span className="text-[0.78rem] text-muted">
              {item.pages.length} {item.pages.length === 1 ? 'page' : 'pages'}
            </span>
          </div>

          <div className="grid gap-3">
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
    return <p className="text-[0.88rem] text-muted">Loading…</p>
  }

  const hasPages = (items) => items.some((item) => item.pages.length > 0 || hasPages(item.children))

  if (!hasPages(menu)) {
    return (
      <div className="card">
        <EmptyState title="Nothing published yet">
          Pages appear here once they are published and their publish date has passed.
        </EmptyState>
      </div>
    )
  }

  return (
    <>
      <div className="mb-6">
        <p className="overline">Index</p>
        <h1 className="text-2xl">Published pages</h1>
        <p className="lede">
          Everything currently live, grouped under the menu item it belongs to.
        </p>
      </div>

      {menu.map((item) => (
        <Group key={item.id} item={item} />
      ))}
    </>
  )
}

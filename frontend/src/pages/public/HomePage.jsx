import { useMemo, useState } from 'react'
import { Link, useOutletContext } from 'react-router-dom'
import EmptyState from '../../components/EmptyState.jsx'
import { formatDate } from '../../lib/format'

/** Flatten the tree into the sections that actually hold pages. */
function toSections(items) {
  return items.flatMap((item) => [
    ...(item.pages.length > 0 ? [{ id: item.id, title: item.title, pages: item.pages }] : []),
    ...toSections(item.children),
  ])
}

function Card({ page }) {
  return (
    <Link
      to={`/pages/${page.slug}`}
      className="group flex flex-col rounded-card border border-line bg-surface p-5 shadow-card transition duration-150 hover:-translate-y-0.5 hover:border-accent/40 hover:shadow-lift"
    >
      {page.cover_image_url && (
        <img
          className="mb-4 h-36 w-full rounded-panel border border-line object-cover"
          src={page.cover_image_url}
          alt=""
        />
      )}

      <h3 className="text-[1.05rem] leading-snug text-ink group-hover:text-accent-strong">
        {page.title}
      </h3>

      <p className="mt-2 line-clamp-3 flex-1 text-[0.89rem] text-ink-soft">{page.excerpt}</p>

      <div className="mt-4 flex items-center justify-between border-t border-line pt-3 text-[0.78rem] text-muted">
        <span>{formatDate(page.published_at) ?? 'Undated'}</span>
        <span className="font-medium text-accent opacity-0 transition-opacity group-hover:opacity-100">
          Read →
        </span>
      </div>
    </Link>
  )
}

export default function HomePage() {
  const { menu, loading } = useOutletContext()
  const [query, setQuery] = useState('')

  const sections = useMemo(() => toSections(menu), [menu])

  const total = useMemo(
    () => sections.reduce((count, section) => count + section.pages.length, 0),
    [sections],
  )

  // Every published page is already in the tree, so filtering here keeps the
  // search instant rather than making a request per keystroke.
  const results = useMemo(() => {
    const term = query.trim().toLowerCase()

    if (!term) {
      return sections
    }

    return sections
      .map((section) => ({
        ...section,
        pages: section.pages.filter(
          (page) =>
            page.title.toLowerCase().includes(term) ||
            page.excerpt.toLowerCase().includes(term),
        ),
      }))
      .filter((section) => section.pages.length > 0)
  }, [sections, query])

  const found = results.reduce((count, section) => count + section.pages.length, 0)

  if (loading) {
    return <p className="text-[0.88rem] text-muted">Loading…</p>
  }

  if (total === 0) {
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
      <div className="mb-9">
        <h1 className="text-[2.1rem] tracking-[-0.03em]">Published pages</h1>
        <p className="lede text-[0.98rem]">
          {total} {total === 1 ? 'page' : 'pages'} across {sections.length}{' '}
          {sections.length === 1 ? 'section' : 'sections'}, grouped the way the menu is ordered.
        </p>

        <div className="relative mt-5 max-w-md">
          <svg
            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted"
            viewBox="0 0 16 16"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            aria-hidden="true"
          >
            <circle cx="7" cy="7" r="4.5" />
            <path d="m10.5 10.5 3 3" strokeLinecap="round" />
          </svg>

          <input
            type="search"
            className="input pl-9"
            placeholder="Search these pages"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            aria-label="Search published pages"
          />
        </div>

        {query.trim() && (
          <p className="mt-2 text-[0.83rem] text-muted">
            {found} {found === 1 ? 'match' : 'matches'} for “{query.trim()}”
          </p>
        )}
      </div>

      {results.length === 0 ? (
        <div className="card">
          <EmptyState title="No pages match that search">
            Try a shorter word, or{' '}
            <button type="button" className="text-accent underline" onClick={() => setQuery('')}>
              clear the search
            </button>
            .
          </EmptyState>
        </div>
      ) : (
        results.map((section) => (
          <section key={section.id} className="mt-9 first:mt-0">
            <div className="mb-4 flex items-baseline gap-3">
              <h2 className="text-[1.15rem]">{section.title}</h2>
              <span className="text-[0.78rem] text-muted">
                {section.pages.length} {section.pages.length === 1 ? 'page' : 'pages'}
              </span>
              <span className="h-px flex-1 bg-line" />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              {section.pages.map((page) => (
                <Card key={page.id} page={page} />
              ))}
            </div>
          </section>
        ))
      )}
    </>
  )
}

import { useMemo, useState } from 'react'
import { Link, useOutletContext } from 'react-router-dom'
import EmptyState from '../../components/EmptyState.jsx'
import PageThumb from '../../components/PageThumb.jsx'
import { isRecent } from '../../lib/cover'
import { formatDate } from '../../lib/format'

/** Flatten the tree into the sections that actually hold pages. */
function toSections(items) {
  return items.flatMap((item) => [
    ...(item.pages.length > 0 ? [{ id: item.id, title: item.title, pages: item.pages }] : []),
    ...toSections(item.children),
  ])
}

/*
 * Rows rather than cards. A section here often holds a single page, and one
 * card in a grid leaves an obvious hole next to it; a list reads correctly
 * whether the section has one page or twenty.
 */
function Row({ page }) {
  return (
    <Link
      to={`/pages/${page.slug}`}
      className="group flex items-start gap-4 px-5 py-4 transition-colors hover:bg-[#fbfcfd]"
    >
      <PageThumb page={page} className="size-11 flex-none text-[1.05rem]" />

      <div className="min-w-0 flex-1">
        <div className="flex items-baseline justify-between gap-4">
          <h3 className="flex items-center gap-2 text-[1.02rem] text-ink group-hover:text-accent-strong">
            {page.title}
            {isRecent(page.published_at) && <span className="tag tag-scheduled">New</span>}
          </h3>
          <span className="shrink-0 text-[0.78rem] whitespace-nowrap text-muted">
            {formatDate(page.published_at)}
          </span>
        </div>

        <p className="mt-1 line-clamp-2 text-[0.89rem] text-ink-soft">{page.excerpt}</p>
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
            page.title.toLowerCase().includes(term) || page.excerpt.toLowerCase().includes(term),
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
      <div className="mb-8 max-w-185">
        <h1 className="text-[2.1rem] tracking-[-0.03em]">Published pages</h1>
        <p className="lede text-[0.98rem]">
          {total} {total === 1 ? 'page' : 'pages'} across {sections.length}{' '}
          {sections.length === 1 ? 'section' : 'sections'}, in the order the menu sets.
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
        <div className="grid gap-6 lg:grid-cols-2">
          {results.map((section) => (
            <section key={section.id}>
              <div className="mb-2.5 flex items-baseline gap-3 px-1">
                <h2 className="text-[1.05rem]">{section.title}</h2>
                <span className="text-[0.78rem] text-muted">
                  {section.pages.length} {section.pages.length === 1 ? 'page' : 'pages'}
                </span>
              </div>

              <div className="card divide-y divide-line overflow-hidden p-0">
                {section.pages.map((page) => (
                  <Row key={page.id} page={page} />
                ))}
              </div>
            </section>
          ))}
        </div>
      )}
    </>
  )
}

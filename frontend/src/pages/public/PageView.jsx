import { useEffect, useMemo, useState } from 'react'
import { Link, useOutletContext, useParams } from 'react-router-dom'
import EmptyState from '../../components/EmptyState.jsx'
import PageThumb from '../../components/PageThumb.jsx'
import { api } from '../../lib/api'
import { readingMinutes } from '../../lib/cover'
import { flattenPages, formatLongDate } from '../../lib/format'
import useDocumentTitle from '../../lib/useDocumentTitle'

export default function PageView() {
  const { slug } = useParams()
  const { menu } = useOutletContext()

  const [page, setPage] = useState(null)
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(true)

  useDocumentTitle(page?.title)

  useEffect(() => {
    setLoading(true)
    setError(null)
    window.scrollTo(0, 0)

    api
      .get(`/public/pages/${slug}`)
      .then((response) => setPage(response.data))
      .catch((problem) =>
        setError(
          problem.status === 404
            ? 'This page is not available. It may be a draft, or its publish date may not have arrived yet.'
            : problem.message,
        ),
      )
      .finally(() => setLoading(false))
  }, [slug])

  // Neighbours in menu order, so a reader can move through the site without
  // going back to the index every time.
  const { previous, next } = useMemo(() => {
    const all = flattenPages(menu ?? [])
    const index = all.findIndex((item) => item.slug === slug)

    if (index === -1) {
      return { previous: null, next: null }
    }

    return { previous: all[index - 1] ?? null, next: all[index + 1] ?? null }
  }, [menu, slug])

  if (loading) {
    return <p className="text-[0.88rem] text-muted">Loading…</p>
  }

  if (error) {
    return (
      <div className="card mx-auto max-w-185">
        <EmptyState title="Not available">
          {error}
          <p className="mt-4">
            <Link to="/">Back to the index</Link>
          </p>
        </EmptyState>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-185">
      <article>
        <p className="mb-3 text-[0.8rem] text-muted">
          <Link to="/" className="text-muted hover:text-ink">
            Index
          </Link>
          {page.menu && (
            <>
              <span className="mx-1.5 text-line-strong">/</span>
              <span className="text-ink-soft">{page.menu.title}</span>
            </>
          )}
        </p>

        <h1 className="text-[2.15rem] leading-[1.15] tracking-[-0.03em]">{page.title}</h1>

        <p className="mt-3 flex flex-wrap items-center gap-x-2 text-[0.85rem] text-muted">
          {page.published_at && <span>Published {formatLongDate(page.published_at)}</span>}
          {page.published_at && <span aria-hidden="true">·</span>}
          <span>{readingMinutes(page.body)} min read</span>
        </p>

        {page.cover_image_url && (
          <img
            className="mt-7 max-h-90 w-full rounded-card border border-line object-cover"
            src={page.cover_image_url}
            alt=""
          />
        )}

        {/*
          The body is HTML written by an authenticated editor in CKEditor, so it
          is rendered as markup rather than escaped text.
        */}
        <div className="page-body mt-7" dangerouslySetInnerHTML={{ __html: page.body }} />
      </article>

      {(previous || next) && (
        <nav className="mt-12 grid gap-3 border-t border-line pt-6 sm:grid-cols-2">
          {previous ? (
            <Link
              to={`/pages/${previous.slug}`}
              className="group flex items-center gap-3 rounded-card border border-line bg-surface p-4 shadow-card transition hover:border-accent/40 hover:shadow-lift"
            >
              <PageThumb page={previous} className="size-10 flex-none text-[0.95rem]" />
              <span className="min-w-0">
                <span className="eyebrow">Previous</span>
                <span className="mt-0.5 block truncate text-[0.95rem] font-medium text-ink group-hover:text-accent-strong">
                  {previous.title}
                </span>
              </span>
            </Link>
          ) : (
            <span />
          )}

          {next && (
            <Link
              to={`/pages/${next.slug}`}
              className="group flex items-center justify-end gap-3 rounded-card border border-line bg-surface p-4 shadow-card transition hover:border-accent/40 hover:shadow-lift"
            >
              <span className="min-w-0 text-right">
                <span className="eyebrow">Next</span>
                <span className="mt-0.5 block truncate text-[0.95rem] font-medium text-ink group-hover:text-accent-strong">
                  {next.title}
                </span>
              </span>
              <PageThumb page={next} className="size-10 flex-none text-[0.95rem]" />
            </Link>
          )}
        </nav>
      )}
    </div>
  )
}

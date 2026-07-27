import { useEffect, useMemo, useState } from 'react'
import { Link, useOutletContext, useParams } from 'react-router-dom'
import EmptyState from '../../components/EmptyState.jsx'
import PageThumb from '../../components/PageThumb.jsx'
import { api } from '../../lib/api'
import { coverStyle, readingMinutes } from '../../lib/cover'
import { flattenPages } from '../../lib/format'
import { useDateFormatter, useLocale } from '../../lib/LocaleContext.jsx'
import { withHeadingIds } from '../../lib/toc'
import useDocumentTitle from '../../lib/useDocumentTitle'

function CopyLinkButton() {
  const { t } = useLocale()
  const [copied, setCopied] = useState(false)

  async function copy() {
    try {
      await navigator.clipboard.writeText(window.location.href)
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    } catch {
      // Clipboard access can be refused; leaving the label alone says enough.
    }
  }

  return (
    <button type="button" className="btn btn-tiny" onClick={copy}>
      {copied ? t.linkCopied : t.copyLink}
    </button>
  )
}

function Contents({ headings }) {
  const { t } = useLocale()

  return (
    <nav aria-label={t.onThisPage} className="lg:sticky lg:top-20">
      <p className="eyebrow mb-2">{t.onThisPage}</p>

      <ul className="space-y-0.5 border-s border-line">
        {headings.map((heading) => (
          <li key={heading.id}>
            <a
              href={`#${heading.id}`}
              className="-ms-px block border-s-2 border-transparent py-1 text-[0.85rem] text-ink-soft hover:border-accent hover:text-ink"
              style={{ paddingInlineStart: heading.level === 3 ? 22 : 12 }}
            >
              {heading.text}
            </a>
          </li>
        ))}
      </ul>
    </nav>
  )
}

export default function PageView() {
  const { slug } = useParams()
  const { menu } = useOutletContext()
  const { t, locale } = useLocale()
  const formatDate = useDateFormatter()

  const [page, setPage] = useState(null)
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(true)

  useDocumentTitle(page?.title)

  useEffect(() => {
    setLoading(true)
    setError(null)
    window.scrollTo(0, 0)

    api
      .get(`/public/pages/${slug}`, { lang: locale })
      .then((response) => setPage(response.data))
      .catch((problem) =>
        setError(
          problem.status === 404
            ? t.notAvailableDetail
            : problem.message,
        ),
      )
      .finally(() => setLoading(false))
  }, [slug, locale])

  const { html, headings } = useMemo(() => withHeadingIds(page?.body), [page?.body])

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
    return <p className="text-[0.88rem] text-muted">{t.loading}</p>
  }

  if (error) {
    return (
      <div className="card mx-auto max-w-185">
        <EmptyState title={t.notAvailable}>
          {error}
          <p className="mt-4">
            <Link to="/">{t.backToIndex}</Link>
          </p>
        </EmptyState>
      </div>
    )
  }

  // Only worth a contents column when there is something to navigate between.
  const showContents = headings.length >= 2

  return (
    <div
      className={
        showContents
          ? 'grid gap-12 lg:grid-cols-[minmax(0,1fr)_190px]'
          : 'mx-auto flex max-w-185 flex-col'
      }
    >
      <div className="min-w-0 max-w-185">
        <article>
          <p className="mb-3 text-[0.8rem] text-muted">
            <Link to="/" className="text-muted hover:text-ink">
              {t.index}
            </Link>
            {page.menu && (
              <>
                <span className="mx-1.5 text-line-strong">/</span>
                <span className="text-ink-soft">{page.menu.title}</span>
              </>
            )}
          </p>

          {/* Without a cover, the page still gets a splash of its own colour. */}
          {!page.cover_image_url && (
            <span
              className="mb-5 block h-1.5 w-16 rounded-full"
              style={coverStyle(page.slug)}
              aria-hidden="true"
            />
          )}

          <h1 className="text-[2.15rem] leading-[1.15] tracking-[-0.03em]">{page.title}</h1>

          {locale === 'ar' && page.is_translated === false && (
            <p className="notice mt-4 mb-0" dir="rtl">
              {t.onlyInEnglish}
            </p>
          )}

          <div className="mt-4 flex flex-wrap items-center gap-x-2 gap-y-2 text-[0.85rem] text-muted">
            {page.published_at && (
              <span>
                {t.published}{' '}
                {formatDate(page.published_at, { day: 'numeric', month: 'long', year: 'numeric' })}
              </span>
            )}
            {page.published_at && <span aria-hidden="true">·</span>}
            <span>
              {readingMinutes(page.body)} {t.minRead}
            </span>
            <span className="ms-auto">
              <CopyLinkButton />
            </span>
          </div>

          {page.cover_image_url && (
            <img
              className="mt-7 max-h-90 w-full rounded-card border border-line object-cover"
              src={page.cover_image_url}
              alt=""
            />
          )}

          {/*
            The body is HTML written by an authenticated editor in CKEditor, so
            it is rendered as markup rather than escaped text.
          */}
          <div
            className="page-body mt-7"
            dir={page.direction ?? 'ltr'}
            dangerouslySetInnerHTML={{ __html: html }}
          />
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
                  <span className="eyebrow">{t.previous}</span>
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
                <span className="min-w-0 text-end">
                  <span className="eyebrow">{t.next}</span>
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

      {showContents && (
        <aside className="hidden lg:block">
          <Contents headings={headings} />
        </aside>
      )}
    </div>
  )
}

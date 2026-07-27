import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { api } from '../../lib/api'

export default function PageView() {
  const { slug } = useParams()
  const [page, setPage] = useState(null)
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    setLoading(true)
    setError(null)

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

  if (loading) {
    return <p className="muted">Loading…</p>
  }

  if (error) {
    return (
      <div className="card reading">
        <div className="empty">
          <strong>Not available</strong>
          {error}
          <p style={{ marginTop: '1rem' }}>
            <Link to="/">Back to the index</Link>
          </p>
        </div>
      </div>
    )
  }

  return (
    <article className="article reading">
      <p className="breadcrumb">
        <Link to="/">Index</Link>
        {page.menu && (
          <>
            <span className="sep">/</span>
            {page.menu.title}
          </>
        )}
      </p>

      <h1>{page.title}</h1>

      {page.published_at && (
        <p className="byline">
          Published{' '}
          {new Date(page.published_at).toLocaleDateString(undefined, {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
          })}
        </p>
      )}

      {page.cover_image_url && <img className="cover" src={page.cover_image_url} alt="" />}

      {/*
        The body is HTML written by an authenticated editor in CKEditor, so it is
        rendered as markup rather than escaped text.
      */}
      <div className="page-body" dangerouslySetInnerHTML={{ __html: page.body }} />
    </article>
  )
}

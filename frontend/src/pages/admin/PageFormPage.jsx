import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import FieldError from '../../components/FieldError.jsx'
import PageHeader from '../../components/PageHeader.jsx'
import RichTextEditor from '../../components/RichTextEditor.jsx'
import { api } from '../../lib/api'
import { flattenTree } from '../../lib/tree'

/** An ISO timestamp as the local value a datetime-local input expects. */
function toLocalInput(iso) {
  if (!iso) {
    return ''
  }

  const date = new Date(iso)
  const pad = (value) => String(value).padStart(2, '0')

  return (
    `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}` +
    `T${pad(date.getHours())}:${pad(date.getMinutes())}`
  )
}

const BLANK = {
  menu_id: '',
  title: '',
  slug: '',
  body: '',
  status: 'draft',
  published_at: '',
  position: 0,
}

export default function PageFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const editing = Boolean(id)

  const [form, setForm] = useState(BLANK)
  const [menus, setMenus] = useState([])
  const [cover, setCover] = useState(null)
  const [existingCover, setExistingCover] = useState(null)
  const [removeCover, setRemoveCover] = useState(false)
  const [errors, setErrors] = useState({})
  const [message, setMessage] = useState(null)
  const [loading, setLoading] = useState(editing)
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    api
      .get('/menus')
      .then((response) => setMenus(flattenTree(response.data)))
      .catch((problem) => setMessage(problem.message))
  }, [])

  useEffect(() => {
    if (!editing) {
      return
    }

    api
      .get(`/pages/${id}`)
      .then((response) => {
        const page = response.data

        setForm({
          menu_id: page.menu_id,
          title: page.title,
          slug: page.slug,
          body: page.body,
          status: page.status,
          published_at: toLocalInput(page.published_at),
          position: page.position,
        })

        setExistingCover(page.cover_image_url)
      })
      .catch((problem) => setMessage(problem.message))
      .finally(() => setLoading(false))
  }, [editing, id])

  function update(field, value) {
    setForm((current) => ({ ...current, [field]: value }))
  }

  async function handleSubmit(event) {
    event.preventDefault()

    setSaving(true)
    setErrors({})
    setMessage(null)

    const payload = new FormData()

    payload.append('menu_id', form.menu_id)
    payload.append('title', form.title)
    payload.append('body', form.body)
    payload.append('status', form.status)
    payload.append('position', form.position ?? 0)

    // The input holds local time; the API stores UTC.
    payload.append(
      'published_at',
      form.published_at ? new Date(form.published_at).toISOString() : '',
    )

    if (form.slug) {
      payload.append('slug', form.slug)
    }

    if (cover) {
      payload.append('cover_image', cover)
    }

    if (editing) {
      // Spoofed so the request arrives as POST and PHP still parses the upload.
      payload.append('_method', 'PUT')

      if (removeCover) {
        payload.append('remove_cover', '1')
      }
    }

    try {
      const response = await api.postForm(editing ? `/pages/${id}` : '/pages', payload)

      navigate('/admin/pages', { state: { saved: response.data.title } })
    } catch (problem) {
      setErrors(problem.errors ?? {})
      setMessage(problem.message)
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return <p className="text-[0.88rem] text-muted">Loading…</p>
  }

  return (
    <>
      <PageHeader eyebrow={editing ? 'Editing' : 'New'} title={editing ? 'Edit page' : 'Add page'}>
        <Link to="/admin/pages" className="btn">
          Back to pages
        </Link>
      </PageHeader>

      {message && <p className="notice notice-error">{message}</p>}

      <form onSubmit={handleSubmit}>
        <div className="card p-5">
          <div className="mb-4">
            <label className="label" htmlFor="title">
              Title
            </label>
            <input
              id="title"
              className="input"
              type="text"
              value={form.title}
              onChange={(event) => update('title', event.target.value)}
              required
            />
            <FieldError errors={errors} name="title" />
          </div>

          <div className="mb-4 flex flex-wrap items-end gap-4">
            <div className="min-w-45 flex-1">
              <label className="label" htmlFor="menu_id">
                Menu item
              </label>
              <select
                id="menu_id"
                className="input"
                value={form.menu_id}
                onChange={(event) => update('menu_id', event.target.value)}
                required
              >
                <option value="">Choose one</option>
                {menus.map((item) => (
                  <option key={item.id} value={item.id}>
                    {'— '.repeat(item.depth)}
                    {item.title}
                  </option>
                ))}
              </select>
              <FieldError errors={errors} name="menu_id" />
            </div>

            <div className="min-w-45 flex-1">
              <label className="label" htmlFor="slug">
                Slug {editing ? '' : '(left to the server if blank)'}
              </label>
              <input
                id="slug"
                className="input"
                type="text"
                value={form.slug}
                placeholder="who-we-are"
                onChange={(event) => update('slug', event.target.value)}
              />
              <FieldError errors={errors} name="slug" />
            </div>

            <div className="w-28">
              <label className="label" htmlFor="position">
                Order
              </label>
              <input
                id="position"
                className="input"
                type="number"
                min="0"
                value={form.position}
                onChange={(event) => update('position', event.target.value)}
              />
              <FieldError errors={errors} name="position" />
            </div>
          </div>

          <div className="mb-4 flex flex-wrap items-start gap-4">
            <div className="min-w-45 flex-1">
              <label className="label" htmlFor="status">
                Status
              </label>
              <select
                id="status"
                className="input"
                value={form.status}
                onChange={(event) => update('status', event.target.value)}
              >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
              </select>
              <FieldError errors={errors} name="status" />
            </div>

            <div className="min-w-45 flex-1">
              <label className="label" htmlFor="published_at">
                Publish date
              </label>
              <input
                id="published_at"
                className="input"
                type="datetime-local"
                value={form.published_at}
                onChange={(event) => update('published_at', event.target.value)}
              />
              <p className="hint">
                Leave empty to go live as soon as it is published, or set a date in the future to
                schedule it.
              </p>
              <FieldError errors={errors} name="published_at" />
            </div>
          </div>

          <div className="mb-4">
            <label className="label" htmlFor="cover">
              Cover image
            </label>

            {existingCover && !removeCover && (
              <div className="mb-2 flex items-center gap-2">
                <img
                  className="h-14 w-21 rounded-panel border border-line object-cover"
                  src={existingCover}
                  alt=""
                />
                <button
                  type="button"
                  className="btn btn-tiny btn-danger"
                  onClick={() => setRemoveCover(true)}
                >
                  Remove
                </button>
              </div>
            )}

            {removeCover && (
              <p className="hint mb-2 flex items-center gap-2">
                The current image will be removed when you save.
                <button type="button" className="btn btn-tiny" onClick={() => setRemoveCover(false)}>
                  Keep it
                </button>
              </p>
            )}

            <input
              id="cover"
              className="input"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              onChange={(event) => setCover(event.target.files?.[0] ?? null)}
            />
            <p className="hint">jpg, png or webp, up to 4 MB.</p>
            <FieldError errors={errors} name="cover_image" />
          </div>

          <div className="mb-5">
            <span className="label">Body</span>
            <div className="editor-shell">
              <RichTextEditor value={form.body} onChange={(html) => update('body', html)} />
            </div>
            <FieldError errors={errors} name="body" />
          </div>

          <div className="flex flex-wrap items-center gap-2">
            <button type="submit" className="btn btn-primary" disabled={saving}>
              {saving ? 'Saving…' : 'Save page'}
            </button>
            <Link to="/admin/pages" className="btn">
              Cancel
            </Link>
          </div>
        </div>
      </form>
    </>
  )
}

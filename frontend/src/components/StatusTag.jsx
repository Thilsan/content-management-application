/**
 * Draft, scheduled or live. A page can be published and still be invisible to
 * readers while its publish date sits in the future, which is worth showing
 * plainly in the back end.
 */
export default function StatusTag({ page }) {
  if (page.status === 'draft') {
    return <span className="tag tag-dot tag-draft">Draft</span>
  }

  if (!page.is_visible) {
    return <span className="tag tag-dot tag-scheduled">Scheduled</span>
  }

  return <span className="tag tag-dot tag-live">Live</span>
}

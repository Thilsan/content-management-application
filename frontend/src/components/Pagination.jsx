export default function Pagination({ meta, onChange }) {
  if (!meta || meta.last_page <= 1) {
    return null
  }

  return (
    <div className="pagination">
      <button
        type="button"
        disabled={meta.current_page <= 1}
        onClick={() => onChange(meta.current_page - 1)}
      >
        Previous
      </button>

      <span className="muted">
        Page {meta.current_page} of {meta.last_page} &middot; {meta.total} in total
      </span>

      <button
        type="button"
        disabled={meta.current_page >= meta.last_page}
        onClick={() => onChange(meta.current_page + 1)}
      >
        Next
      </button>
    </div>
  )
}

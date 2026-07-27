export default function Pagination({ meta, onChange }) {
  if (!meta || meta.last_page <= 1) {
    return null
  }

  return (
    <div className="pagination">
      <span>
        Page {meta.current_page} of {meta.last_page} &middot; {meta.total} in total
      </span>

      <span className="pair">
        <button
          type="button"
          className="tiny"
          disabled={meta.current_page <= 1}
          onClick={() => onChange(meta.current_page - 1)}
        >
          Previous
        </button>

        <button
          type="button"
          className="tiny"
          disabled={meta.current_page >= meta.last_page}
          onClick={() => onChange(meta.current_page + 1)}
        >
          Next
        </button>
      </span>
    </div>
  )
}

export default function Pagination({ meta, onChange }) {
  if (!meta || meta.last_page <= 1) {
    return null
  }

  return (
    <div className="flex items-center justify-between gap-3 border-t border-line pt-4 text-[0.85rem] text-muted">
      <span>
        Page {meta.current_page} of {meta.last_page} &middot; {meta.total} in total
      </span>

      <span className="flex gap-1.5">
        <button
          type="button"
          className="btn btn-tiny"
          disabled={meta.current_page <= 1}
          onClick={() => onChange(meta.current_page - 1)}
        >
          Previous
        </button>

        <button
          type="button"
          className="btn btn-tiny"
          disabled={meta.current_page >= meta.last_page}
          onClick={() => onChange(meta.current_page + 1)}
        >
          Next
        </button>
      </span>
    </div>
  )
}

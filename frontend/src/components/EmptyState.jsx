export default function EmptyState({ title, children }) {
  return (
    <div className="px-4 py-10 text-center text-[0.9rem] text-muted">
      {title && <strong className="mb-1 block text-[0.98rem] font-semibold text-ink">{title}</strong>}
      {children}
    </div>
  )
}

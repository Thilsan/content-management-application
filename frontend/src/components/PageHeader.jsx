/**
 * Overline, title, one line of explanation, and an optional action on the right.
 * Shared so every screen in the back office opens the same way.
 */
export default function PageHeader({ eyebrow, title, lede, children }) {
  return (
    <div className="mb-6 flex flex-wrap items-start justify-between gap-5">
      <div>
        {eyebrow && <p className="eyebrow">{eyebrow}</p>}
        <h1 className="text-2xl">{title}</h1>
        {lede && <p className="lede">{lede}</p>}
      </div>

      {children && <div className="flex flex-wrap items-center gap-2">{children}</div>}
    </div>
  )
}

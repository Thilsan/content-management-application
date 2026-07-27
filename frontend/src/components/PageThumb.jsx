import { coverStyle, initialOf } from '../lib/cover'

/**
 * The page's cover if it has one, otherwise a colour generated from its slug.
 */
export default function PageThumb({ page, className = '' }) {
  if (page.cover_image_url) {
    return (
      <img
        className={`rounded-panel border border-line object-cover ${className}`}
        src={page.cover_image_url}
        alt=""
      />
    )
  }

  return (
    <span
      className={`grid place-items-center rounded-panel font-semibold text-white/90 select-none ${className}`}
      style={coverStyle(page.slug)}
      aria-hidden="true"
    >
      {initialOf(page.title)}
    </span>
  )
}

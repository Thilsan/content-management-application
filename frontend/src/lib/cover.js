/*
 * Pages without a cover image still need something to look at. Deriving a
 * colour from the slug gives every page a stable identity of its own without
 * shipping placeholder artwork, and a page keeps the same colour for good.
 */

function hueFrom(seed) {
  let hash = 0

  for (let index = 0; index < seed.length; index += 1) {
    hash = (hash * 31 + seed.charCodeAt(index)) % 360
  }

  return hash
}

export function coverStyle(seed) {
  const hue = hueFrom(seed ?? '')

  return {
    backgroundImage: `linear-gradient(135deg, hsl(${hue} 58% 60%), hsl(${(hue + 42) % 360} 62% 47%))`,
  }
}

export function initialOf(title) {
  return (title ?? '').trim().charAt(0).toUpperCase() || '·'
}

/** Roughly how long the body takes to read, at 200 words a minute. */
export function readingMinutes(html) {
  const words = String(html ?? '')
    .replace(/<[^>]+>/g, ' ')
    .trim()
    .split(/\s+/)
    .filter(Boolean).length

  return Math.max(1, Math.round(words / 200))
}

/** Published within the last fortnight. */
export function isRecent(publishedAt) {
  if (!publishedAt) {
    return false
  }

  const fortnight = 14 * 24 * 60 * 60 * 1000

  return Date.now() - new Date(publishedAt).getTime() < fortnight
}

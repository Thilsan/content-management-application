/**
 * Dates read as "27 May 2026" rather than a locale guess like 5/27/2026, which
 * is ambiguous to half the world.
 */
export function formatDate(value) {
  if (!value) {
    return null
  }

  return new Date(value).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

export function formatLongDate(value) {
  if (!value) {
    return null
  }

  return new Date(value).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

/** Every page in the tree, in the order the menu presents them. */
export function flattenPages(items) {
  return items.flatMap((item) => [
    ...item.pages.map((page) => ({ ...page, section: item.title })),
    ...flattenPages(item.children),
  ])
}

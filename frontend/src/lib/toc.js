/*
 * The body arrives from the API as a string of CKEditor HTML with no ids on its
 * headings, so there is nothing to link to. Parsing it once gives every heading
 * a stable id and hands back the list a table of contents needs.
 */

function slugify(text) {
  return (
    text
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'section'
  )
}

/**
 * @returns {{ html: string, headings: Array<{id: string, text: string, level: number}> }}
 */
export function withHeadingIds(html) {
  if (!html || typeof DOMParser === 'undefined') {
    return { html: html ?? '', headings: [] }
  }

  const document = new DOMParser().parseFromString(html, 'text/html')
  const taken = new Set()
  const headings = []

  document.body.querySelectorAll('h2, h3').forEach((element) => {
    const text = element.textContent.trim()

    if (!text) {
      return
    }

    const base = slugify(text)
    let id = base
    let suffix = 2

    while (taken.has(id)) {
      id = `${base}-${suffix}`
      suffix += 1
    }

    taken.add(id)
    element.id = id

    headings.push({ id, text, level: Number(element.tagName.charAt(1)) })
  })

  return { html: document.body.innerHTML, headings }
}

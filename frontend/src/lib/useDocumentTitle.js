import { useEffect } from 'react'

const SUFFIX = 'Content'

/**
 * Keeps the browser tab in step with the screen. Passing null leaves the title
 * alone, which avoids a flash of the wrong heading while a page is loading.
 */
export default function useDocumentTitle(title) {
  useEffect(() => {
    if (!title) {
      return
    }

    const previous = document.title
    document.title = `${title} · ${SUFFIX}`

    return () => {
      document.title = previous
    }
  }, [title])
}

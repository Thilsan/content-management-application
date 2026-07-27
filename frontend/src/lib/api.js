const BASE_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

const TOKEN_KEY = 'cms.token'

let onUnauthorized = () => {}

let locale = 'en'

/** Public reads follow whichever language the visitor picked. */
export function setApiLocale(next) {
  locale = next
}

export function setUnauthorizedHandler(handler) {
  onUnauthorized = handler
}

export function getToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token) {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token)
  } else {
    localStorage.removeItem(TOKEN_KEY)
  }
}

/**
 * Thrown for any non 2xx response. `errors` carries the field messages Laravel
 * returns for a 422 so forms can put them next to the right input.
 */
export class ApiError extends Error {
  constructor(status, payload) {
    super(payload?.message ?? `The request failed with status ${status}.`)
    this.name = 'ApiError'
    this.status = status
    this.errors = payload?.errors ?? {}
  }
}

async function request(method, path, { body, params } = {}) {
  const url = new URL(`${BASE_URL}/api${path}`)

  if (path.startsWith('/public') && locale !== 'en') {
    url.searchParams.set('lang', locale)
  }

  Object.entries(params ?? {}).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      url.searchParams.set(key, value)
    }
  })

  const headers = { Accept: 'application/json' }
  const token = getToken()

  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  let payload = body

  // FormData sets its own content type, including the multipart boundary.
  if (body && !(body instanceof FormData)) {
    headers['Content-Type'] = 'application/json'
    payload = JSON.stringify(body)
  }

  const response = await fetch(url, { method, headers, body: payload })

  if (response.status === 204) {
    return null
  }

  const data = await response.json().catch(() => null)

  if (!response.ok) {
    if (response.status === 401) {
      onUnauthorized()
    }

    throw new ApiError(response.status, data)
  }

  return data
}

export const api = {
  get: (path, params) => request('GET', path, { params }),
  post: (path, body) => request('POST', path, { body }),
  put: (path, body) => request('PUT', path, { body }),
  delete: (path) => request('DELETE', path),

  /**
   * Multipart writes always go out as POST. PHP only fills in uploaded files for
   * POST requests, so an update spoofs the method with a _method field instead.
   */
  postForm: (path, formData) => request('POST', path, { body: formData }),
}

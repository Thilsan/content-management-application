import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { api, getToken, setToken, setUnauthorizedHandler } from '../lib/api'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(Boolean(getToken()))

  const signOutLocally = useCallback(() => {
    setToken(null)
    setUser(null)
  }, [])

  // A rejected token anywhere in the app drops the session rather than leaving
  // the user staring at screens that will not load.
  useEffect(() => {
    setUnauthorizedHandler(signOutLocally)
  }, [signOutLocally])

  useEffect(() => {
    if (!getToken()) {
      return
    }

    api
      .get('/auth/me')
      .then((response) => setUser(response.data))
      .catch(() => signOutLocally())
      .finally(() => setLoading(false))
  }, [signOutLocally])

  const signIn = useCallback(async (email, password) => {
    const response = await api.post('/auth/login', { email, password })

    setToken(response.token)
    setUser(response.data)

    return response.data
  }, [])

  const signOut = useCallback(async () => {
    try {
      await api.post('/auth/logout')
    } finally {
      signOutLocally()
    }
  }, [signOutLocally])

  const value = useMemo(
    () => ({
      user,
      loading,
      signIn,
      signOut,
      can: (privilege) => Boolean(user?.privileges?.includes(privilege)),
      canAny: (privileges) => privileges.some((p) => user?.privileges?.includes(p)),
    }),
    [user, loading, signIn, signOut],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth must be used inside an AuthProvider.')
  }

  return context
}

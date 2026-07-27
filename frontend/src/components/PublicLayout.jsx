import { useEffect, useRef, useState } from 'react'
import { Link, NavLink, Outlet } from 'react-router-dom'
import { api } from '../lib/api'

const TAB =
  'flex items-center gap-1 rounded-md px-2.5 py-1.5 text-[0.9rem] font-medium whitespace-nowrap transition-colors'

/** Pages belonging to an item and everything nested under it, flattened. */
function branchPages(item) {
  return [...item.pages, ...item.children.flatMap(branchPages)]
}

function Chevron() {
  return (
    <svg
      className="size-3 text-muted"
      viewBox="0 0 12 12"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.6"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <path d="M3 4.5 6 7.5 9 4.5" />
    </svg>
  )
}

/** The nested part of a dropdown: a child section and its pages. */
function DropdownSection({ item, onNavigate, depth }) {
  return (
    <>
      <p className="overline mt-3 mb-1 px-2 first:mt-0" style={{ paddingLeft: 8 + depth * 10 }}>
        {item.title}
      </p>

      {item.pages.map((page) => (
        <Link
          key={page.id}
          to={`/pages/${page.slug}`}
          onClick={onNavigate}
          style={{ paddingLeft: 8 + depth * 10 }}
          className="block rounded-md py-1.5 pr-2 text-[0.88rem] text-ink-soft hover:bg-wash hover:text-ink"
        >
          {page.title}
        </Link>
      ))}

      {item.children.map((child) => (
        <DropdownSection key={child.id} item={child} onNavigate={onNavigate} depth={depth + 1} />
      ))}
    </>
  )
}

function NavItem({ item, open, onToggle, onNavigate }) {
  const pages = branchPages(item)

  // A section with a single page and nothing nested is just a link.
  if (pages.length === 1 && item.children.length === 0) {
    return (
      <NavLink
        to={`/pages/${pages[0].slug}`}
        onClick={onNavigate}
        className={({ isActive }) =>
          isActive
            ? `${TAB} bg-accent-wash text-accent-strong`
            : `${TAB} text-ink-soft hover:bg-wash hover:text-ink`
        }
      >
        {item.title}
      </NavLink>
    )
  }

  return (
    <div className="relative">
      <button
        type="button"
        className={`${TAB} border-0 bg-transparent shadow-none ${
          open ? 'bg-wash text-ink' : 'text-ink-soft hover:bg-wash hover:text-ink'
        }`}
        aria-expanded={open}
        aria-haspopup="true"
        onClick={onToggle}
      >
        {item.title}
        <Chevron />
      </button>

      {open && (
        <div className="absolute top-full left-0 z-30 mt-1 max-h-[70vh] w-60 overflow-y-auto rounded-card border border-line bg-surface p-2 shadow-lift">
          {item.pages.map((page) => (
            <Link
              key={page.id}
              to={`/pages/${page.slug}`}
              onClick={onNavigate}
              className="block rounded-md px-2 py-1.5 text-[0.88rem] text-ink-soft hover:bg-wash hover:text-ink"
            >
              {page.title}
            </Link>
          ))}

          {item.children.map((child) => (
            <DropdownSection key={child.id} item={child} onNavigate={onNavigate} depth={0} />
          ))}
        </div>
      )}
    </div>
  )
}

export default function PublicLayout() {
  const [menu, setMenu] = useState([])
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(true)
  const [openId, setOpenId] = useState(null)
  const navRef = useRef(null)

  useEffect(() => {
    api
      .get('/public/menu')
      .then((response) => setMenu(response.data))
      .catch((problem) => setError(problem.message))
      .finally(() => setLoading(false))
  }, [])

  // Close an open dropdown on a click elsewhere or on Escape.
  useEffect(() => {
    function handlePointer(event) {
      if (navRef.current && !navRef.current.contains(event.target)) {
        setOpenId(null)
      }
    }

    function handleKey(event) {
      if (event.key === 'Escape') {
        setOpenId(null)
      }
    }

    document.addEventListener('mousedown', handlePointer)
    document.addEventListener('keydown', handleKey)

    return () => {
      document.removeEventListener('mousedown', handlePointer)
      document.removeEventListener('keydown', handleKey)
    }
  }, [])

  return (
    <>
      <header className="sticky top-0 z-20 border-b border-line bg-surface/85 backdrop-blur-md backdrop-saturate-150">
        <div className="mx-auto flex max-w-260 flex-wrap items-center gap-x-5 gap-y-1 px-6 py-2.5">
          <Link
            to="/"
            className="flex items-center gap-2 text-[0.95rem] font-semibold tracking-tight whitespace-nowrap text-ink hover:text-ink"
          >
            <span className="grid size-6 place-items-center rounded-[7px] bg-linear-to-br from-accent to-[#6d4bf0] text-[0.72rem] font-bold text-white">
              CM
            </span>
            Content
          </Link>

          <nav ref={navRef} className="flex flex-wrap items-center gap-0.5">
            {menu.map((item) => (
              <NavItem
                key={item.id}
                item={item}
                open={openId === item.id}
                onToggle={() => setOpenId(openId === item.id ? null : item.id)}
                onNavigate={() => setOpenId(null)}
              />
            ))}
          </nav>

          <Link
            to="/admin"
            className="ml-auto text-[0.88rem] font-medium text-ink-soft hover:text-ink"
          >
            Back office
          </Link>
        </div>
      </header>

      <main className="mx-auto max-w-260 px-6 pt-10 pb-20">
        {error && <p className="notice notice-error">{error}</p>}
        <Outlet context={{ menu, loading }} />
      </main>
    </>
  )
}

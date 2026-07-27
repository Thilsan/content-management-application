import { useEffect, useRef, useState } from 'react'
import { Link, NavLink, Outlet } from 'react-router-dom'
import { api } from '../lib/api'
import PageThumb from './PageThumb.jsx'

const TAB =
  'flex items-center gap-1 rounded-md px-2.5 py-1.5 text-[0.9rem] font-medium whitespace-nowrap transition-colors'

/** Pages belonging to an item and everything nested under it, flattened. */
function branchPages(item) {
  return [...item.pages, ...item.children.flatMap(branchPages)]
}

function Chevron({ open }) {
  return (
    <svg
      className={`size-3 text-muted transition-transform duration-150 ${open ? 'rotate-180' : ''}`}
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

function MenuLink({ page, onNavigate }) {
  return (
    <NavLink
      to={`/pages/${page.slug}`}
      role="menuitem"
      data-menu-item
      onClick={onNavigate}
      className={({ isActive }) =>
        isActive
          ? 'menu-item bg-accent-wash font-medium text-accent-strong'
          : 'menu-item text-ink-soft hover:bg-wash hover:text-ink'
      }
    >
      <PageThumb page={page} className="size-6 flex-none text-[0.6rem]" />
      <span className="truncate">{page.title}</span>
    </NavLink>
  )
}

/** A nested section inside the dropdown, with its own pages beneath it. */
function MenuGroup({ item, onNavigate }) {
  return (
    <>
      <p className="overline px-2 pt-3 pb-1">{item.title}</p>

      {item.pages.map((page) => (
        <MenuLink key={page.id} page={page} onNavigate={onNavigate} />
      ))}

      {item.children.map((child) => (
        <MenuGroup key={child.id} item={child} onNavigate={onNavigate} />
      ))}
    </>
  )
}

function NavItem({ item, open, onOpen, onClose, onToggle }) {
  const pages = branchPages(item)
  const buttonRef = useRef(null)
  const panelRef = useRef(null)

  // A section holding a single page and nothing nested is just a link. Wrapping
  // one item in a dropdown would be a click for no reason.
  if (pages.length === 1 && item.children.length === 0) {
    return (
      <NavLink
        to={`/pages/${pages[0].slug}`}
        onClick={onClose}
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

  function focusItem(index) {
    const items = [...(panelRef.current?.querySelectorAll('[data-menu-item]') ?? [])]

    if (items.length === 0) {
      return
    }

    const wrapped = (index + items.length) % items.length
    items[wrapped].focus()
  }

  function handleButtonKey(event) {
    if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
      event.preventDefault()
      onOpen()
      // The panel mounts on the next paint, so wait before reaching into it.
      requestAnimationFrame(() => focusItem(0))
    }
  }

  function handlePanelKey(event) {
    const items = [...(panelRef.current?.querySelectorAll('[data-menu-item]') ?? [])]
    const current = items.indexOf(document.activeElement)

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault()
        focusItem(current + 1)
        break
      case 'ArrowUp':
        event.preventDefault()
        focusItem(current - 1)
        break
      case 'Home':
        event.preventDefault()
        focusItem(0)
        break
      case 'End':
        event.preventDefault()
        focusItem(items.length - 1)
        break
      case 'Escape':
        event.preventDefault()
        onClose()
        buttonRef.current?.focus()
        break
      case 'Tab':
        onClose()
        break
      default:
        break
    }
  }

  return (
    <div className="relative">
      <button
        ref={buttonRef}
        type="button"
        className={`${TAB} border-0 bg-transparent shadow-none ${
          open ? 'bg-wash text-ink' : 'text-ink-soft hover:bg-wash hover:text-ink'
        }`}
        aria-expanded={open}
        aria-haspopup="menu"
        onClick={onToggle}
        onKeyDown={handleButtonKey}
      >
        {item.title}
        <span className="text-[0.72rem] font-normal text-muted">{pages.length}</span>
        <Chevron open={open} />
      </button>

      {open && (
        <div ref={panelRef} role="menu" onKeyDown={handlePanelKey} className="menu-panel">
          {item.pages.map((page) => (
            <MenuLink key={page.id} page={page} onNavigate={onClose} />
          ))}

          {item.children.map((child) => (
            <MenuGroup key={child.id} item={child} onNavigate={onClose} />
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
                onOpen={() => setOpenId(item.id)}
                onClose={() => setOpenId(null)}
                onToggle={() => setOpenId(openId === item.id ? null : item.id)}
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

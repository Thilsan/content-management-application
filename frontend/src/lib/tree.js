/*
 * The menu editor works on a flat list of rows carrying a depth, which is what a
 * vertical drag and drop list can sort. These helpers convert between that flat
 * shape and the nested tree the API speaks.
 */

export function flattenTree(nodes, depth = 0, rows = []) {
  nodes.forEach((node) => {
    rows.push({
      id: node.id,
      title: node.title,
      slug: node.slug,
      isActive: node.is_active,
      pagesCount: node.pages_count ?? 0,
      depth,
    })

    flattenTree(node.children ?? [], depth + 1, rows)
  })

  return rows
}

/**
 * A row may sit at most one level deeper than the row above it, and the first
 * row is always at the root. Clamping runs top to bottom so each decision is
 * made against the depth the previous row ended up with.
 */
export function normaliseDepths(rows) {
  const result = []

  rows.forEach((row, index) => {
    const ceiling = index === 0 ? 0 : result[index - 1].depth + 1

    result.push({ ...row, depth: Math.max(0, Math.min(row.depth, ceiling)) })
  })

  return result
}

/** Index just past the last descendant of the row at `index`. */
export function branchEnd(rows, index) {
  const { depth } = rows[index]
  let end = index + 1

  while (end < rows.length && rows[end].depth > depth) {
    end += 1
  }

  return end
}

/**
 * Move a row and everything nested under it. Dragging a parent has to carry its
 * children along, otherwise they would silently re-parent to whatever ends up
 * above them.
 */
export function moveBranch(rows, activeId, overId) {
  const from = rows.findIndex((row) => row.id === activeId)
  const over = rows.findIndex((row) => row.id === overId)

  if (from === -1 || over === -1) {
    return rows
  }

  const end = branchEnd(rows, from)

  // Dropping a branch inside itself would detach it from its own parent.
  if (over >= from && over < end) {
    return rows
  }

  const branch = rows.slice(from, end)
  const rest = rows.filter((row, index) => index < from || index >= end)
  const anchor = rest.findIndex((row) => row.id === overId)
  const insertAt = over > from ? anchor + 1 : anchor

  rest.splice(insertAt, 0, ...branch)

  return normaliseDepths(rest)
}

/** Indent (delta 1) or outdent (delta -1) a row together with its descendants. */
export function shiftBranch(rows, id, delta) {
  const index = rows.findIndex((row) => row.id === id)

  if (index === -1) {
    return rows
  }

  const end = branchEnd(rows, index)
  const ceiling = index === 0 ? 0 : rows[index - 1].depth + 1
  const target = Math.max(0, Math.min(rows[index].depth + delta, ceiling))
  const shift = target - rows[index].depth

  if (shift === 0) {
    return rows
  }

  return normaliseDepths(
    rows.map((row, position) =>
      position >= index && position < end ? { ...row, depth: row.depth + shift } : row,
    ),
  )
}

/** The payload POST /api/menus/reorder expects: every node, its parent and its position. */
export function toReorderPayload(rows) {
  const ancestors = []
  const nextPosition = new Map()

  return rows.map((row) => {
    const parentId = row.depth === 0 ? null : (ancestors[row.depth - 1] ?? null)
    const key = parentId ?? 'root'
    const position = nextPosition.get(key) ?? 0

    nextPosition.set(key, position + 1)

    ancestors[row.depth] = row.id
    ancestors.length = row.depth + 1

    return { id: row.id, parent_id: parentId, position }
  })
}

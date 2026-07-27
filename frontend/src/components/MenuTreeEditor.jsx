import { DndContext, KeyboardSensor, PointerSensor, closestCenter, useSensor, useSensors } from '@dnd-kit/core'
import {
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import { moveBranch, shiftBranch } from '../lib/tree'

const INDENT_PX = 26

function Row({ row, sortable, onIndent, onOutdent, onEdit, onDelete }) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: row.id,
    disabled: !sortable,
  })

  const classes = ['tree-row']

  if (isDragging) {
    classes.push('dragging')
  }

  if (!row.isActive) {
    classes.push('off')
  }

  return (
    <div
      ref={setNodeRef}
      className={classes.join(' ')}
      style={{
        transform: CSS.Transform.toString(transform),
        transition,
        marginLeft: row.depth * INDENT_PX,
      }}
    >
      {sortable && (
        <span className="handle" title="Drag to reorder" {...attributes} {...listeners}>
          ⠿
        </span>
      )}

      <span className="title">{row.title}</span>

      <span className="muted">
        {row.pagesCount} {row.pagesCount === 1 ? 'page' : 'pages'}
      </span>

      {!row.isActive && <span className="tag">Hidden</span>}

      {sortable && (
        <span className="actions">
          <button type="button" className="tiny" title="Outdent" onClick={() => onOutdent(row.id)}>
            &larr;
          </button>
          <button type="button" className="tiny" title="Indent" onClick={() => onIndent(row.id)}>
            &rarr;
          </button>
          {onEdit && (
            <button type="button" className="tiny" onClick={() => onEdit(row)}>
              Edit
            </button>
          )}
          {onDelete && (
            <button type="button" className="tiny danger" onClick={() => onDelete(row)}>
              Delete
            </button>
          )}
        </span>
      )}
    </div>
  )
}

export default function MenuTreeEditor({ rows, onChange, sortable, onEdit, onDelete }) {
  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
  )

  function handleDragEnd({ active, over }) {
    if (!over || active.id === over.id) {
      return
    }

    onChange(moveBranch(rows, active.id, over.id))
  }

  return (
    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
      <SortableContext items={rows.map((row) => row.id)} strategy={verticalListSortingStrategy}>
        {rows.map((row) => (
          <Row
            key={row.id}
            row={row}
            sortable={sortable}
            onIndent={(id) => onChange(shiftBranch(rows, id, 1))}
            onOutdent={(id) => onChange(shiftBranch(rows, id, -1))}
            onEdit={onEdit}
            onDelete={onDelete}
          />
        ))}
      </SortableContext>
    </DndContext>
  )
}

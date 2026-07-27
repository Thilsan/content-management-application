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

  return (
    <div
      ref={setNodeRef}
      className={`mb-1.5 flex items-center gap-2.5 rounded-panel border border-line bg-surface px-3 py-2 shadow-card ${
        isDragging ? 'opacity-45 shadow-lift' : ''
      }`}
      style={{
        transform: CSS.Transform.toString(transform),
        transition,
        marginLeft: row.depth * INDENT_PX,
      }}
    >
      {sortable && (
        <span
          className="cursor-grab touch-none p-0.5 leading-none text-line-strong select-none hover:text-muted active:cursor-grabbing"
          title="Drag to reorder"
          {...attributes}
          {...listeners}
        >
          ⠿
        </span>
      )}

      <span
        className={`min-w-0 flex-1 truncate text-[0.92rem] ${
          row.isActive ? 'font-medium' : 'text-muted'
        }`}
      >
        {row.title}
      </span>

      <span className="text-[0.78rem] whitespace-nowrap text-muted">
        {row.pagesCount} {row.pagesCount === 1 ? 'page' : 'pages'}
      </span>

      {!row.isActive && <span className="tag">Hidden</span>}

      {sortable && (
        <span className="flex flex-wrap items-center gap-1.5">
          <button
            type="button"
            className="btn btn-tiny"
            title="Outdent"
            onClick={() => onOutdent(row.id)}
          >
            &larr;
          </button>
          <button
            type="button"
            className="btn btn-tiny"
            title="Indent"
            onClick={() => onIndent(row.id)}
          >
            &rarr;
          </button>
          {onEdit && (
            <button type="button" className="btn btn-tiny" onClick={() => onEdit(row)}>
              Edit
            </button>
          )}
          {onDelete && (
            <button
              type="button"
              className="btn btn-tiny btn-danger"
              onClick={() => onDelete(row)}
            >
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

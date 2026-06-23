'use client'

import { useState } from 'react'
import {
  flexRender,
  getCoreRowModel,
  getSortedRowModel,
  getPaginationRowModel,
  useReactTable,
  type ColumnDef,
  type SortingState,
  type RowSelectionState,
} from '@tanstack/react-table'
import { ChevronUp, ChevronDown, ChevronsUpDown, ChevronLeft, ChevronRight } from 'lucide-react'
import { cn } from '@/lib/utils'

interface DataTableProps<TData> {
  data: TData[]
  columns: ColumnDef<TData, unknown>[]
  pageCount?: number
  pageIndex?: number
  pageSize?: number
  onPageChange?: (page: number) => void
  onRowClick?: (row: TData) => void
  rowSelection?: RowSelectionState
  onRowSelectionChange?: (selection: RowSelectionState) => void
  loading?: boolean
  stickyHeader?: boolean
  className?: string
}

export function DataTable<TData>({
  data,
  columns,
  pageCount,
  pageIndex = 0,
  pageSize = 20,
  onPageChange,
  onRowClick,
  rowSelection,
  onRowSelectionChange,
  loading = false,
  stickyHeader = false,
  className,
}: DataTableProps<TData>) {
  const [sorting, setSorting] = useState<SortingState>([])
  const [internalSelection, setInternalSelection] = useState<RowSelectionState>({})

  // When the data is server-paginated we only hold the current page, so
  // client-side sorting would silently reorder just the visible rows (a lie).
  // Until a server sort param is wired, disable header sorting on those tables.
  const serverPaginated = Boolean(pageCount)

  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    enableSorting: !serverPaginated,
    onSortingChange: setSorting,
    onRowSelectionChange: (updater) => {
      const fn = onRowSelectionChange ?? setInternalSelection
      if (typeof updater === 'function') {
        fn(updater(rowSelection ?? internalSelection))
      } else {
        fn(updater)
      }
    },
    state: {
      sorting,
      rowSelection: rowSelection ?? internalSelection,
      pagination: { pageIndex, pageSize },
    },
    manualPagination: Boolean(pageCount),
    pageCount: pageCount ?? -1,
  })

  return (
    <div className={cn('flex flex-col', className)}>
      <div className="overflow-x-auto rounded-xl border border-[var(--color-border)]">
        <table className="w-full text-sm border-collapse">
          <thead className={cn(stickyHeader && 'sticky top-0 z-10')}>
            {table.getHeaderGroups().map((hg) => (
              <tr key={hg.id} className="border-b border-[var(--color-border)] bg-[var(--color-surface-alt)]">
                {hg.headers.map((header) => {
                  const sortable = header.column.getCanSort()
                  const sorted = header.column.getIsSorted()
                  return (
                    <th
                      key={header.id}
                      scope="col"
                      aria-sort={
                        sortable
                          ? sorted === 'asc'
                            ? 'ascending'
                            : sorted === 'desc'
                              ? 'descending'
                              : 'none'
                          : undefined
                      }
                      className="px-4 py-3 text-left text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide whitespace-nowrap"
                      style={{ width: header.getSize() !== 150 ? header.getSize() : undefined }}
                    >
                      {header.isPlaceholder ? null : sortable ? (
                        <button
                          type="button"
                          onClick={header.column.getToggleSortingHandler()}
                          className="flex items-center gap-1.5 select-none uppercase tracking-wide hover:text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:text-[var(--color-text-primary)] focus-visible:underline"
                        >
                          {flexRender(header.column.columnDef.header, header.getContext())}
                          <span className="text-[var(--color-text-muted)]">
                            {sorted === 'asc' ? (
                              <ChevronUp size={12} strokeWidth={2} />
                            ) : sorted === 'desc' ? (
                              <ChevronDown size={12} strokeWidth={2} />
                            ) : (
                              <ChevronsUpDown size={12} strokeWidth={1.5} className="opacity-40" />
                            )}
                          </span>
                        </button>
                      ) : (
                        <div className="flex items-center gap-1.5">
                          {flexRender(header.column.columnDef.header, header.getContext())}
                        </div>
                      )}
                    </th>
                  )
                })}
              </tr>
            ))}
          </thead>

          <tbody className="bg-white divide-y divide-[var(--color-border)]">
            {loading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i}>
                  {columns.map((_, ci) => (
                    <td key={ci} className="px-4 py-3">
                      <div className="h-4 rounded bg-[var(--color-border)] animate-pulse" style={{ width: `${60 + Math.random() * 30}%` }} />
                    </td>
                  ))}
                </tr>
              ))
            ) : table.getRowModel().rows.length === 0 ? (
              <tr>
                <td
                  colSpan={columns.length}
                  className="py-16 text-center text-sm text-[var(--color-text-muted)]"
                >
                  No results found
                </td>
              </tr>
            ) : (
              table.getRowModel().rows.map((row) => (
                <tr
                  key={row.id}
                  onClick={() => onRowClick?.(row.original)}
                  className={cn(
                    'hover:bg-[var(--color-surface-alt)] transition-colors',
                    onRowClick && 'cursor-pointer',
                    row.getIsSelected() && 'bg-[var(--color-brand-50)]',
                  )}
                >
                  {row.getVisibleCells().map((cell) => (
                    <td
                      key={cell.id}
                      className="px-4 text-[var(--color-text-primary)]"
                      style={{ height: 'var(--row-h, 52px)', paddingTop: 0, paddingBottom: 0 }}
                    >
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {(pageCount ?? 0) > 1 && (
        <div className="flex items-center justify-between px-2 py-3">
          <span className="text-xs text-[var(--color-text-muted)]">
            Page {pageIndex + 1} of {pageCount}
          </span>
          <div className="flex items-center gap-1">
            <button
              onClick={() => onPageChange?.(pageIndex - 1)}
              disabled={pageIndex === 0}
              className="flex items-center justify-center w-8 h-8 rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            >
              <ChevronLeft size={14} strokeWidth={1.75} />
            </button>
            <button
              onClick={() => onPageChange?.(pageIndex + 1)}
              disabled={pageIndex + 1 >= (pageCount ?? 1)}
              className="flex items-center justify-center w-8 h-8 rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            >
              <ChevronRight size={14} strokeWidth={1.75} />
            </button>
          </div>
        </div>
      )}
    </div>
  )
}

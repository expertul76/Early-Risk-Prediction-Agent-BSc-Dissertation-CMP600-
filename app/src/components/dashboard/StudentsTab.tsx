import { useState, useMemo } from 'react'
import { Search, ChevronUp, ChevronDown, X } from 'lucide-react'
import StudentDetailPanel from './StudentDetailPanel'
import type { Student } from '../../context/types'

type SortKey = 'name' | 'course' | 'attendance' | 'assessment' | 'composite'
type SortDir = 'asc' | 'desc'

interface Props {
  students: Student[]
}

export default function StudentsTab({ students }: Props) {
  const [search, setSearch] = useState('')
  const [sortKey, setSortKey] = useState<SortKey>('composite')
  const [sortDir, setSortDir] = useState<SortDir>('asc')
  const [selectedStudent, setSelectedStudent] = useState<Student | null>(null)

  const filtered = useMemo(() => {
    let result = students

    if (search) {
      const q = search.toLowerCase()
      result = result.filter(s =>
        `${s.firstName} ${s.lastName}`.toLowerCase().includes(q) ||
        s.email.toLowerCase().includes(q) ||
        s.id.toLowerCase().includes(q)
      )
    }

    result = [...result].sort((a, b) => {
      let aVal: number | string = 0
      let bVal: number | string = 0

      switch (sortKey) {
        case 'name':
          aVal = `${a.firstName} ${a.lastName}`
          bVal = `${b.firstName} ${b.lastName}`
          break
        case 'course':
          aVal = a.courseId
          bVal = b.courseId
          break
        case 'attendance':
          aVal = a.riskScore?.factors.attendance ?? 0
          bVal = b.riskScore?.factors.attendance ?? 0
          break
        case 'assessment':
          aVal = a.riskScore?.factors.assessment ?? 0
          bVal = b.riskScore?.factors.assessment ?? 0
          break
        case 'composite':
          aVal = a.riskScore?.composite ?? 0
          bVal = b.riskScore?.composite ?? 0
          break
      }

      if (typeof aVal === 'string') {
        return sortDir === 'asc' ? aVal.localeCompare(bVal as string) : (bVal as string).localeCompare(aVal)
      }
      return sortDir === 'asc' ? aVal - (bVal as number) : (bVal as number) - aVal
    })

    return result
  }, [students, search, sortKey, sortDir])

  const handleSort = (key: SortKey) => {
    if (sortKey === key) {
      setSortDir(d => d === 'asc' ? 'desc' : 'asc')
    } else {
      setSortKey(key)
      setSortDir('asc')
    }
  }

  const SortIcon = ({ col }: { col: SortKey }) => {
    if (sortKey !== col) return null
    return sortDir === 'asc' ? <ChevronUp className="w-3 h-3" /> : <ChevronDown className="w-3 h-3" />
  }

  return (
    <div className="relative">
      {/* Search */}
      <div className="flex items-center gap-3 mb-4">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
          <input
            type="text"
            value={search}
            onChange={e => setSearch(e.target.value)}
            placeholder="Search by name, email, or ID..."
            className="w-full pl-9 pr-3 py-2 bg-bg-tertiary border border-border rounded-xl text-sm text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent/50"
          />
        </div>
        <span className="text-xs text-text-muted">{filtered.length} students</span>
      </div>

      {/* Table */}
      <div className="glass-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-text-muted border-b border-border bg-bg-secondary/50">
                {([
                  ['name', 'Student'],
                  ['course', 'Course'],
                  ['attendance', 'Attendance'],
                  ['assessment', 'Assessment'],
                  ['composite', 'Risk Score'],
                ] as [SortKey, string][]).map(([key, label]) => (
                  <th
                    key={key}
                    onClick={() => handleSort(key)}
                    className="px-4 py-3 font-medium cursor-pointer hover:text-text-primary transition-colors"
                  >
                    <span className="flex items-center gap-1">
                      {label}
                      <SortIcon col={key} />
                    </span>
                  </th>
                ))}
                <th className="px-4 py-3 font-medium">Risk</th>
                <th className="px-4 py-3 font-medium">Review</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map(s => (
                <tr
                  key={s.id}
                  onClick={() => setSelectedStudent(s)}
                  className="border-b border-border/30 hover:bg-bg-card cursor-pointer transition-colors"
                >
                  <td className="px-4 py-3">
                    <p className="text-text-primary font-medium">{s.firstName} {s.lastName}</p>
                    <p className="text-text-muted text-xs">{s.email}</p>
                  </td>
                  <td className="px-4 py-3 font-mono text-xs text-text-secondary">{s.courseId}</td>
                  <td className="px-4 py-3 text-text-secondary">{s.riskScore?.factors.attendance ?? '-'}</td>
                  <td className="px-4 py-3 text-text-secondary">{s.riskScore?.factors.assessment ?? '-'}</td>
                  <td className="px-4 py-3 font-medium text-text-primary">{s.riskScore?.composite ?? '-'}</td>
                  <td className="px-4 py-3">
                    <span className={`badge-${s.riskScore?.classification || 'medium'} text-xs px-2 py-0.5 rounded-full font-medium`}>
                      {s.riskScore?.classification?.toUpperCase() || 'N/A'}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    {s.teacherReview?.agrees !== null && s.teacherReview?.agrees !== undefined ? (
                      <span className={`text-xs ${s.teacherReview.agrees ? 'text-risk-low' : 'text-risk-high'}`}>
                        {s.teacherReview.agrees ? 'Agreed' : 'Disagreed'}
                      </span>
                    ) : (
                      <span className="text-xs text-text-muted">-</span>
                    )}
                  </td>
                </tr>
              ))}
              {filtered.length === 0 && (
                <tr>
                  <td colSpan={7} className="px-4 py-8 text-center text-sm text-text-muted">
                    No students match the current filters
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Student Detail Panel */}
      {selectedStudent && (
        <div className="fixed inset-0 z-30 flex justify-end" onClick={() => setSelectedStudent(null)}>
          <div className="absolute inset-0 bg-black/40" />
          <div
            className="relative w-full max-w-lg bg-bg-secondary border-l border-border overflow-y-auto"
            onClick={e => e.stopPropagation()}
          >
            <button
              onClick={() => setSelectedStudent(null)}
              className="absolute top-4 right-4 p-1 text-text-muted hover:text-text-primary z-10"
            >
              <X className="w-5 h-5" />
            </button>
            <StudentDetailPanel
              student={selectedStudent}
              onClose={() => setSelectedStudent(null)}
            />
          </div>
        </div>
      )}
    </div>
  )
}

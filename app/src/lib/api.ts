import type { Student } from '../context/types'

const API = '/api'

async function request<T>(url: string, options?: RequestInit): Promise<T> {
  const res = await fetch(`${API}${url}`, {
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    ...options,
  })
  const data = await res.json()
  if (!res.ok) {
    throw new Error(data.error || `Request failed: ${res.status}`)
  }
  return data as T
}

// Auth
export interface AuthUser {
  id: number
  email: string
  name: string
}

export async function apiGetMe(): Promise<AuthUser | null> {
  const data = await request<{ user: AuthUser | null }>('/me.php')
  return data.user
}

export async function apiLogin(email: string, password: string): Promise<AuthUser> {
  const data = await request<{ user: AuthUser }>('/login.php', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  })
  return data.user
}

export async function apiRegister(name: string, email: string, password: string): Promise<AuthUser> {
  const data = await request<{ user: AuthUser }>('/register.php', {
    method: 'POST',
    body: JSON.stringify({ name, email, password }),
  })
  return data.user
}

export async function apiLogout(): Promise<void> {
  await request('/logout.php', { method: 'POST' })
}

// Reports
export interface ReportCourse {
  course_code: string
  course_length: number
  weeks_loaded: number
  last_upload: string
}

export interface ReportSummary {
  id: number
  name: string
  created_at: string
  updated_at: string
  course_count: number
  student_count: number
  high_risk_count: number
  courses: ReportCourse[]
}

export interface FullReport {
  id: number
  name: string
  created_at: string
  updated_at: string
  courses: ReportCourse[]
  students: Student[]
}

export async function apiListReports(): Promise<ReportSummary[]> {
  const data = await request<{ reports: ReportSummary[] }>('/reports.php')
  return data.reports
}

export async function apiLoadReport(id: number): Promise<FullReport> {
  const data = await request<{ report: FullReport }>(`/reports.php?id=${id}`)
  return data.report
}

export async function apiSaveReport(name: string, students: Student[]): Promise<{ reportId: number }> {
  return request('/reports.php', {
    method: 'POST',
    body: JSON.stringify({ name, students: stripStudentsForSave(students) }),
  })
}

export async function apiUpdateReport(id: number, students: Student[]): Promise<{ matched_courses: string[]; new_courses: string[] }> {
  return request(`/reports.php?id=${id}`, {
    method: 'PUT',
    body: JSON.stringify({ students: stripStudentsForSave(students) }),
  })
}

export async function apiDeleteReport(id: number): Promise<void> {
  await request(`/reports.php?id=${id}`, { method: 'DELETE' })
}

function stripStudentsForSave(students: Student[]): Record<string, unknown>[] {
  return students.map(s => ({
    id: s.id,
    firstName: s.firstName,
    lastName: s.lastName,
    email: s.email,
    courseId: s.courseId,
    courseLength: s.courseLength,
    attendance: s.attendance,
    assessments: s.assessments,
    existingMetrics: s.existingMetrics,
    riskScore: s.riskScore,
    sentimentAnalysis: s.sentimentAnalysis,
    teacherReview: s.teacherReview,
  }))
}

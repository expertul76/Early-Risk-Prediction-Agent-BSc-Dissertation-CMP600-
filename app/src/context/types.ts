export type RiskLevel = 'low' | 'medium' | 'high';
export type AttendanceStatus = 'present' | 'late' | 'absent';
export type AssessmentStatus = 'completed' | 'late' | 'not_completed' | 'not_given';
export type AssessmentType = 'formative' | 'summative';
export type SchemaType = 'modern' | 'legacy' | 'unknown';
export type AppStep = 'reports' | 'upload' | 'mapping' | 'dashboard';

export interface WeekRecord {
  week: number;
  status: AttendanceStatus;
  engagementNote?: string;
}

export interface Assessment {
  name: string;
  type: AssessmentType;
  uploaded: boolean;
  status: AssessmentStatus;
  grade?: number | null;
  feedback?: string;
}

export interface ExistingMetrics {
  overallAssessmentPct?: number;
  assessmentPerformance?: string;
  engagementComment?: string;
  attendancePct?: number;
  riskRank?: string;
  dropoutProbability?: number;
  engagementTrend?: string;
  tutorNotes?: string;
}

export interface RiskFactors {
  attendance: number;
  assessment: number;
  engagement: number;
  sentiment: number | null;
}

export interface RiskScore {
  composite: number;
  classification: RiskLevel;
  factors: RiskFactors;
  explanations: string[];
}

export interface SentimentResult {
  score: number;
  summary: string;
}

export interface TeacherReview {
  agrees: boolean | null; // null = not reviewed
  notes: string;
  reviewedAt?: string;
}

export interface Student {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  courseId: string;
  courseLength: number;
  attendance: WeekRecord[];
  assessments: Assessment[];
  existingMetrics?: ExistingMetrics;
  riskScore?: RiskScore;
  sentimentAnalysis?: SentimentResult;
  teacherReview?: TeacherReview;
}

export interface ColumnMapping {
  field: string;
  label: string;
  csvColumn: string | null;
  required: boolean;
}

export interface DetectedSchema {
  type: SchemaType;
  delimiter: string;
  columnCount: number;
  rowCount: number;
  headers: string[];
  mappings: ColumnMapping[];
}

export interface AppSettings {
  openaiApiKey: string;
  sentimentEnabled: boolean;
}

// App state
export interface AppState {
  step: AppStep;
  rawData: Record<string, string>[];
  detectedSchema: DetectedSchema | null;
  students: Student[];
  settings: AppSettings;
  isProcessing: boolean;
  error: string | null;
  currentReportId: number | null;
  currentReportName: string | null;
  isSaved: boolean;
}

export type AppAction =
  | { type: 'SET_STEP'; payload: AppStep }
  | { type: 'SET_RAW_DATA'; payload: { data: Record<string, string>[]; schema: DetectedSchema } }
  | { type: 'SET_STUDENTS'; payload: Student[] }
  | { type: 'UPDATE_STUDENT'; payload: { id: string; updates: Partial<Student> } }
  | { type: 'SET_SETTINGS'; payload: Partial<AppSettings> }
  | { type: 'SET_PROCESSING'; payload: boolean }
  | { type: 'SET_ERROR'; payload: string | null }
  | { type: 'LOAD_REPORT'; payload: { id: number; name: string; students: Student[] } }
  | { type: 'SET_SAVED'; payload: { id: number; name: string } }
  | { type: 'RESET' };

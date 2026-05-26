import Papa from 'papaparse';
import type { DetectedSchema, SchemaType, ColumnMapping } from '../context/types';

export interface ParseResult {
  data: Record<string, string>[];
  schema: DetectedSchema;
}

export function parseCSV(file: File): Promise<ParseResult> {
  return new Promise((resolve, reject) => {
    Papa.parse(file, {
      header: true,
      skipEmptyLines: true,
      encoding: 'UTF-8',
      complete(results) {
        if (results.errors.length > 0 && results.data.length === 0) {
          reject(new Error(`CSV parse error: ${results.errors[0].message}`));
          return;
        }
        const data = results.data as Record<string, string>[];
        const headers = results.meta.fields || [];
        const delimiter = results.meta.delimiter;
        const schemaType = detectSchemaType(headers);
        const mappings = generateMappings(schemaType, headers);

        resolve({
          data,
          schema: {
            type: schemaType,
            delimiter,
            columnCount: headers.length,
            rowCount: data.length,
            headers,
            mappings,
          },
        });
      },
      error(err) {
        reject(new Error(`Failed to parse CSV: ${err.message}`));
      },
    });
  });
}

function detectSchemaType(headers: string[]): SchemaType {
  const lower = headers.map(h => h.trim().toLowerCase());

  // Modern format: has Student ID, Course ID, Course Length
  if (lower.includes('student id') && lower.includes('course id')) {
    return 'modern';
  }

  // Legacy format: has Semester references or Overall Assessment %
  if (lower.some(h => h.includes('semester'))) {
    return 'legacy';
  }

  // Legacy fallback: has pre-calculated metrics
  if (lower.includes('overall assessment %') || lower.includes('attendance %') || lower.includes('risk rank')) {
    return 'legacy';
  }

  return 'unknown';
}

function generateMappings(schemaType: SchemaType, headers: string[]): ColumnMapping[] {
  const mappings: ColumnMapping[] = [];
  const lower = headers.map(h => h.trim().toLowerCase());

  // Core identity fields
  const identityFields = [
    { field: 'firstName', label: 'First Name', patterns: ['first name', 'firstname'] },
    { field: 'lastName', label: 'Last Name', patterns: ['last name', 'lastname', 'surname'] },
    { field: 'email', label: 'Email', patterns: ['email', 'email address'] },
    { field: 'courseId', label: 'Course', patterns: ['course', 'course id', 'course name'] },
  ];

  for (const f of identityFields) {
    const idx = lower.findIndex(h => f.patterns.includes(h));
    mappings.push({
      field: f.field,
      label: f.label,
      csvColumn: idx >= 0 ? headers[idx] : null,
      required: true,
    });
  }

  // Student ID
  const idIdx = lower.findIndex(h => h === 'student id');
  mappings.push({
    field: 'studentId',
    label: 'Student ID',
    csvColumn: idIdx >= 0 ? headers[idIdx] : null,
    required: false,
  });

  // Course length
  const lengthIdx = lower.findIndex(h => h.includes('course length'));
  mappings.push({
    field: 'courseLength',
    label: 'Course Length (weeks)',
    csvColumn: lengthIdx >= 0 ? headers[lengthIdx] : null,
    required: false,
  });

  // Week columns
  const weekHeaders = headers.filter((_, i) => {
    const h = lower[i];
    return h.match(/^week\s*\d+$/) || h.match(/semester\s*\d+\s*-\s*week\s*\d+$/);
  });

  if (weekHeaders.length > 0) {
    mappings.push({
      field: 'weekColumns',
      label: `Attendance Weeks (${weekHeaders.length} found)`,
      csvColumn: weekHeaders.join('|'),
      required: true,
    });
  }

  // Engagement notes columns (modern format - alternating with weeks)
  if (schemaType === 'modern') {
    const noteHeaders = headers.filter((_, i) => {
      const h = lower[i];
      return h.match(/^engagement notes/i) || h === 'engagement notes';
    });
    if (noteHeaders.length > 0) {
      mappings.push({
        field: 'engagementNoteColumns',
        label: `Engagement Notes (${noteHeaders.length} found)`,
        csvColumn: noteHeaders.join('|'),
        required: false,
      });
    }
  }

  // Assessment columns
  const assessmentHeaders = headers.filter((_, i) => {
    const h = lower[i];
    return h.includes('assessment') && !h.includes('overall') && !h.includes('performance');
  });
  if (assessmentHeaders.length > 0) {
    mappings.push({
      field: 'assessmentColumns',
      label: `Assessment Columns (${assessmentHeaders.length} found)`,
      csvColumn: assessmentHeaders.join('|'),
      required: true,
    });
  }

  // Pre-calculated metrics (legacy)
  const metricFields = [
    { field: 'overallAssessmentPct', label: 'Overall Assessment %', patterns: ['overall assessment %'] },
    { field: 'attendancePct', label: 'Attendance %', patterns: ['attendance %'] },
    { field: 'assessmentPerformance', label: 'Assessment Performance', patterns: ['assessment performance'] },
    { field: 'engagementComment', label: 'Engagement Comment', patterns: ['engagement comment'] },
    { field: 'riskRank', label: 'Risk Rank', patterns: ['risk rank'] },
    { field: 'engagementTrend', label: 'Engagement Trend', patterns: ['engagement trend'] },
    { field: 'dropoutProbability', label: 'Dropout Probability', patterns: ['dropout probability'] },
    { field: 'tutorNotes', label: 'Tutor Notes', patterns: ['tutor intervention notes', 'tutor notes'] },
    { field: 'detailedFeedback', label: 'Detailed Feedback', patterns: ['detailed assessment feedback'] },
  ];

  for (const f of metricFields) {
    const idx = lower.findIndex(h => f.patterns.includes(h));
    if (idx >= 0) {
      mappings.push({
        field: f.field,
        label: f.label,
        csvColumn: headers[idx],
        required: false,
      });
    }
  }

  return mappings;
}

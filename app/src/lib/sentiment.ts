import type { Student, SentimentResult } from '../context/types'

const BATCH_SIZE = 10
const PROXY_URL = '/api/sentiment.php'

/**
 * Analyse sentiment via server-side Gemini proxy (free, no API key needed from user).
 */
export async function analyseSentimentBatch(
  students: Student[],
): Promise<Student[]> {
  const studentsWithNotes = students.filter(s =>
    s.attendance.some(w => w.engagementNote) ||
    s.existingMetrics?.tutorNotes ||
    s.existingMetrics?.engagementComment
  )

  if (studentsWithNotes.length === 0) return students

  const results = new Map<string, SentimentResult>()

  for (let i = 0; i < studentsWithNotes.length; i += BATCH_SIZE) {
    const batch = studentsWithNotes.slice(i, i + BATCH_SIZE)

    try {
      const batchResults = await analyseBatch(batch)
      for (const [id, result] of batchResults) {
        results.set(id, result)
      }
    } catch {
      // If a batch fails, give neutral scores
      for (const s of batch) {
        results.set(s.id, { score: 50, summary: 'Analysis unavailable for this batch' })
      }
    }

    // Small delay between batches to respect rate limits
    if (i + BATCH_SIZE < studentsWithNotes.length) {
      await new Promise(r => setTimeout(r, 500))
    }
  }

  return students.map(s => ({
    ...s,
    sentimentAnalysis: results.get(s.id) || s.sentimentAnalysis,
  }))
}

async function analyseBatch(
  students: Student[],
): Promise<Map<string, SentimentResult>> {
  const studentNotes = students.map(s => {
    const notes: string[] = []

    s.attendance.forEach(w => {
      if (w.engagementNote) notes.push(`Week ${w.week}: ${w.engagementNote}`)
    })

    if (s.existingMetrics?.tutorNotes) {
      notes.push(`Tutor notes: ${s.existingMetrics.tutorNotes}`)
    }

    if (s.existingMetrics?.engagementComment) {
      notes.push(`Engagement: ${s.existingMetrics.engagementComment}`)
    }

    return {
      id: s.id,
      name: `${s.firstName} ${s.lastName}`,
      notes: notes.join('\n'),
    }
  })

  const prompt = `You are an educational data analyst. Analyze the following student engagement notes. For each student, assess the overall sentiment on a scale of 0-100 (0 = extremely negative/disengaged, 100 = extremely positive/engaged). Provide a one-sentence summary.

${studentNotes.map(s => `Student "${s.id}" (${s.name}):\n${s.notes}`).join('\n\n')}

Return ONLY a JSON array with this exact structure:
[{"studentId": "...", "score": 0-100, "summary": "..."}]`

  const response = await fetch(PROXY_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ prompt }),
  })

  if (!response.ok) {
    const err = await response.json().catch(() => ({}))
    throw new Error(err.error || `AI API error: ${response.status}`)
  }

  const data = await response.json()
  const text = data.result || ''

  // Parse JSON from response (handle markdown code blocks)
  const jsonStr = text.replace(/```json?\n?/g, '').replace(/```/g, '').trim()
  const parsed = JSON.parse(jsonStr) as { studentId: string; score: number; summary: string }[]

  const results = new Map<string, SentimentResult>()
  for (const item of parsed) {
    results.set(item.studentId, {
      score: Math.max(0, Math.min(100, Math.round(item.score))),
      summary: item.summary,
    })
  }

  return results
}

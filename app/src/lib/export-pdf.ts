import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import type { Student } from '../context/types'

export function exportStudentsPDF(students: Student[]) {
  const doc = new jsPDF()
  const date = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })

  const highRisk = students.filter(s => s.riskScore?.classification === 'high')
  const mediumRisk = students.filter(s => s.riskScore?.classification === 'medium')
  const lowRisk = students.filter(s => s.riskScore?.classification === 'low')

  // Title Page
  doc.setFontSize(24)
  doc.setTextColor(99, 102, 241) // accent
  doc.text('Student Risk Analysis Report', 20, 40)

  doc.setFontSize(12)
  doc.setTextColor(161, 161, 161) // text-secondary
  doc.text('Early Risk Prediction Agent', 20, 52)
  doc.text(`Generated: ${date}`, 20, 60)
  doc.text(`Total Students: ${students.length}`, 20, 68)

  doc.setFontSize(14)
  doc.setTextColor(250, 250, 250) // text-primary
  doc.text('Risk Distribution', 20, 90)

  doc.setFontSize(11)
  doc.setTextColor(239, 68, 68)
  doc.text(`High Risk: ${highRisk.length} students`, 20, 102)
  doc.setTextColor(245, 158, 11)
  doc.text(`Medium Risk: ${mediumRisk.length} students`, 20, 110)
  doc.setTextColor(34, 197, 94)
  doc.text(`Low Risk: ${lowRisk.length} students`, 20, 118)

  // Summary Table
  doc.addPage()
  doc.setFontSize(14)
  doc.setTextColor(250, 250, 250)
  doc.text('All Students Summary', 20, 20)

  const tableData = students
    .sort((a, b) => (a.riskScore?.composite ?? 100) - (b.riskScore?.composite ?? 100))
    .map(s => [
      `${s.firstName} ${s.lastName}`,
      s.courseId,
      String(s.riskScore?.factors.attendance ?? '-'),
      String(s.riskScore?.factors.assessment ?? '-'),
      String(s.riskScore?.factors.engagement ?? '-'),
      String(s.riskScore?.composite ?? '-'),
      s.riskScore?.classification?.toUpperCase() ?? '-',
    ])

  autoTable(doc, {
    startY: 28,
    head: [['Student', 'Course', 'Attend.', 'Assess.', 'Engage.', 'Score', 'Risk']],
    body: tableData,
    theme: 'grid',
    headStyles: {
      fillColor: [26, 26, 26],
      textColor: [250, 250, 250],
      fontSize: 8,
    },
    bodyStyles: {
      fillColor: [17, 17, 17],
      textColor: [161, 161, 161],
      fontSize: 7,
    },
    alternateRowStyles: {
      fillColor: [26, 26, 26],
    },
    columnStyles: {
      6: {
        cellWidth: 18,
        halign: 'center',
      },
    },
    didParseCell(data) {
      if (data.section === 'body' && data.column.index === 6) {
        const value = String(data.cell.raw)
        if (value === 'HIGH') data.cell.styles.textColor = [239, 68, 68]
        else if (value === 'MEDIUM') data.cell.styles.textColor = [245, 158, 11]
        else if (value === 'LOW') data.cell.styles.textColor = [34, 197, 94]
      }
    },
  })

  // High Risk Student Profiles
  if (highRisk.length > 0) {
    doc.addPage()
    doc.setFontSize(14)
    doc.setTextColor(239, 68, 68)
    doc.text('High Risk Student Profiles', 20, 20)

    let yPos = 35

    for (const s of highRisk) {
      if (yPos > 250) {
        doc.addPage()
        yPos = 20
      }

      doc.setFontSize(11)
      doc.setTextColor(250, 250, 250)
      doc.text(`${s.firstName} ${s.lastName}`, 20, yPos)

      doc.setFontSize(8)
      doc.setTextColor(161, 161, 161)
      doc.text(`${s.courseId} | ${s.email} | Score: ${s.riskScore?.composite ?? '-'}/100`, 20, yPos + 6)

      // Key risk factors
      if (s.riskScore?.explanations) {
        const keyExplanations = s.riskScore.explanations
          .filter(e => !e.startsWith('  ') && e.trim() !== '' && !e.startsWith('Composite'))
          .slice(0, 4)

        keyExplanations.forEach((exp, i) => {
          doc.text(`  ${exp}`, 22, yPos + 12 + i * 5)
        })

        yPos += 12 + keyExplanations.length * 5 + 8
      } else {
        yPos += 18
      }

      // Teacher review if present
      if (s.teacherReview?.agrees !== null && s.teacherReview?.agrees !== undefined) {
        doc.setTextColor(130, 130, 130)
        doc.text(
          `Teacher Review: ${s.teacherReview.agrees ? 'Agrees' : 'Disagrees'}${s.teacherReview.notes ? ` - ${s.teacherReview.notes}` : ''}`,
          22,
          yPos
        )
        yPos += 10
      }

      // Separator line
      doc.setDrawColor(40, 40, 40)
      doc.line(20, yPos, 190, yPos)
      yPos += 8
    }
  }

  doc.save(`risk-analysis-${new Date().toISOString().slice(0, 10)}.pdf`)
}

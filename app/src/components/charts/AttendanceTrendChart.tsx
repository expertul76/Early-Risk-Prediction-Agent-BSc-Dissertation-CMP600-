import { useMemo } from 'react'
import { Line } from 'react-chartjs-2'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'
import type { Student } from '../../context/types'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend, Filler)

interface Props {
  students: Student[]
}

export default function AttendanceTrendChart({ students }: Props) {
  const chartData = useMemo(() => {
    if (students.length === 0) return null

    const maxWeek = Math.max(...students.flatMap(s => s.attendance.map(w => w.week)))
    const weeks = Array.from({ length: maxWeek }, (_, i) => i + 1)

    const weeklyAvg = weeks.map(week => {
      const studentsWithWeek = students.filter(s => s.attendance.some(w => w.week === week))
      if (studentsWithWeek.length === 0) return null

      const presentPct = studentsWithWeek.reduce((sum, s) => {
        const w = s.attendance.find(a => a.week === week)
        return sum + (w?.status === 'present' ? 100 : w?.status === 'late' ? 50 : 0)
      }, 0) / studentsWithWeek.length

      return Math.round(presentPct)
    })

    return {
      labels: weeks.map(w => `W${w}`),
      datasets: [{
        label: 'Cohort Attendance %',
        data: weeklyAvg,
        borderColor: 'rgba(99, 102, 241, 0.8)',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        fill: true,
        tension: 0.3,
        pointRadius: 3,
        pointBackgroundColor: 'rgba(99, 102, 241, 1)',
        pointBorderColor: 'transparent',
        pointHoverRadius: 5,
      }],
    }
  }, [students])

  if (!chartData) {
    return <p className="text-sm text-text-muted text-center py-8">No attendance data available</p>
  }

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      x: {
        grid: { color: 'rgba(255,255,255,0.04)' },
        ticks: { color: '#666666', font: { size: 11 } },
      },
      y: {
        min: 0,
        max: 100,
        grid: { color: 'rgba(255,255,255,0.04)' },
        ticks: {
          color: '#666666',
          font: { size: 11 },
          callback: (value: number | string) => `${value}%`,
        },
      },
    },
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#1a1a1a',
        titleColor: '#fafafa',
        bodyColor: '#a1a1a1',
        borderColor: 'rgba(255,255,255,0.08)',
        borderWidth: 1,
        cornerRadius: 8,
        padding: 10,
        callbacks: {
          label: (ctx: { parsed: { y: number | null } }) => ` ${ctx.parsed.y ?? 0}% attendance`,
        },
      },
    },
  }

  return (
    <div className="h-[220px]">
      <Line data={chartData} options={options} />
    </div>
  )
}

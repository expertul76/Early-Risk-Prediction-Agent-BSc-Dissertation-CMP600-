import { Doughnut } from 'react-chartjs-2'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

interface Props {
  low: number
  medium: number
  high: number
}

export default function RiskDistributionChart({ low, medium, high }: Props) {
  const total = low + medium + high

  const data = {
    labels: ['Low Risk', 'Medium Risk', 'High Risk'],
    datasets: [{
      data: [low, medium, high],
      backgroundColor: [
        'rgba(34, 197, 94, 0.7)',
        'rgba(245, 158, 11, 0.7)',
        'rgba(239, 68, 68, 0.7)',
      ],
      borderColor: [
        'rgba(34, 197, 94, 0.3)',
        'rgba(245, 158, 11, 0.3)',
        'rgba(239, 68, 68, 0.3)',
      ],
      borderWidth: 1,
      hoverOffset: 8,
    }],
  }

  const options = {
    responsive: true,
    maintainAspectRatio: true,
    cutout: '65%',
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        backgroundColor: '#1a1a1a',
        titleColor: '#fafafa',
        bodyColor: '#a1a1a1',
        borderColor: 'rgba(255,255,255,0.08)',
        borderWidth: 1,
        cornerRadius: 8,
        padding: 10,
        callbacks: {
          label: (ctx: { parsed: number }) => {
            const pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0
            return ` ${ctx.parsed} students (${pct}%)`
          },
        },
      },
    },
  }

  return (
    <div className="relative max-w-[240px] mx-auto">
      <Doughnut data={data} options={options} />
      <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div className="text-center">
          <p className="text-2xl font-bold text-text-primary">{total}</p>
          <p className="text-xs text-text-muted">students</p>
        </div>
      </div>
      <div className="flex justify-center gap-4 mt-4">
        {[
          { label: 'Low', count: low, color: 'bg-risk-low' },
          { label: 'Medium', count: medium, color: 'bg-risk-medium' },
          { label: 'High', count: high, color: 'bg-risk-high' },
        ].map(item => (
          <div key={item.label} className="flex items-center gap-1.5 text-xs text-text-muted">
            <span className={`w-2.5 h-2.5 rounded-full ${item.color}`} />
            {item.count}
          </div>
        ))}
      </div>
    </div>
  )
}

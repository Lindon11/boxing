export function formatDate(value: string | null | undefined, options: Intl.DateTimeFormatOptions = {}) {
  if (!value) return 'TBC'

  return new Date(value).toLocaleDateString(undefined, {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    ...options,
  })
}

export function formatDateTime(value: string | null | undefined) {
  if (!value) return 'TBC'

  return new Date(value).toLocaleString(undefined, {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export function formatNumber(value: number | null | undefined) {
  return new Intl.NumberFormat().format(value ?? 0)
}

export function koRate(wins: number, knockouts: number) {
  if (wins <= 0) return '0%'
  return `${Math.round((knockouts / wins) * 100)}%`
}

export function titleCase(value: string | null | undefined) {
  if (!value) return ''
  return value.replace(/_/g, ' ').replace(/\b\w/g, (letter: string) => letter.toUpperCase())
}

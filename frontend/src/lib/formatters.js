export function formatNumber(value) {
  return Number(value || 0).toLocaleString();
}

export function formatCurrency(value, currency) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'USD',
    maximumFractionDigits: 2
  }).format(Number(value || 0));
}

export function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleString();
}

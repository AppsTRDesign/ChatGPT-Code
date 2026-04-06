import { apiGet } from './apiClient';

export function fetchAdminDashboard() {
  return apiGet('/admin/dashboard');
}

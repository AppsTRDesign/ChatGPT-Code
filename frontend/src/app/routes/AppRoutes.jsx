import React from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import DashboardPage from '../../pages/dashboard/DashboardPage';
import MapPage from '../../pages/map/MapPage';

export default function AppRoutes() {
  return (
    <Routes>
      <Route path="/dashboard" element={<DashboardPage />} />
      <Route path="/map" element={<MapPage />} />
      <Route path="*" element={<Navigate to="/map" replace />} />
    </Routes>
  );
}

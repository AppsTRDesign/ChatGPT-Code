import React from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import DashboardPage from '../../pages/dashboard/DashboardPage';
import MapPage from '../../pages/map/MapPage';
import CountryPage from '../../pages/country/CountryPage';
import CityPage from '../../pages/city/CityPage';
import TravelPage from '../../pages/travel/TravelPage';

export default function AppRoutes() {
  return (
    <Routes>
      <Route path="/dashboard" element={<DashboardPage />} />
      <Route path="/map" element={<MapPage />} />
      <Route path="/country/:countryCode" element={<CountryPage />} />
      <Route path="/city/:countryCode/:cityName" element={<CityPage />} />
      <Route path="/travel" element={<TravelPage />} />
      <Route path="*" element={<Navigate to="/map" replace />} />
    </Routes>
  );
}

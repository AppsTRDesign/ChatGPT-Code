import React from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import DashboardPage from '../../pages/dashboard/DashboardPage';
import MapPage from '../../pages/map/MapPage';
import CountryPage from '../../pages/country/CountryPage';
import CityPage from '../../pages/city/CityPage';
import TravelPage from '../../pages/travel/TravelPage';
import ElectionsPage from '../../pages/elections/ElectionsPage';
import EconomyPage from '../../pages/economy/EconomyPage';
import WarPage from '../../pages/war/WarPage';
import RealtimePage from '../../pages/realtime/RealtimePage';
import ProfilePage from '../../pages/profile/ProfilePage';
import InventoryPage from '../../pages/inventory/InventoryPage';
import AdminPage from '../../pages/admin/AdminPage';
import LoginPage from '../../pages/profile/LoginPage';

export default function AppRoutes() {
  return (
    <Routes>
      <Route path="/dashboard" element={<DashboardPage />} />
      <Route path="/map" element={<MapPage />} />
      <Route path="/country/:countryCode" element={<CountryPage />} />
      <Route path="/city/:countryCode/:cityName" element={<CityPage />} />
      <Route path="/travel" element={<TravelPage />} />
      <Route path="/elections" element={<ElectionsPage />} />
      <Route path="/economy" element={<EconomyPage />} />
      <Route path="/war" element={<WarPage />} />
      <Route path="/realtime" element={<RealtimePage />} />
      <Route path="/profile" element={<ProfilePage />} />
      <Route path="/inventory" element={<InventoryPage />} />
      <Route path="/admin" element={<AdminPage />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="*" element={<Navigate to="/map" replace />} />
    </Routes>
  );
}

import React, { useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import WorldMapCanvas from '../../components/map/WorldMapCanvas';
import CountryInfoCard from '../../components/cards/CountryInfoCard';
import CityInfoCard from '../../components/cards/CityInfoCard';
import LanguageSwitcher from '../../components/ui/LanguageSwitcher';
import { useI18n } from '../../i18n/I18nProvider';

export default function MapPage() {
  const { t } = useI18n();
  const navigate = useNavigate();
  const [countryCard, setCountryCard] = useState(null);
  const [cityCard, setCityCard] = useState(null);
  const [travelLine, setTravelLine] = useState(null);
  const [focusTarget, setFocusTarget] = useState(null);

  function handleFlyToCapital() {
    if (!countryCard) return;
    setFocusTarget({ latitude: countryCard.centroid.latitude, longitude: countryCard.centroid.longitude, zoom: 5 });
  }

  function handleFlyToCity() {
    if (!cityCard) return;

    setTravelLine({
      from: { latitude: 41.0082, longitude: 28.9784 },
      to: { latitude: cityCard.latitude, longitude: cityCard.longitude }
    });

    setFocusTarget({ latitude: cityCard.latitude, longitude: cityCard.longitude, zoom: 6 });
  }

  const rightPanel = useMemo(() => {
    if (cityCard) {
      return <CityInfoCard city={cityCard} onFlyHere={handleFlyToCity} onViewDetails={() => navigate(`/city/${cityCard.countryCode}/${encodeURIComponent(cityCard.name)}`)} />;
    }

    if (countryCard) {
      return <CountryInfoCard country={countryCard} onFlyToCapital={handleFlyToCapital} onViewDetails={() => navigate(`/country/${countryCard.code}`)} />;
    }

    return (
      <div className="glass-panel p-4 text-slate-300 text-sm">
        Haritada bir ülke veya şehir seçin.
      </div>
    );
  }, [cityCard, countryCard]);

  const navItems = [
    { label: 'Dashboard', to: '/dashboard' },
    { label: 'Politics', to: '/elections' },
    { label: 'Travel', to: '/travel' },
    { label: 'Economy', to: '/economy' },
    { label: 'War', to: '/war' },
    { label: 'Realtime', to: '/realtime' }
  ];

  return (
    <div className="page-wrap">
      <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
        <div>
          <h1 className="section-title">World Map</h1>
          <p className="text-sm text-slate-400">Country / city selection, focus zoom, travel path animation.</p>
        </div>
        <LanguageSwitcher />
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-12 gap-4">
        <aside className="xl:col-span-2 glass-panel p-4">
          <p className="text-sm text-slate-400 mb-2">Navigation</p>
          <div className="flex flex-wrap xl:flex-col gap-2 text-sm">
            <span className="rounded-lg px-2 py-1 bg-slate-800 text-slate-200">Map</span>
            {navItems.map((item) => (
              <Link key={item.to} to={item.to} className="nav-link">{item.label}</Link>
            ))}
          </div>
        </aside>

        <main className="xl:col-span-7">
          <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} className="glass-panel p-2 md:p-3">
            <WorldMapCanvas
              onCountrySelected={(card) => {
                setCountryCard(card);
                setCityCard(null);
                setFocusTarget({ latitude: card.centroid.latitude, longitude: card.centroid.longitude, zoom: 4 });
              }}
              onCitySelected={(card) => {
                setCityCard(card);
                setCountryCard(null);
                setFocusTarget({ latitude: card.latitude, longitude: card.longitude, zoom: 6 });
              }}
              travelLine={travelLine}
              focusTarget={focusTarget}
            />
          </motion.div>
        </main>

        <aside className="xl:col-span-3 space-y-3">
          {rightPanel}
          <div className="glass-panel p-4 text-sm text-slate-300">
            <p className="font-medium mb-2">{t('travel.in_progress')}</p>
            <p className="text-slate-400">Aktif uçuş yok</p>
          </div>
        </aside>
      </div>
    </div>
  );
}

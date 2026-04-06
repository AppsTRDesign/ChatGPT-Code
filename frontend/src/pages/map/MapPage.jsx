import React, { useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
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
      <div className="rounded-2xl border border-slate-700 bg-slate-900/80 p-4 text-slate-300">
        Haritada bir ülke veya şehir seçin.
      </div>
    );
  }, [cityCard, countryCard]);

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 p-5">
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-2xl font-semibold">World Map</h1>
        <LanguageSwitcher />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <aside className="col-span-2 rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
          <p className="text-sm text-slate-400">Navigation</p>
          <ul className="mt-3 space-y-2 text-sm text-slate-200">
            <li><Link to="/dashboard" className="text-cyan-400">Dashboard</Link></li>
            <li>Map</li>
            <li><Link to="/elections" className="text-cyan-400">Politics</Link></li>
            <li><Link to="/travel" className="text-cyan-400">Travel</Link></li>
            <li>War</li>
          </ul>
        </aside>

        <main className="col-span-7">
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
        </main>

        <aside className="col-span-3 space-y-3">
          {rightPanel}
          <div className="rounded-2xl border border-slate-700 bg-slate-900/80 p-4 text-sm text-slate-300">
            <p className="font-medium mb-2">{t('travel.in_progress')}</p>
            <p className="text-slate-400">Aktif uçuş yok</p>
          </div>
        </aside>
      </div>
    </div>
  );
}

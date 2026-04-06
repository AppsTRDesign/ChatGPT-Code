import React, { useEffect, useMemo, useState } from 'react';
import { CircleMarker, MapContainer, Polyline, TileLayer, Tooltip, useMap } from 'react-leaflet';
import { useQuery } from '@tanstack/react-query';
import { fetchCityCard, fetchCountryCard, fetchWorldMap } from '../../services/mapApi';

function FocusController({ target }) {
  const map = useMap();

  useEffect(() => {
    if (!target) return;
    map.setView([target.latitude, target.longitude], target.zoom || 5, { animate: true, duration: 1.2 });
  }, [map, target]);

  return null;
}

export default function WorldMapCanvas({ onCountrySelected, onCitySelected, travelLine, focusTarget }) {
  const [selectedCountryCode, setSelectedCountryCode] = useState(null);

  const { data: world } = useQuery({ queryKey: ['world-map'], queryFn: fetchWorldMap });

  const countries = world?.countries || [];
  const cities = world?.cities || [];

  const countryMarkers = useMemo(() => {
    return countries.map((country) => ({
      ...country,
      radius: country.playable ? 14 : 8,
      color: country.playable ? '#22d3ee' : '#64748b'
    }));
  }, [countries]);

  async function selectCountry(countryCode) {
    setSelectedCountryCode(countryCode);
    const card = await fetchCountryCard(countryCode);
    onCountrySelected(card);
  }

  async function selectCity(countryCode, cityName) {
    const card = await fetchCityCard(countryCode, cityName);
    onCitySelected(card);
  }

  return (
    <MapContainer center={[20, 10]} zoom={2} minZoom={2} className="h-[72vh] w-full rounded-2xl overflow-hidden">
      <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" attribution="&copy; OpenStreetMap contributors" />
      <FocusController target={focusTarget} />

      {countryMarkers.map((country) => (
        <CircleMarker
          key={country.code}
          center={[country.centroid.latitude, country.centroid.longitude]}
          radius={selectedCountryCode === country.code ? country.radius + 3 : country.radius}
          pathOptions={{ color: country.color, fillOpacity: 0.55 }}
          eventHandlers={{ click: () => selectCountry(country.code) }}
        >
          <Tooltip>{country.flagEmoji} {country.name}</Tooltip>
        </CircleMarker>
      ))}

      {cities.map((city) => (
        <CircleMarker
          key={`${city.countryCode}-${city.name}`}
          center={[city.latitude, city.longitude]}
          radius={city.isCapital ? 7 : 5}
          pathOptions={{ color: city.isCapital ? '#f59e0b' : '#38bdf8', fillOpacity: 0.7 }}
          eventHandlers={{ click: () => selectCity(city.countryCode, city.name) }}
        >
          <Tooltip>{city.name}</Tooltip>
        </CircleMarker>
      ))}

      {travelLine ? (
        <Polyline positions={[[travelLine.from.latitude, travelLine.from.longitude], [travelLine.to.latitude, travelLine.to.longitude]]} pathOptions={{ color: '#22d3ee', weight: 3 }} />
      ) : null}
    </MapContainer>
  );
}

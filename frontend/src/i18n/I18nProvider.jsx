import React, { createContext, useContext, useMemo, useState } from 'react';
import en from '../../lang/en.json';
import tr from '../../lang/tr.json';

const I18nContext = createContext(null);

const dictionaries = { en, tr };

function detectBrowserLang() {
  if (typeof navigator === 'undefined') return 'en';
  const raw = navigator.language || 'en';
  const shortCode = raw.toLowerCase().split('-')[0];
  return dictionaries[shortCode] ? shortCode : 'en';
}

function getInitialLanguage() {
  const fromStorage = typeof localStorage !== 'undefined' ? localStorage.getItem('lang') : null;
  if (fromStorage && dictionaries[fromStorage]) return fromStorage;
  return detectBrowserLang();
}

export function I18nProvider({ children }) {
  const [language, setLanguageState] = useState(getInitialLanguage);

  function setLanguage(next) {
    const safe = dictionaries[next] ? next : 'en';
    setLanguageState(safe);
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('lang', safe);
    }
  }

  function t(key) {
    return dictionaries[language][key] || dictionaries.en[key] || key;
  }

  const value = useMemo(
    () => ({ language, setLanguage, t, availableLanguages: Object.keys(dictionaries) }),
    [language]
  );

  return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
}

export function useI18n() {
  const ctx = useContext(I18nContext);
  if (!ctx) throw new Error('useI18n must be used inside I18nProvider');
  return ctx;
}

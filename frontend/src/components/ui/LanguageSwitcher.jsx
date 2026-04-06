import React from 'react';
import { useI18n } from '../../i18n/I18nProvider';

export default function LanguageSwitcher() {
  const { language, setLanguage } = useI18n();

  return (
    <div className="flex items-center gap-2 rounded-xl bg-slate-900/70 border border-slate-700 px-3 py-2">
      <button
        className={`px-2 py-1 rounded ${language === 'en' ? 'bg-cyan-600 text-white' : 'bg-slate-800 text-slate-300'}`}
        onClick={() => setLanguage('en')}
      >
        EN
      </button>
      <button
        className={`px-2 py-1 rounded ${language === 'tr' ? 'bg-cyan-600 text-white' : 'bg-slate-800 text-slate-300'}`}
        onClick={() => setLanguage('tr')}
      >
        TR
      </button>
    </div>
  );
}

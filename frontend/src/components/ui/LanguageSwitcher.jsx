import React from 'react';
import { motion } from 'framer-motion';
import { useI18n } from '../../i18n/I18nProvider';

const LANG_OPTIONS = [
  { code: 'en', label: 'English', short: 'EN' },
  { code: 'tr', label: 'Türkçe', short: 'TR' }
];

export default function LanguageSwitcher() {
  const { language, setLanguage } = useI18n();

  return (
    <div className="glass-panel flex items-center gap-2 p-1.5">
      {LANG_OPTIONS.map((opt) => {
        const active = language === opt.code;
        return (
          <motion.button
            key={opt.code}
            whileTap={{ scale: 0.95 }}
            onClick={() => setLanguage(opt.code)}
            className={`rounded-lg px-3 py-1.5 text-sm transition ${active ? 'bg-cyan-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800'}`}
            title={opt.label}
          >
            <span className="sm:hidden">{opt.short}</span>
            <span className="hidden sm:inline">{opt.label}</span>
          </motion.button>
        );
      })}
    </div>
  );
}

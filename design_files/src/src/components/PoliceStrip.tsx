import { PhoneIcon, XIcon, ShieldAlertIcon } from 'lucide-react';
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
export const PoliceStrip: React.FC = () => {
  const [isVisible, setIsVisible] = useState(true);

  if (!isVisible) {
return null;
}

  return (
    <div className="bg-brand-amber-light/10 border-b border-brand-amber/20 text-brand-amber-dark px-4 py-2.5 text-sm relative z-40">
      <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-6 pr-8 text-center sm:text-left">
        <div className="flex items-center gap-2 font-medium">
          <ShieldAlertIcon className="w-4 h-4 shrink-0" />
          <span>
            Witnessed or experienced a crime? Contact police directly.
          </span>
        </div>
        <div className="flex items-center gap-4 text-sm">
          <a
            href="tel:999"
            className="flex items-center gap-1 hover:text-brand-amber transition-colors font-semibold">
            
            <PhoneIcon className="w-3.5 h-3.5" /> Emergency: 999
          </a>
          <span className="opacity-50 hidden sm:inline">•</span>
          <a
            href="tel:101"
            className="flex items-center gap-1 hover:text-brand-amber transition-colors font-semibold">
            
            <PhoneIcon className="w-3.5 h-3.5" /> Non-emergency: 101
          </a>
          <span className="opacity-50 hidden sm:inline">•</span>
          <Link
            to="/about#police"
            className="underline hover:text-brand-amber transition-colors">
            
            Learn more
          </Link>
        </div>
      </div>
      <button
        onClick={() => setIsVisible(false)}
        className="absolute right-2 top-1/2 -translate-y-1/2 p-2 hover:bg-brand-amber/10 rounded-full transition-colors sm:hidden"
        aria-label="Dismiss">
        
        <XIcon className="w-4 h-4" />
      </button>
    </div>);

};
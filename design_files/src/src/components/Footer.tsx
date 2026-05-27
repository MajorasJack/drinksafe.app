import React from 'react';
import { Link } from 'react-router-dom';
import { ShieldIcon } from 'lucide-react';
export const Footer: React.FC = () => {
  return (
    <footer className="bg-slate-900 text-slate-300 py-12 mt-auto">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
          <div className="col-span-1 md:col-span-2">
            <Link to="/" className="flex items-center gap-2 mb-4">
              <ShieldIcon className="w-6 h-6 text-brand-teal-light" />
              <span className="font-bold text-xl text-white tracking-tight">
                DrinkSafe
              </span>
            </Link>
            <p className="text-sm text-slate-400 max-w-md mb-4 leading-relaxed">
              An independent, informational platform for sharing and viewing
              anonymized reports of venue spiking incidents. Our goal is to help
              people make informed decisions about their safety.
            </p>
            <div className="bg-slate-800/50 border border-slate-700 rounded-lg p-4 text-sm">
              <strong className="text-white block mb-1">
                Important Disclaimer
              </strong>
              DrinkSafe is strictly informational. Submitting a report here does
              NOT notify the police or any authorities. If you have been a
              victim of a crime, please contact your local police directly.
            </div>
          </div>

          <div>
            <h3 className="text-white font-semibold mb-4">Navigation</h3>
            <ul className="space-y-3 text-sm">
              <li>
                <Link to="/" className="hover:text-white transition-colors">
                  Home
                </Link>
              </li>
              <li>
                <Link to="/map" className="hover:text-white transition-colors">
                  Map & Reports
                </Link>
              </li>
              <li>
                <Link
                  to="/report"
                  className="hover:text-white transition-colors">

                  Submit a Report
                </Link>
              </li>
              <li>
                <Link
                  to="/about"
                  className="hover:text-white transition-colors">

                  About & Resources
                </Link>
              </li>
            </ul>
          </div>

          <div>
            <h3 className="text-white font-semibold mb-4">
              Emergency Contacts
            </h3>
            <ul className="space-y-3 text-sm">
              <li>
                <span className="block text-slate-400 text-xs mb-0.5">
                  Police Emergency
                </span>
                <a
                  href="tel:999"
                  className="text-white font-medium hover:text-brand-amber transition-colors">

                  999
                </a>
              </li>
              <li>
                <span className="block text-slate-400 text-xs mb-0.5">
                  Police Non-Emergency
                </span>
                <a
                  href="tel:101"
                  className="text-white font-medium hover:text-brand-amber transition-colors">

                  101
                </a>
              </li>
              <li>
                <span className="block text-slate-400 text-xs mb-0.5">
                  Victim Support (UK)
                </span>
                <a
                  href="tel:08081689111"
                  className="text-white font-medium hover:text-brand-amber transition-colors">

                  0808 1689 111
                </a>
              </li>
            </ul>
          </div>
        </div>

        <div className="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-500">
          <p>
            © {new Date().getFullYear()} DrinkSafe. Informational purposes only.
          </p>
          <div className="flex gap-4">
            <Link to="/about" className="hover:text-white transition-colors">
              Privacy Policy
            </Link>
            <Link to="/about" className="hover:text-white transition-colors">
              Terms of Use
            </Link>
          </div>
        </div>
      </div>
    </footer>);

};

import React, { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { ShieldIcon, MenuIcon, XIcon } from 'lucide-react';
export const Header: React.FC = () => {
  const location = useLocation();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const navLinks = [
  {
    name: 'Home',
    path: '/'
  },
  {
    name: 'Map & Reports',
    path: '/map'
  },
  {
    name: 'Submit Report',
    path: '/report'
  },
  {
    name: 'About & Resources',
    path: '/about'
  }];

  const isActive = (path: string) => {
    if (path === '/' && location.pathname !== '/') return false;
    return location.pathname.startsWith(path);
  };
  return (
    <header className="bg-white border-b border-slate-200 sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          <Link to="/" className="flex items-center gap-2 group">
            <div className="bg-brand-teal text-white p-1.5 rounded-lg group-hover:bg-brand-teal-light transition-colors">
              <ShieldIcon className="w-5 h-5" />
            </div>
            <span className="font-bold text-xl text-slate-900 tracking-tight">
              DrinkSafe
            </span>
          </Link>

          {/* Desktop Nav */}
          <nav className="hidden md:flex items-center gap-8">
            {navLinks.map((link) =>
            <Link
              key={link.path}
              to={link.path}
              className={`text-sm font-medium transition-colors ${isActive(link.path) ? 'text-brand-teal' : 'text-slate-600 hover:text-slate-900'}`}>

                {link.name}
              </Link>
            )}
            <Link
              to="/report"
              className="bg-brand-teal hover:bg-brand-teal-light text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">

              Share a Report
            </Link>
          </nav>

          {/* Mobile Menu Button */}
          <button
            className="md:hidden p-2 text-slate-600"
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}>

            {isMobileMenuOpen ?
            <XIcon className="w-6 h-6" /> :

            <MenuIcon className="w-6 h-6" />
            }
          </button>
        </div>
      </div>

      {/* Mobile Nav */}
      {isMobileMenuOpen &&
      <div className="md:hidden border-t border-slate-100 bg-white">
          <div className="px-4 pt-2 pb-4 space-y-1">
            {navLinks.map((link) =>
          <Link
            key={link.path}
            to={link.path}
            onClick={() => setIsMobileMenuOpen(false)}
            className={`block px-3 py-3 rounded-md text-base font-medium ${isActive(link.path) ? 'bg-brand-teal/5 text-brand-teal' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'}`}>

                {link.name}
              </Link>
          )}
          </div>
        </div>
      }
    </header>);

};

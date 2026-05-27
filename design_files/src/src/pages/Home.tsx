import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import {
  SearchIcon,
  MapPinIcon,
  ShieldCheckIcon,
  ClockIcon,
  ArrowRightIcon } from
'lucide-react';
import { DisclaimerBanner } from '../components/DisclaimerBanner';
import { useReports } from '../context/ReportContext';
import { formatDistanceToNow } from 'date-fns';
export const Home: React.FC = () => {
  const navigate = useNavigate();
  const { populatedReports, setSearchQuery } = useReports();
  const recentReports = populatedReports.slice(0, 3);
  const handleSearch = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    const query = formData.get('search') as string;
    if (query.trim()) {
      setSearchQuery(query);
      navigate('/map');
    }
  };
  return (
    <motion.div
      initial={{
        opacity: 0
      }}
      animate={{
        opacity: 1
      }}
      className="flex-grow">

      {/* Hero Section */}
      <section className="bg-brand-teal text-white py-16 sm:py-24 relative overflow-hidden">
        <div className="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-white via-transparent to-transparent"></div>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <div className="max-w-3xl">
            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight mb-6">
              Community awareness for safer nights out.
            </h1>
            <p className="text-lg sm:text-xl text-brand-teal-light/20 text-slate-200 mb-10 leading-relaxed max-w-2xl">
              DrinkSafe is an anonymous, informational platform to share and view
              reports of venue spiking incidents. Stay informed and help others
              make safer decisions.
            </p>

            <form onSubmit={handleSearch} className="relative max-w-2xl mb-8">
              <div className="relative flex items-center">
                <SearchIcon className="absolute left-4 text-slate-400 w-6 h-6" />
                <input
                  type="text"
                  name="search"
                  placeholder="Search by city, town, or venue name..."
                  className="w-full pl-14 pr-32 py-4 rounded-xl text-slate-900 text-lg focus:outline-none focus:ring-4 focus:ring-brand-amber/30 shadow-lg text-white"
                />

                <button
                  type="submit"
                  className="absolute right-2 bg-brand-amber hover:bg-brand-amber-light text-white px-6 py-2 rounded-lg font-medium transition-colors">

                  Search
                </button>
              </div>
            </form>

            <div className="flex flex-wrap gap-4 text-sm font-medium">
              <Link
                to="/map"
                className="flex items-center gap-2 bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-colors">

                <MapPinIcon className="w-4 h-4" /> Browse Map
              </Link>
              <Link
                to="/report"
                className="flex items-center gap-2 bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-colors">

                <ShieldCheckIcon className="w-4 h-4" /> Share a Report
              </Link>
            </div>
          </div>
        </div>
      </section>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <DisclaimerBanner className="mb-16" />

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">
          {/* Recent Reports */}
          <div className="lg:col-span-2">
            <div className="flex justify-between items-end mb-6">
              <div>
                <h2 className="text-2xl font-bold text-slate-900 mb-2">
                  Recent Reports
                </h2>
                <p className="text-slate-600">
                  Latest anonymized submissions from the community.
                </p>
              </div>
              <Link
                to="/map"
                className="hidden sm:flex items-center gap-1 text-brand-teal font-medium hover:text-brand-teal-light transition-colors">

                View all <ArrowRightIcon className="w-4 h-4" />
              </Link>
            </div>

            <div className="space-y-4">
              {recentReports.map((report) =>
              <Link
                key={report.id}
                to={`/venue/${report.venueId}`}
                className="block bg-white border border-slate-200 rounded-xl p-5 hover:border-brand-teal/30 hover:shadow-md transition-all group">

                  <div className="flex justify-between items-start mb-3">
                    <div>
                      <h3 className="font-semibold text-lg text-slate-900 group-hover:text-brand-teal transition-colors">
                        {report.venue.name}
                      </h3>
                      <p className="text-sm text-slate-500 flex items-center gap-1 mt-1">
                        <MapPinIcon className="w-3.5 h-3.5" />{' '}
                        {report.venue.city}
                      </p>
                    </div>
                    <span className="text-xs font-medium bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full flex items-center gap-1">
                      <ClockIcon className="w-3 h-3" />
                      {formatDistanceToNow(new Date(report.date), {
                      addSuffix: true
                    })}
                    </span>
                  </div>
                  <p className="text-slate-700 line-clamp-2 text-sm leading-relaxed">
                    "{report.description}"
                  </p>
                </Link>
              )}
            </div>

            <Link
              to="/map"
              className="sm:hidden flex items-center justify-center gap-2 w-full mt-6 bg-slate-100 hover:bg-slate-200 text-slate-700 py-3 rounded-xl font-medium transition-colors">

              View all reports
            </Link>
          </div>

          {/* How it works */}
          <div>
            <h2 className="text-2xl font-bold text-slate-900 mb-6">
              How it works
            </h2>
            <div className="bg-white border border-slate-200 rounded-xl p-6 space-y-8">
              <div className="flex gap-4">
                <div className="bg-brand-teal/10 text-brand-teal w-8 h-8 rounded-full flex items-center justify-center font-bold shrink-0">
                  1
                </div>
                <div>
                  <h4 className="font-semibold text-slate-900 mb-1">
                    Search or Browse
                  </h4>
                  <p className="text-sm text-slate-600">
                    Look up venues or cities to see if there are any recent
                    community reports.
                  </p>
                </div>
              </div>
              <div className="flex gap-4">
                <div className="bg-brand-teal/10 text-brand-teal w-8 h-8 rounded-full flex items-center justify-center font-bold shrink-0">
                  2
                </div>
                <div>
                  <h4 className="font-semibold text-slate-900 mb-1">
                    Stay Informed
                  </h4>
                  <p className="text-sm text-slate-600">
                    Read anonymized accounts to understand potential risks at
                    specific locations.
                  </p>
                </div>
              </div>
              <div className="flex gap-4">
                <div className="bg-brand-teal/10 text-brand-teal w-8 h-8 rounded-full flex items-center justify-center font-bold shrink-0">
                  3
                </div>
                <div>
                  <h4 className="font-semibold text-slate-900 mb-1">
                    Share Anonymously
                  </h4>
                  <p className="text-sm text-slate-600">
                    If you've experienced an incident, share it to help protect
                    others. No personal data is required.
                  </p>
                </div>
              </div>
            </div>

            <div className="mt-6 bg-brand-amber/10 border border-brand-amber/20 rounded-xl p-5">
              <h4 className="font-semibold text-brand-amber-dark mb-2 flex items-center gap-2">
                <ShieldCheckIcon className="w-5 h-5" /> Need to report a crime?
              </h4>
              <p className="text-sm text-brand-amber-dark/80 mb-3">
                This platform does not contact the police. If you need
                authorities, please contact them directly.
              </p>
              <Link
                to="/about#police"
                className="text-sm font-medium text-brand-amber-dark underline hover:text-brand-amber transition-colors">

                View police contact guide
              </Link>
            </div>
          </div>
        </div>
      </div>
    </motion.div>);

};

import React from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import {
  MapPinIcon,
  ArrowLeftIcon,
  ShieldAlertIcon,
  CalendarIcon,
  ClockIcon,
  InfoIcon } from
'lucide-react';
import { useReports } from '../context/ReportContext';
import { format } from 'date-fns';
import { DisclaimerBanner } from '../components/DisclaimerBanner';
export const VenueDetail: React.FC = () => {
  const { id } = useParams<{
    id: string;
  }>();
  const navigate = useNavigate();
  const { venues, reports } = useReports();
  const venue = venues.find((v) => v.id === id);
  const venueReports = reports.
  filter((r) => r.venueId === id).
  sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
  if (!venue) {
    return (
      <div className="flex-grow flex flex-col items-center justify-center p-8 text-center">
        <h2 className="text-2xl font-bold text-slate-900 mb-2">
          Venue not found
        </h2>
        <p className="text-slate-600 mb-6">
          We couldn't find the venue you're looking for.
        </p>
        <button
          onClick={() => navigate('/map')}
          className="text-brand-teal font-medium hover:underline">
          
          Return to map
        </button>
      </div>);

  }
  return (
    <motion.div
      initial={{
        opacity: 0,
        y: 10
      }}
      animate={{
        opacity: 1,
        y: 0
      }}
      className="flex-grow bg-slate-50 py-8">
      
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <Link
          to="/map"
          className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-900 mb-6 transition-colors">
          
          <ArrowLeftIcon className="w-4 h-4" /> Back to map
        </Link>

        {/* Venue Header */}
        <div className="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 mb-8 shadow-sm">
          <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-6">
            <div>
              <h1 className="text-3xl font-bold text-slate-900 mb-2">
                {venue.name}
              </h1>
              <div className="flex items-center gap-2 text-slate-600 mb-4">
                <MapPinIcon className="w-4 h-4 shrink-0" />
                <span>{venue.address}</span>
              </div>
              <div className="inline-flex items-center gap-2 bg-slate-100 text-slate-700 px-3 py-1.5 rounded-lg text-sm font-medium">
                <InfoIcon className="w-4 h-4" />
                {venueReports.length} anonymized report
                {venueReports.length !== 1 ? 's' : ''}
              </div>
            </div>

            <Link
              to={`/report?venue=${venue.id}`}
              className="bg-brand-teal hover:bg-brand-teal-light text-white px-5 py-2.5 rounded-xl font-medium transition-colors text-center shrink-0">
              
              Add a Report
            </Link>
          </div>
        </div>

        <DisclaimerBanner className="mb-8" />

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {/* Reports List */}
          <div className="md:col-span-2 space-y-4">
            <h2 className="text-xl font-bold text-slate-900 mb-4">
              Reported Incidents
            </h2>

            {venueReports.length === 0 ?
            <div className="bg-white border border-slate-200 rounded-xl p-8 text-center text-slate-500">
                No reports for this venue yet.
              </div> :

            venueReports.map((report) =>
            <div
              key={report.id}
              className="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 shadow-sm">
              
                  <div className="flex flex-wrap gap-4 mb-4 text-sm text-slate-600 border-b border-slate-100 pb-4">
                    <div className="flex items-center gap-1.5">
                      <CalendarIcon className="w-4 h-4 text-slate-400" />
                      {format(new Date(report.date), 'MMMM d, yyyy')}
                    </div>
                    <div className="flex items-center gap-1.5">
                      <ClockIcon className="w-4 h-4 text-slate-400" />
                      {report.timeOfDay}
                    </div>
                  </div>
                  <p className="text-slate-800 leading-relaxed">
                    "{report.description}"
                  </p>
                </div>
            )
            }
          </div>

          {/* Police CTA Sidebar */}
          <div className="space-y-6">
            <div className="bg-brand-amber/10 border border-brand-amber/20 rounded-xl p-6">
              <div className="flex items-center gap-3 mb-4">
                <div className="bg-brand-amber/20 p-2 rounded-full text-brand-amber-dark">
                  <ShieldAlertIcon className="w-6 h-6" />
                </div>
                <h3 className="font-bold text-brand-amber-dark">
                  Report to Police
                </h3>
              </div>
              <p className="text-sm text-brand-amber-dark/80 mb-6 leading-relaxed">
                If you have been spiked, it is a serious crime. The police
                recommend reporting it as soon as possible, even if you don't
                want to press charges.
              </p>
              <div className="space-y-3">
                <a
                  href="tel:999"
                  className="flex items-center justify-center w-full bg-white border border-brand-amber/30 text-brand-amber-dark py-2.5 rounded-lg font-bold hover:bg-brand-amber/5 transition-colors">
                  
                  Emergency: 999
                </a>
                <a
                  href="tel:101"
                  className="flex items-center justify-center w-full bg-white border border-brand-amber/30 text-brand-amber-dark py-2.5 rounded-lg font-bold hover:bg-brand-amber/5 transition-colors">
                  
                  Non-Emergency: 101
                </a>
              </div>
            </div>

            <div className="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
              <h3 className="font-bold text-slate-900 mb-2">Need Support?</h3>
              <p className="text-sm text-slate-600 mb-4">
                You don't have to go through this alone. There are organizations
                that can help.
              </p>
              <Link
                to="/about"
                className="text-brand-teal font-medium text-sm hover:underline flex items-center gap-1">
                
                View support resources{' '}
                <ArrowLeftIcon className="w-3 h-3 rotate-180" />
              </Link>
            </div>
          </div>
        </div>
      </div>
    </motion.div>);

};
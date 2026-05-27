import React, { useEffect, useMemo, useState, useRef } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { motion } from 'framer-motion';
import {
  ShieldAlertIcon,
  CheckCircleIcon,
  SearchIcon,
  PlusIcon,
  CheckIcon,
  XIcon } from
'lucide-react';
import { toast } from 'sonner';
import { useReports } from '../context/ReportContext';
import { DisclaimerBanner } from '../components/DisclaimerBanner';
export const SubmitReport: React.FC = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const initialVenueId = searchParams.get('venue') || '';
  const { venues, addReport } = useReports();
  const [step, setStep] = useState(1);
  const [isSubmitting, setIsSubmitting] = useState(false);
  // Form State
  const initialVenue = venues.find((v) => v.id === initialVenueId);
  const [venueQuery, setVenueQuery] = useState(
    initialVenue ? `${initialVenue.name}` : ''
  );
  const [venueId, setVenueId] = useState(initialVenueId);
  const [isCreatingNew, setIsCreatingNew] = useState(false);
  const [newVenueCity, setNewVenueCity] = useState('');
  const [isVenueDropdownOpen, setIsVenueDropdownOpen] = useState(false);
  const venueWrapperRef = useRef<HTMLDivElement>(null);
  const [date, setDate] = useState('');
  const [timeOfDay, setTimeOfDay] = useState<
    'Morning' | 'Afternoon' | 'Evening' | 'Night' | 'Unknown'>(
    'Night');
  const [description, setDescription] = useState('');
  // Derived: filtered venues for the typeahead
  const filteredVenues = useMemo(() => {
    const q = venueQuery.trim().toLowerCase();
    if (!q) return venues.slice(0, 8);
    return venues.
    filter(
      (v) =>
      v.name.toLowerCase().includes(q) || v.city.toLowerCase().includes(q)
    ).
    slice(0, 8);
  }, [venueQuery, venues]);
  const exactMatch = useMemo(
    () =>
    venues.find(
      (v) => v.name.toLowerCase() === venueQuery.trim().toLowerCase()
    ),
    [venueQuery, venues]
  );
  // Close dropdown on outside click
  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (
      venueWrapperRef.current &&
      !venueWrapperRef.current.contains(e.target as Node))
      {
        setIsVenueDropdownOpen(false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);
  const selectExistingVenue = (id: string, name: string) => {
    setVenueId(id);
    setVenueQuery(name);
    setIsCreatingNew(false);
    setNewVenueCity('');
    setIsVenueDropdownOpen(false);
  };
  const startCreatingNewVenue = () => {
    setVenueId('');
    setIsCreatingNew(true);
    setIsVenueDropdownOpen(false);
  };
  const clearVenueSelection = () => {
    setVenueId('');
    setVenueQuery('');
    setIsCreatingNew(false);
    setNewVenueCity('');
    setIsVenueDropdownOpen(true);
  };
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    // Simulate network delay for realism
    setTimeout(() => {
      if (isCreatingNew) {
        addReport(
          {
            date: new Date(date).toISOString(),
            timeOfDay,
            description,
            venueId: ''
          },
          {
            name: venueQuery.trim(),
            city: newVenueCity.trim(),
            address: 'Address unknown',
            lat: 51.5,
            lng: -0.1
          }
        );
      } else {
        addReport({
          date: new Date(date).toISOString(),
          timeOfDay,
          description,
          venueId
        });
      }
      setIsSubmitting(false);
      setStep(3); // Success step
      toast.success('Report submitted anonymously');
    }, 800);
  };
  // Step 1 advance validation — require either an existing selection or a fully-filled new venue
  const canContinue =
  !!date && (
  venueId && !isCreatingNew ||
  isCreatingNew && venueQuery.trim() && newVenueCity.trim());
  if (step === 3) {
    return (
      <motion.div
        initial={{
          opacity: 0
        }}
        animate={{
          opacity: 1
        }}
        className="flex-grow flex items-center justify-center p-4 bg-slate-50">

        <div className="max-w-md w-full bg-white rounded-2xl p-8 text-center shadow-sm border border-slate-200">
          <div className="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <CheckCircleIcon className="w-8 h-8" />
          </div>
          <h2 className="text-2xl font-bold text-slate-900 mb-4">
            Thank you for sharing
          </h2>
          <p className="text-slate-600 mb-8 leading-relaxed">
            Your report has been submitted anonymously. By sharing your
            experience, you are helping others make informed decisions.
          </p>
          <div className="space-y-3">
            <button
              onClick={() => navigate('/map')}
              className="w-full bg-brand-teal hover:bg-brand-teal-light text-white py-3 rounded-xl font-medium transition-colors">

              Return to Map
            </button>
            <button
              onClick={() => navigate('/about')}
              className="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 py-3 rounded-xl font-medium transition-colors">

              View Support Resources
            </button>
          </div>
        </div>
      </motion.div>);

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
      className="flex-grow bg-slate-50 py-8 sm:py-12">

      <div className="max-w-2xl mx-auto px-4 sm:px-6">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-slate-900 mb-3">
            Share an Experience
          </h1>
          <p className="text-slate-600 text-lg">
            Submit an anonymous report to help the community stay informed.
          </p>
        </div>

        <DisclaimerBanner className="mb-8" />

        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
          {/* Progress Bar */}
          <div className="flex border-b border-slate-100">
            <div
              className={`flex-1 py-4 text-center text-sm font-medium border-b-2 ${step === 1 ? 'border-brand-teal text-brand-teal' : 'border-transparent text-slate-400'}`}>

              1. Location & Time
            </div>
            <div
              className={`flex-1 py-4 text-center text-sm font-medium border-b-2 ${step === 2 ? 'border-brand-teal text-brand-teal' : 'border-transparent text-slate-400'}`}>

              2. Details
            </div>
          </div>

          <form
            onSubmit={
            step === 1 ?
            (e) => {
              e.preventDefault();
              setStep(2);
            } :
            handleSubmit
            }
            className="p-6 sm:p-8">

            {step === 1 &&
            <motion.div
              initial={{
                opacity: 0
              }}
              animate={{
                opacity: 1
              }}
              className="space-y-6">

                <div ref={venueWrapperRef}>
                  <label
                  htmlFor="venue-search"
                  className="block text-sm font-medium text-slate-700 mb-2">

                    Venue
                  </label>
                  <p className="text-xs text-slate-500 mb-3">
                    Start typing to search existing venues, or add a new one if
                    it's not listed.
                  </p>

                  <div className="relative">
                    <SearchIcon className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                    <input
                    id="venue-search"
                    type="text"
                    autoComplete="off"
                    required
                    placeholder="Search for a venue..."
                    value={venueQuery}
                    onFocus={() => setIsVenueDropdownOpen(true)}
                    onChange={(e) => {
                      setVenueQuery(e.target.value);
                      setVenueId('');
                      setIsCreatingNew(false);
                      setIsVenueDropdownOpen(true);
                    }}
                    className="w-full pl-9 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-teal/20 focus:border-brand-teal outline-none" />

                    {venueQuery &&
                  <button
                    type="button"
                    onClick={clearVenueSelection}
                    aria-label="Clear venue"
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 p-1 rounded">

                        <XIcon className="w-4 h-4" />
                      </button>
                  }

                    {isVenueDropdownOpen && !isCreatingNew &&
                  <div className="absolute z-20 mt-2 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                        {filteredVenues.length > 0 &&
                    <ul className="max-h-64 overflow-y-auto py-1">
                            {filteredVenues.map((v) => {
                        const isSelected = v.id === venueId;
                        return (
                          <li key={v.id}>
                                  <button
                              type="button"
                              onClick={() =>
                              selectExistingVenue(v.id, v.name)
                              }
                              className={`w-full text-left px-4 py-2.5 hover:bg-slate-50 transition-colors flex items-center justify-between gap-3 ${isSelected ? 'bg-brand-teal/5' : ''}`}>

                                    <div>
                                      <div className="text-sm font-medium text-slate-900">
                                        {v.name}
                                      </div>
                                      <div className="text-xs text-slate-500">
                                        {v.city}
                                      </div>
                                    </div>
                                    {isSelected &&
                              <CheckIcon className="w-4 h-4 text-brand-teal shrink-0" />
                              }
                                  </button>
                                </li>);

                      })}
                          </ul>
                    }

                        {venueQuery.trim() && !exactMatch &&
                    <div
                      className={
                      filteredVenues.length > 0 ?
                      'border-t border-slate-100' :
                      ''
                      }>

                            <button
                        type="button"
                        onClick={startCreatingNewVenue}
                        className="w-full text-left px-4 py-3 hover:bg-brand-teal/5 transition-colors flex items-center gap-3 text-brand-teal">

                              <span className="bg-brand-teal/10 p-1 rounded">
                                <PlusIcon className="w-3.5 h-3.5" />
                              </span>
                              <span className="text-sm font-medium">
                                Add "{venueQuery.trim()}" as a new venue
                              </span>
                            </button>
                          </div>
                    }

                        {!venueQuery.trim() && filteredVenues.length === 0 &&
                    <div className="px-4 py-6 text-center text-sm text-slate-500">
                            Start typing to search venues.
                          </div>
                    }
                      </div>
                  }
                  </div>

                  {/* Selected existing venue indicator */}
                  {venueId && !isCreatingNew &&
                <div className="mt-3 flex items-center gap-2 text-xs text-slate-500">
                      <CheckIcon className="w-3.5 h-3.5 text-brand-teal" />
                      Existing venue selected
                    </div>
                }

                  {/* New venue — reveal city field */}
                  {isCreatingNew &&
                <div className="mt-4 bg-brand-teal/5 border border-brand-teal/20 rounded-xl p-4 space-y-3">
                      <div className="flex items-start gap-2 text-sm text-brand-teal">
                        <PlusIcon className="w-4 h-4 mt-0.5 shrink-0" />
                        <p>
                          Adding <strong>"{venueQuery.trim()}"</strong> as a new
                          venue. Please confirm its city or town below.
                        </p>
                      </div>
                      <input
                    required
                    type="text"
                    placeholder="City / Town"
                    value={newVenueCity}
                    onChange={(e) => setNewVenueCity(e.target.value)}
                    className="w-full p-3 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-teal/20 focus:border-brand-teal outline-none text-sm" />

                    </div>
                }
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-medium text-slate-700 mb-2">
                      Approximate Date
                    </label>
                    <input
                    required
                    type="date"
                    max={new Date().toISOString().split('T')[0]}
                    value={date}
                    onChange={(e) => setDate(e.target.value)}
                    className="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-teal/20 focus:border-brand-teal outline-none" />

                  </div>
                  <div>
                    <label className="block text-sm font-medium text-slate-700 mb-2">
                      Time of Day
                    </label>
                    <select
                    value={timeOfDay}
                    onChange={(e) => setTimeOfDay(e.target.value as any)}
                    className="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-teal/20 focus:border-brand-teal outline-none">

                      <option value="Morning">Morning</option>
                      <option value="Afternoon">Afternoon</option>
                      <option value="Evening">Evening</option>
                      <option value="Night">Night</option>
                      <option value="Unknown">Not Sure</option>
                    </select>
                  </div>
                </div>

                <div className="pt-4">
                  <button
                  type="submit"
                  disabled={!canContinue}
                  className="w-full bg-slate-900 hover:bg-slate-800 text-white py-3.5 rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">

                    Continue to Details
                  </button>
                </div>
              </motion.div>
            }

            {step === 2 &&
            <motion.div
              initial={{
                opacity: 0
              }}
              animate={{
                opacity: 1
              }}
              className="space-y-6">

                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-2">
                    What happened?
                  </label>
                  <p className="text-xs text-slate-500 mb-3">
                    Please describe the incident.{' '}
                    <strong>
                      Do not include personal names or identifying information
                    </strong>{' '}
                    about yourself or others. Focus on the facts of what
                    occurred.
                  </p>
                  <textarea
                  required
                  rows={6}
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  placeholder="e.g., Left drink unattended for a few minutes. Felt extremely dizzy shortly after..."
                  className="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-teal/20 focus:border-brand-teal outline-none resize-none">
                </textarea>
                </div>

                <div className="bg-brand-amber/10 border border-brand-amber/20 rounded-xl p-4 flex gap-3 items-start">
                  <ShieldAlertIcon className="w-5 h-5 text-brand-amber-dark shrink-0 mt-0.5" />
                  <div className="text-sm text-brand-amber-dark">
                    <strong>Final Reminder:</strong> Submitting this form does
                    not report the incident to the police or the venue. It will
                    be published anonymously on DrinkSafe.
                  </div>
                </div>

                <div className="flex gap-4 pt-4">
                  <button
                  type="button"
                  onClick={() => setStep(1)}
                  className="px-6 py-3.5 rounded-xl font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">

                    Back
                  </button>
                  <button
                  type="submit"
                  disabled={isSubmitting}
                  className="flex-1 bg-brand-teal hover:bg-brand-teal-light text-white py-3.5 rounded-xl font-medium transition-colors disabled:opacity-70">

                    {isSubmitting ? 'Submitting...' : 'Submit Anonymously'}
                  </button>
                </div>
              </motion.div>
            }
          </form>
        </div>
      </div>
    </motion.div>);

};

import { formatDistanceToNow, isAfter, subDays, subMonths } from 'date-fns';
import { motion } from 'framer-motion';
import L from 'leaflet';
import {
  SearchIcon,
  FilterIcon,
  MapPinIcon,
  ClockIcon,
  ChevronRightIcon } from
'lucide-react';
import React, { useEffect, useMemo, useState } from 'react';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import { Link } from 'react-router-dom';
import { useReports } from '../context/ReportContext';
// Custom Map Marker Icon
const customIcon = L.divIcon({
  className: 'custom-marker',
  html: `<div class="w-6 h-6 bg-brand-amber rounded-full border-2 border-white shadow-md flex items-center justify-center">
          <div class="w-2 h-2 bg-white rounded-full"></div>
         </div>`,
  iconSize: [24, 24],
  iconAnchor: [12, 12],
  popupAnchor: [0, -12]
});
// Component to handle map centering when search changes
const MapUpdater: React.FC<{
  center: [number, number];
}> = ({ center }) => {
  const map = useMap();
  useEffect(() => {
    map.setView(center, map.getZoom());
  }, [center, map]);

  return null;
};
export const MapPage: React.FC = () => {
  const { venues, populatedReports, searchQuery, setSearchQuery } = useReports();
  const [dateFilter, setDateFilter] = useState<string>('all');
  const [isMobileListOpen, setIsMobileListOpen] = useState(false);
  const filteredReports = useMemo(() => {
    let filtered = populatedReports;

    // Search filter
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(
        (r) =>
        r.venue.name.toLowerCase().includes(query) ||
        r.venue.city.toLowerCase().includes(query)
      );
    }

    // Date filter
    const now = new Date();

    if (dateFilter === '7days') {
      filtered = filtered.filter((r) =>
      isAfter(new Date(r.date), subDays(now, 7))
      );
    } else if (dateFilter === '30days') {
      filtered = filtered.filter((r) =>
      isAfter(new Date(r.date), subDays(now, 30))
      );
    } else if (dateFilter === '6months') {
      filtered = filtered.filter((r) =>
      isAfter(new Date(r.date), subMonths(now, 6))
      );
    }

    return filtered;
  }, [populatedReports, searchQuery, dateFilter]);
  // Get unique venues from filtered reports to show on map
  const activeVenues = useMemo(() => {
    const venueIds = new Set(filteredReports.map((r) => r.venueId));

    return venues.filter((v) => venueIds.has(v.id));
  }, [filteredReports, venues]);
  // Default center (UK)
  const mapCenter: [number, number] =
  activeVenues.length > 0 ?
  [activeVenues[0].lat, activeVenues[0].lng] :
  [53.4808, -2.2426]; // Manchester default

  return (
    <motion.div
      initial={{
        opacity: 0
      }}
      animate={{
        opacity: 1
      }}
      className="flex-grow flex flex-col md:flex-row h-[calc(100vh-104px)]" // Adjust based on header+strip height
    >
      {/* Sidebar / List View */}
      <div
        className={`
        ${isMobileListOpen ? 'flex' : 'hidden'} 
        md:flex flex-col w-full md:w-96 lg:w-[400px] bg-white border-r border-slate-200 z-10 h-full
        absolute md:relative top-0 left-0
      `}>
        
        <div className="p-4 border-b border-slate-200 bg-white sticky top-0 z-20">
          <div className="flex justify-between items-center mb-4 md:hidden">
            <h2 className="font-semibold text-lg">Reports List</h2>
            <button
              onClick={() => setIsMobileListOpen(false)}
              className="text-brand-teal font-medium">
              
              Show Map
            </button>
          </div>

          <div className="relative mb-3">
            <SearchIcon className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
            <input
              type="text"
              placeholder="Search city or venue..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-9 pr-3 py-2 bg-slate-100 border-transparent rounded-lg text-sm focus:bg-white focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/20 transition-all" />
            
          </div>

          <div className="flex items-center gap-2">
            <FilterIcon className="w-4 h-4 text-slate-400" />
            <select
              value={dateFilter}
              onChange={(e) => setDateFilter(e.target.value)}
              className="text-sm bg-transparent border-none text-slate-700 focus:ring-0 cursor-pointer font-medium">
              
              <option value="all">All Time</option>
              <option value="7days">Last 7 Days</option>
              <option value="30days">Last 30 Days</option>
              <option value="6months">Last 6 Months</option>
            </select>
          </div>
        </div>

        <div className="flex-grow overflow-y-auto p-4 space-y-3 bg-slate-50">
          {filteredReports.length === 0 ?
          <div className="text-center py-10 text-slate-500">
              <p>No reports found matching your filters.</p>
              <button
              onClick={() => {
                setSearchQuery('');
                setDateFilter('all');
              }}
              className="text-brand-teal font-medium mt-2 hover:underline">
              
                Clear filters
              </button>
            </div> :

          filteredReports.map((report) =>
          <Link
            key={report.id}
            to={`/venue/${report.venueId}`}
            className="block bg-white border border-slate-200 rounded-lg p-4 hover:border-brand-teal/40 hover:shadow-sm transition-all">
            
                <h3 className="font-semibold text-slate-900 mb-1">
                  {report.venue.name}
                </h3>
                <div className="flex items-center gap-3 text-xs text-slate-500 mb-2">
                  <span className="flex items-center gap-1">
                    <MapPinIcon className="w-3 h-3" /> {report.venue.city}
                  </span>
                  <span className="flex items-center gap-1">
                    <ClockIcon className="w-3 h-3" />{' '}
                    {formatDistanceToNow(new Date(report.date))} ago
                  </span>
                </div>
                <p className="text-sm text-slate-600 line-clamp-2">
                  "{report.description}"
                </p>
              </Link>
          )
          }
        </div>
      </div>

      {/* Map View */}
      <div
        className={`flex-grow relative ${isMobileListOpen ? 'hidden md:block' : 'block'}`}>
        
        <button
          className="md:hidden absolute bottom-6 left-1/2 -translate-x-1/2 z-[1000] bg-brand-teal text-white px-6 py-3 rounded-full font-medium shadow-lg flex items-center gap-2"
          onClick={() => setIsMobileListOpen(true)}>
          
          <FilterIcon className="w-4 h-4" /> View {filteredReports.length}{' '}
          Reports
        </button>

        <MapContainer
          center={mapCenter}
          zoom={6}
          className="w-full h-full z-0"
          zoomControl={false}>
          
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
            url="https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png" />
          
          <MapUpdater center={mapCenter} />

          {activeVenues.map((venue) => {
            const venueReports = filteredReports.filter(
              (r) => r.venueId === venue.id
            );

            return (
              <Marker
                key={venue.id}
                position={[venue.lat, venue.lng]}
                icon={customIcon}>
                
                <Popup className="custom-popup">
                  <div className="p-1 min-w-[200px]">
                    <h3 className="font-bold text-slate-900 mb-1">
                      {venue.name}
                    </h3>
                    <p className="text-xs text-slate-500 mb-3">{venue.city}</p>
                    <div className="bg-brand-amber/10 text-brand-amber-dark text-xs font-semibold px-2 py-1 rounded mb-3 inline-block">
                      {venueReports.length} Report
                      {venueReports.length !== 1 ? 's' : ''}
                    </div>
                    <Link
                      to={`/venue/${venue.id}`}
                      className="flex items-center justify-between w-full bg-slate-900 hover:bg-slate-800 text-white text-sm py-2 px-3 rounded transition-colors">
                      
                      View Details <ChevronRightIcon className="w-4 h-4" />
                    </Link>
                  </div>
                </Popup>
              </Marker>);

          })}
        </MapContainer>
      </div>
    </motion.div>);

};
import React, { useMemo, useState, createContext, useContext } from 'react';
import { Venue, Report, PopulatedReport } from '../types';
import { mockVenues, mockReports } from '../data/mockData';
interface ReportContextType {
  venues: Venue[];
  reports: Report[];
  populatedReports: PopulatedReport[];
  addReport: (
  report: Omit<Report, 'id' | 'createdAt'>,
  newVenue?: Omit<Venue, 'id'>)
  => void;
  searchQuery: string;
  setSearchQuery: (query: string) => void;
}
const ReportContext = createContext<ReportContextType | undefined>(undefined);
export const ReportProvider: React.FC<{
  children: React.ReactNode;
}> = ({ children }) => {
  const [venues, setVenues] = useState<Venue[]>(mockVenues);
  const [reports, setReports] = useState<Report[]>(mockReports);
  const [searchQuery, setSearchQuery] = useState('');
  const populatedReports = useMemo(() => {
    return reports.
    map((report) => ({
      ...report,
      venue: venues.find((v) => v.id === report.venueId)!
    })).
    sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
  }, [reports, venues]);
  const addReport = (
  reportData: Omit<Report, 'id' | 'createdAt'>,
  newVenueData?: Omit<Venue, 'id'>) =>
  {
    let venueId = reportData.venueId;
    if (newVenueData) {
      const newVenue: Venue = {
        ...newVenueData,
        id: `v${Date.now()}`
      };
      setVenues((prev) => [...prev, newVenue]);
      venueId = newVenue.id;
    }
    const newReport: Report = {
      ...reportData,
      venueId,
      id: `r${Date.now()}`,
      createdAt: new Date().toISOString()
    };
    setReports((prev) => [newReport, ...prev]);
  };
  return (
    <ReportContext.Provider
      value={{
        venues,
        reports,
        populatedReports,
        addReport,
        searchQuery,
        setSearchQuery
      }}>
      
      {children}
    </ReportContext.Provider>);

};
export const useReports = () => {
  const context = useContext(ReportContext);
  if (context === undefined) {
    throw new Error('useReports must be used within a ReportProvider');
  }
  return context;
};
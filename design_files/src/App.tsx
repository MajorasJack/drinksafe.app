import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { Toaster } from 'sonner';
import { ReportProvider } from './src/context/ReportContext';
import { Layout } from './src/components/Layout';
import { Home } from './src/pages/Home';
import { MapPage } from './src/pages/MapPage';
import { VenueDetail } from './src/pages/VenueDetail';
import { SubmitReport } from './src/pages/SubmitReport';
import { About } from './src/pages/About';
export function App() {
  return (
    <ReportProvider>
      <BrowserRouter>
        <Toaster position="top-center" richColors />
        <Routes>
          <Route path="/" element={<Layout />}>
            <Route index element={<Home />} />
            <Route path="map" element={<MapPage />} />
            <Route path="venue/:id" element={<VenueDetail />} />
            <Route path="report" element={<SubmitReport />} />
            <Route path="about" element={<About />} />
          </Route>
        </Routes>
      </BrowserRouter>
    </ReportProvider>);

}
import React from 'react';
import { Outlet } from 'react-router-dom';
import { Header } from './Header';
import { Footer } from './Footer';
import { PoliceStrip } from './PoliceStrip';
export const Layout: React.FC = () => {
  return (
    <div className="min-h-screen flex flex-col font-sans">
      <PoliceStrip />
      <Header />
      <main className="flex-grow flex flex-col">
        <Outlet />
      </main>
      <Footer />
    </div>);

};
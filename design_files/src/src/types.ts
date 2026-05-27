export interface Venue {
  id: string;
  name: string;
  city: string;
  address: string;
  lat: number;
  lng: number;
}

export interface Report {
  id: string;
  venueId: string;
  date: string; // ISO string
  timeOfDay: 'Morning' | 'Afternoon' | 'Evening' | 'Night' | 'Unknown';
  description: string;
  createdAt: string; // ISO string
}

export interface PopulatedReport extends Report {
  venue: Venue;
}
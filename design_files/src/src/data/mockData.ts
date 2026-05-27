import { Venue, Report } from '../types';
import { subDays, subMonths } from 'date-fns';

export const mockVenues: Venue[] = [
{
  id: 'v1',
  name: 'The Neon Lounge',
  city: 'London',
  address: '124 Shoreditch High St, London E1 6JE',
  lat: 51.5255,
  lng: -0.0782
},
{
  id: 'v2',
  name: 'Club Vertex',
  city: 'Manchester',
  address: '15 Peter St, Manchester M2 5QR',
  lat: 53.478,
  lng: -2.247
},
{
  id: 'v3',
  name: 'Harbour Lights Bar',
  city: 'Bristol',
  address: '10 Welsh Back, Bristol BS1 4SP',
  lat: 51.452,
  lng: -2.593
},
{
  id: 'v4',
  name: 'The Old Foundry',
  city: 'Leeds',
  address: 'Water Ln, Leeds LS11 5QZ',
  lat: 53.7925,
  lng: -1.551
},
{
  id: 'v5',
  name: 'Symphony Club',
  city: 'Birmingham',
  address: 'Broad St, Birmingham B1 2EA',
  lat: 52.477,
  lng: -1.913
},
{
  id: 'v6',
  name: 'The Velvet Room',
  city: 'London',
  address: 'Soho Square, London W1D 3QP',
  lat: 51.515,
  lng: -0.132
},
{
  id: 'v7',
  name: 'Northern Quarter Pub',
  city: 'Manchester',
  address: 'Thomas St, Manchester M4 1ER',
  lat: 53.4835,
  lng: -2.236
}];


const now = new Date();

export const mockReports: Report[] = [
{
  id: 'r1',
  venueId: 'v1',
  date: subDays(now, 2).toISOString(),
  timeOfDay: 'Night',
  description:
  'Left drink unattended for a few minutes. Felt extremely dizzy and nauseous shortly after finishing it. Friends had to take me home.',
  createdAt: subDays(now, 1).toISOString()
},
{
  id: 'r2',
  venueId: 'v1',
  date: subDays(now, 14).toISOString(),
  timeOfDay: 'Evening',
  description:
  'Noticed a strange powder around the rim of my glass after returning from the bathroom. Did not drink it, reported to bar staff who replaced it.',
  createdAt: subDays(now, 13).toISOString()
},
{
  id: 'r3',
  venueId: 'v2',
  date: subDays(now, 5).toISOString(),
  timeOfDay: 'Night',
  description:
  'Felt suddenly paralyzed and unable to speak clearly after only one drink. Woke up in hospital. Suspect drink was spiked.',
  createdAt: subDays(now, 4).toISOString()
},
{
  id: 'r4',
  venueId: 'v3',
  date: subDays(now, 1).toISOString(),
  timeOfDay: 'Night',
  description:
  'Group of friends all felt unusually intoxicated and sick after a round of shots. One friend collapsed.',
  createdAt: subDays(now, 0).toISOString()
},
{
  id: 'r5',
  venueId: 'v4',
  date: subMonths(now, 1).toISOString(),
  timeOfDay: 'Evening',
  description:
  'Drink tasted unusually salty. Stopped drinking it immediately but still felt lightheaded.',
  createdAt: subMonths(now, 1).toISOString()
},
{
  id: 'r6',
  venueId: 'v2',
  date: subDays(now, 20).toISOString(),
  timeOfDay: 'Night',
  description:
  'Lost memory of the entire evening after 10 PM. Friends said I was acting completely out of character and lethargic.',
  createdAt: subDays(now, 19).toISOString()
},
{
  id: 'r7',
  venueId: 'v5',
  date: subDays(now, 8).toISOString(),
  timeOfDay: 'Night',
  description:
  'Felt a sharp scratch on my arm in a crowded area of the dancefloor. Felt unwell and left immediately.',
  createdAt: subDays(now, 7).toISOString()
},
{
  id: 'r8',
  venueId: 'v6',
  date: subDays(now, 3).toISOString(),
  timeOfDay: 'Night',
  description:
  'Drink was definitely tampered with. Saw someone hovering near our table. We left the drinks and informed security.',
  createdAt: subDays(now, 2).toISOString()
},
{
  id: 'r9',
  venueId: 'v7',
  date: subDays(now, 45).toISOString(),
  timeOfDay: 'Evening',
  description:
  'Became violently ill after one glass of wine. Suspect it was spiked as I had eaten well and not drank anything else.',
  createdAt: subDays(now, 44).toISOString()
},
{
  id: 'r10',
  venueId: 'v1',
  date: subMonths(now, 2).toISOString(),
  timeOfDay: 'Night',
  description:
  'Complete blackout after two drinks. Woke up at home with no recollection of how I got there.',
  createdAt: subMonths(now, 2).toISOString()
},
{
  id: 'r11',
  venueId: 'v3',
  date: subDays(now, 10).toISOString(),
  timeOfDay: 'Afternoon',
  description:
  'Day drinking event. Left drink to go to the toilet. Came back and it tasted metallic. Did not finish it.',
  createdAt: subDays(now, 9).toISOString()
},
{
  id: 'r12',
  venueId: 'v5',
  date: subDays(now, 60).toISOString(),
  timeOfDay: 'Night',
  description:
  'Felt extremely confused and disoriented. Security were helpful and called a taxi for me.',
  createdAt: subDays(now, 59).toISOString()
}];
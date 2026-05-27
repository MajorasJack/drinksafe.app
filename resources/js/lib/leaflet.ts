import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix default marker icons issue in Leaflet with bundlers
// The default icon paths don't work correctly with Vite
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Remove the default _getIconUrl method

delete (L.Icon.Default.prototype as any)._getIconUrl;

// Set the correct icon URLs
L.Icon.Default.mergeOptions({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIcon2x,
    shadowUrl: markerShadow,
});

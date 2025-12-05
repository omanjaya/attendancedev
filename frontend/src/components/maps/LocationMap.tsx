import { useEffect } from 'react';
import { MapContainer, TileLayer, Marker, Circle, Popup, useMap } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix for default marker icons in React Leaflet
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

// Custom icons
const userIcon = new L.Icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

const officeIcon = new L.Icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

interface LocationMapProps {
  userLocation: {
    latitude: number;
    longitude: number;
  };
  officeLocation?: {
    latitude: number;
    longitude: number;
    name: string;
    address?: string;
    radius: number; // in meters
  };
  isWithinRadius?: boolean;
  distance?: number; // in meters
  className?: string;
}

// Component to auto-fit bounds
function FitBounds({ userLocation, officeLocation }: { userLocation: [number, number]; officeLocation?: [number, number] }) {
  const map = useMap();

  useEffect(() => {
    if (officeLocation) {
      const bounds = L.latLngBounds([userLocation, officeLocation]);
      map.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
    } else {
      map.setView(userLocation, 15);
    }
  }, [map, userLocation, officeLocation]);

  return null;
}

export function LocationMap({ userLocation, officeLocation, isWithinRadius, distance, className }: LocationMapProps) {
  const userPos: [number, number] = [userLocation.latitude, userLocation.longitude];
  const officePos: [number, number] | undefined =
    officeLocation &&
      typeof officeLocation.latitude === 'number' &&
      typeof officeLocation.longitude === 'number'
      ? [officeLocation.latitude, officeLocation.longitude]
      : undefined;

  return (
    <div className={`relative w-full rounded-2xl overflow-hidden shadow-lg border-2 border-border ${className || 'h-[400px]'}`}>
      <MapContainer
        center={userPos}
        zoom={15}
        style={{ height: '100%', width: '100%' }}
        zoomControl={true}
      >
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />

        {/* User Location Marker */}
        <Marker position={userPos} icon={userIcon}>
          <Popup>
            <div className="text-sm">
              <p className="font-semibold mb-1">📍 Lokasi Anda</p>
              <p className="text-xs text-gray-600">
                {userLocation.latitude.toFixed(6)}, {userLocation.longitude.toFixed(6)}
              </p>
              {distance !== undefined && (
                <p className="text-xs mt-2">
                  <span className="font-medium">Jarak ke kantor:</span>{' '}
                  <span className={isWithinRadius ? 'text-emerald-600 font-bold' : 'text-red-600 font-bold'}>
                    {distance.toFixed(0)}m
                  </span>
                </p>
              )}
            </div>
          </Popup>
        </Marker>

        {/* Office Location and Radius */}
        {officePos && officeLocation && (
          <>
            <Marker position={officePos} icon={officeIcon}>
              <Popup>
                <div className="text-sm">
                  <p className="font-semibold mb-1">🏢 {officeLocation.name}</p>
                  {officeLocation.address && (
                    <p className="text-xs text-gray-600 mb-2">{officeLocation.address}</p>
                  )}
                  <p className="text-xs">
                    <span className="font-medium">Radius:</span> {officeLocation.radius}m
                  </p>
                </div>
              </Popup>
            </Marker>

            {/* Radius Circle */}
            <Circle
              center={officePos}
              radius={officeLocation.radius}
              pathOptions={{
                color: isWithinRadius ? '#10b981' : '#ef4444',
                fillColor: isWithinRadius ? '#10b981' : '#ef4444',
                fillOpacity: 0.1,
                weight: 2,
              }}
            />
          </>
        )}

        <FitBounds userLocation={userPos} officeLocation={officePos} />
      </MapContainer>

      {/* Status Badge Overlay */}
      {isWithinRadius !== undefined && (
        <div className="absolute top-4 left-4 z-[1000] bg-white dark:bg-gray-900 rounded-xl px-4 py-2 shadow-lg border border-border">
          <p className="text-xs font-medium flex items-center gap-2">
            <span className={`w-2 h-2 rounded-full ${isWithinRadius ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'}`} />
            {isWithinRadius ? '✓ Dalam Radius' : '✗ Di Luar Radius'}
          </p>
        </div>
      )}

      {/* Distance Badge */}
      {distance !== undefined && (
        <div className="absolute bottom-4 left-1/2 -translate-x-1/2 z-[1000] bg-white dark:bg-gray-900 rounded-full px-4 py-2 shadow-lg border border-border">
          <p className="text-sm font-bold">
            {distance.toFixed(0)} meter
          </p>
        </div>
      )}
    </div>
  );
}

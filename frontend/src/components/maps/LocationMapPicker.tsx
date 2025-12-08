import { useState, useRef, useEffect, useMemo } from 'react';
import { MapContainer, TileLayer, Marker, useMapEvents, useMap } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Search, MapPin, Loader2, X, Navigation } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

// Fix for default marker icons in React Leaflet
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

// Custom draggable marker icon
const draggableIcon = new L.Icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

interface LocationMapPickerProps {
  latitude: number;
  longitude: number;
  onLocationChange: (lat: number, lng: number, address?: string) => void;
  height?: string;
  className?: string;
}

interface SearchResult {
  place_id: number;
  display_name: string;
  lat: string;
  lon: string;
  address?: {
    road?: string;
    suburb?: string;
    city?: string;
    state?: string;
    country?: string;
  };
}

// Draggable marker component
function DraggableMarker({
  position,
  onPositionChange,
}: {
  position: [number, number];
  onPositionChange: (lat: number, lng: number) => void;
}) {
  const markerRef = useRef<L.Marker>(null);

  const eventHandlers = useMemo(
    () => ({
      dragend() {
        const marker = markerRef.current;
        if (marker != null) {
          const { lat, lng } = marker.getLatLng();
          onPositionChange(lat, lng);
        }
      },
    }),
    [onPositionChange]
  );

  return (
    <Marker
      draggable={true}
      eventHandlers={eventHandlers}
      position={position}
      ref={markerRef}
      icon={draggableIcon}
    />
  );
}

// Click handler component
function MapClickHandler({ onLocationClick }: { onLocationClick: (lat: number, lng: number) => void }) {
  useMapEvents({
    click(e) {
      onLocationClick(e.latlng.lat, e.latlng.lng);
    },
  });
  return null;
}

// Component to update map view
function MapViewController({ center, zoom }: { center: [number, number]; zoom?: number }) {
  const map = useMap();

  useEffect(() => {
    map.setView(center, zoom || map.getZoom());
  }, [center, zoom, map]);

  return null;
}

export function LocationMapPicker({
  latitude,
  longitude,
  onLocationChange,
  height = '400px',
  className,
}: LocationMapPickerProps) {
  const [position, setPosition] = useState<[number, number]>([latitude, longitude]);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<SearchResult[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [showResults, setShowResults] = useState(false);
  const [isGettingLocation, setIsGettingLocation] = useState(false);
  const searchTimeoutRef = useRef<NodeJS.Timeout | undefined>(undefined);

  // Update position when props change
  useEffect(() => {
    setPosition([latitude, longitude]);
  }, [latitude, longitude]);

  // Geocoding search function (Nominatim API)
  const searchLocation = async (query: string) => {
    if (!query || query.length < 3) {
      setSearchResults([]);
      return;
    }

    setIsSearching(true);
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(
          query
        )}&limit=5&addressdetails=1`,
        {
          headers: {
            'Accept-Language': 'id',
          },
        }
      );
      const data: SearchResult[] = await response.json();
      setSearchResults(data);
      setShowResults(true);
    } catch (error) {
      console.error('Error searching location:', error);
      setSearchResults([]);
    } finally {
      setIsSearching(false);
    }
  };

  // Reverse geocoding to get address from coordinates
  const reverseGeocode = async (lat: number, lng: number) => {
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`,
        {
          headers: {
            'Accept-Language': 'id',
          },
        }
      );
      const data = await response.json();
      return data.display_name || '';
    } catch (error) {
      console.error('Error reverse geocoding:', error);
      return '';
    }
  };

  // Handle search input change with debounce
  const handleSearchChange = (value: string) => {
    setSearchQuery(value);

    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current);
    }

    searchTimeoutRef.current = setTimeout(() => {
      searchLocation(value);
    }, 500);
  };

  // Handle selecting a search result
  const handleSelectResult = async (result: SearchResult) => {
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);
    setPosition([lat, lng]);
    setSearchQuery(result.display_name);
    setShowResults(false);
    onLocationChange(lat, lng, result.display_name);
  };

  // Handle marker position change
  const handlePositionChange = async (lat: number, lng: number) => {
    setPosition([lat, lng]);
    const address = await reverseGeocode(lat, lng);
    onLocationChange(lat, lng, address);
    if (address) {
      setSearchQuery(address);
    }
  };

  // Get current location
  const getCurrentLocation = () => {
    setIsGettingLocation(true);
    if ('geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition(
        async (position) => {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          setPosition([lat, lng]);
          const address = await reverseGeocode(lat, lng);
          onLocationChange(lat, lng, address);
          if (address) {
            setSearchQuery(address);
          }
          setIsGettingLocation(false);
        },
        (error) => {
          console.error('Error getting location:', error);
          setIsGettingLocation(false);
        }
      );
    } else {
      setIsGettingLocation(false);
    }
  };

  return (
    <div className={cn('space-y-3', className)}>
      {/* Search Box */}
      <div className="relative">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Cari lokasi (contoh: SMP 1 Saraswati)..."
            value={searchQuery}
            onChange={(e) => handleSearchChange(e.target.value)}
            onFocus={() => searchResults.length > 0 && setShowResults(true)}
            className="pl-9 pr-24"
          />
          <div className="absolute right-1 top-1/2 -translate-y-1/2 flex gap-1">
            {searchQuery && (
              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="h-7 px-2"
                onClick={() => {
                  setSearchQuery('');
                  setSearchResults([]);
                  setShowResults(false);
                }}
              >
                <X className="h-3 w-3" />
              </Button>
            )}
            <Button
              type="button"
              variant="ghost"
              size="sm"
              className="h-7 px-2"
              onClick={getCurrentLocation}
              disabled={isGettingLocation}
            >
              {isGettingLocation ? (
                <Loader2 className="h-3 w-3 animate-spin" />
              ) : (
                <Navigation className="h-3 w-3" />
              )}
            </Button>
          </div>
        </div>

        {/* Search Results Dropdown */}
        {showResults && searchResults.length > 0 && (
          <Card className="absolute top-full left-0 right-0 mt-1 z-50 max-h-[300px] overflow-y-auto">
            <CardContent className="p-0">
              {isSearching ? (
                <div className="p-4 flex items-center justify-center gap-2 text-sm text-muted-foreground">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Mencari...
                </div>
              ) : (
                <div className="divide-y divide-border">
                  {searchResults.map((result) => (
                    <button
                      key={result.place_id}
                      type="button"
                      className="w-full text-left p-3 hover:bg-muted/50 transition-colors flex items-start gap-2"
                      onClick={() => handleSelectResult(result)}
                    >
                      <MapPin className="h-4 w-4 mt-0.5 text-primary flex-shrink-0" />
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium truncate">{result.display_name}</p>
                        {result.address && (
                          <p className="text-xs text-muted-foreground truncate">
                            {[
                              result.address.road,
                              result.address.suburb,
                              result.address.city,
                              result.address.state,
                            ]
                              .filter(Boolean)
                              .join(', ')}
                          </p>
                        )}
                      </div>
                    </button>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        )}
      </div>

      {/* Map */}
      <div
        className="relative w-full rounded-xl overflow-hidden shadow-md border border-border"
        style={{ height }}
      >
        <MapContainer
          center={position}
          zoom={15}
          style={{ height: '100%', width: '100%' }}
          zoomControl={true}
        >
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          <DraggableMarker position={position} onPositionChange={handlePositionChange} />
          <MapClickHandler onLocationClick={handlePositionChange} />
          <MapViewController center={position} />
        </MapContainer>

        {/* Instructions Overlay */}
        <div className="absolute bottom-4 left-1/2 -translate-x-1/2 z-[1000] bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm rounded-full px-4 py-2 shadow-lg border border-border">
          <p className="text-xs font-medium text-center flex items-center gap-2">
            <MapPin className="h-3 w-3 text-primary" />
            Drag pin atau klik map untuk memilih lokasi
          </p>
        </div>
      </div>

      {/* Current Coordinates */}
      <div className="flex items-center justify-between text-xs text-muted-foreground bg-muted/30 rounded-lg px-3 py-2">
        <span className="font-medium">Koordinat:</span>
        <span className="font-mono">
          {position[0].toFixed(6)}, {position[1].toFixed(6)}
        </span>
      </div>
    </div>
  );
}

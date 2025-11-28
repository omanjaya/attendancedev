// Location Types

export interface Location {
  id: string;
  name: string;
  address: string;
  latitude: number;
  longitude: number;
  radius_meters: number;
  wifi_ssid?: string;
  is_active: boolean;
  metadata?: {
    description?: string;
    type?: LocationType;
    capacity?: number;
    contact_person?: string;
    contact_phone?: string;
  };
  employee_count?: number;
  created_at: string;
  updated_at: string;
}

export type LocationType = 'office' | 'school' | 'factory' | 'warehouse' | 'remote' | 'other';

export interface LocationFormData {
  name: string;
  address: string;
  latitude: number;
  longitude: number;
  radius_meters: number;
  wifi_ssid?: string;
  is_active: boolean;
  description?: string;
  type?: LocationType;
}

export interface LocationStatistics {
  total_locations: number;
  active_locations: number;
  inactive_locations: number;
  total_employees_assigned: number;
  average_radius: number;
  locations_with_wifi: number;
}

// Labels
export const locationTypeLabels: Record<LocationType, string> = {
  office: 'Kantor',
  school: 'Sekolah',
  factory: 'Pabrik',
  warehouse: 'Gudang',
  remote: 'Remote',
  other: 'Lainnya',
};

export const locationTypeColors: Record<LocationType, string> = {
  office: '#3B82F6',
  school: '#10B981',
  factory: '#F59E0B',
  warehouse: '#8B5CF6',
  remote: '#EC4899',
  other: '#6B7280',
};

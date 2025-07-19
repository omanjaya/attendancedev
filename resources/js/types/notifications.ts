// Notification Types
export interface Notification {
  id: string
  type: string
  notifiable_type: string
  notifiable_id: string
  title: string
  message: string
  data: Record<string, any>
  priority: 'low' | 'normal' | 'high' | 'urgent'
  read_at: string | null
  created_at: string
  updated_at: string

  // Additional metadata
  action_url?: string
  action_text?: string
  icon?: string
  image?: string
  category?: string
}

export interface NotificationPreferences {
  id: string
  user_id: string
  email_enabled: boolean
  push_enabled: boolean
  sound_enabled: boolean
  desktop_enabled: boolean
  digest_frequency: 'immediate' | 'hourly' | 'daily' | 'weekly' | 'never'
  quiet_hours_start?: string
  quiet_hours_end?: string
  weekend_notifications: boolean

  // Type-specific preferences
  types: Record<string, NotificationTypePreference>

  created_at: string
  updated_at: string
}

export interface NotificationTypePreference {
  enabled: boolean
  email: boolean
  push: boolean
  toast: boolean
  sound: boolean
}

export interface ToastNotification {
  id: string
  type: 'success' | 'error' | 'warning' | 'info' | 'security'
  title: string
  message: string
  duration?: number // in milliseconds, 0 for persistent
  action?: {
    text: string
    handler: () => void
  }
  timestamp: string
}

export interface NotificationChannel {
  name: string
  enabled: boolean
  config: Record<string, any>
}

export interface NotificationTemplate {
  id: string
  name: string
  type: string
  subject: string
  content: string
  variables: string[]
  created_at: string
  updated_at: string
}

export interface NotificationDigest {
  id: string
  user_id: string
  frequency: NotificationPreferences['digest_frequency']
  notifications: Notification[]
  sent_at: string | null
  created_at: string
}

export interface NotificationStats {
  total: number
  unread: number
  by_type: Record<string, number>
  by_priority: Record<string, number>
  today: number
  this_week: number
  this_month: number
}

export interface PushSubscription {
  id: string
  user_id: string
  endpoint: string
  public_key: string
  auth_token: string
  device_name: string
  platform: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface NotificationHistory {
  id: string
  notification_id: string
  channel: string
  status: 'sent' | 'failed' | 'pending'
  sent_at: string | null
  error_message?: string
  metadata: Record<string, any>
}

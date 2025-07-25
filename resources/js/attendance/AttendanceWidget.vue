<template>
  <div class="attendance-widget">
    <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
      <div class="flex flex-col space-y-1.5 p-6">
        <h3 class="text-2xl font-semibold leading-none tracking-tight">
          Attendance Status
        </h3>
      </div>
      <div class="p-6 pt-0">
        <div v-if="loading" class="flex justify-center">
          <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-primary" role="status">
            <span class="sr-only">Loading...</span>
          </div>
        </div>
        <div v-else-if="attendanceStatus">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <p class="text-sm">
                <span class="font-medium">Status:</span> {{ attendanceStatus.status }}
              </p>
              <p class="text-sm">
                <span class="font-medium">Check-in:</span>
                {{ attendanceStatus.check_in_time || 'Not checked in' }}
              </p>
            </div>
            <div class="space-y-2">
              <p class="text-sm">
                <span class="font-medium">Working Hours:</span>
                {{ attendanceStatus.working_hours || 0 }}h
              </p>
              <p class="text-sm">
                <span class="font-medium">Check-out:</span>
                {{ attendanceStatus.check_out_time || 'Not checked out' }}
              </p>
            </div>
          </div>
          <div class="mt-6">
            <button
              v-if="!attendanceStatus.check_in_time"
              class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground ring-offset-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
              :disabled="processing"
              @click="startCheckIn"
            >
              Check In
            </button>
            <button
              v-else-if="!attendanceStatus.check_out_time"
              class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground ring-offset-background transition-colors hover:bg-destructive/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
              :disabled="processing"
              @click="startCheckOut"
            >
              Check Out
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAttendance } from '@/composables/useAttendance'

const { attendanceStatus, loading, processing, fetchAttendanceStatus } = useAttendance()

onMounted(() => {
  fetchAttendanceStatus()
})

const startCheckIn = () => {
  // This will trigger face detection and location verification
  console.log('Starting check-in process...')
}

const startCheckOut = () => {
  // This will trigger face detection and location verification
  console.log('Starting check-out process...')
}
</script>

<style scoped>
.attendance-widget {
  max-width: 600px;
  margin: 0 auto;
}
</style>

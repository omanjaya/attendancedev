<template>
  <div class="face-detection-component">
    <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
      <div class="flex flex-col space-y-1.5 p-6">
        <h3 class="text-2xl font-semibold leading-none tracking-tight">
          Face Detection
        </h3>
      </div>
      <div class="p-6 pt-0">
        <div v-if="!cameraActive" class="text-center">
          <button
            class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground ring-offset-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
            @click="startCamera"
          >
            <i class="ti ti-camera mr-2" /> Start Camera
          </button>
        </div>

        <div v-else class="camera-container">
          <video
            ref="videoElement"
            autoplay
            muted
            playsinline
            class="camera-video"
          />
          <canvas ref="canvasElement" class="camera-overlay" />

          <div v-if="gesturePrompt" class="gesture-prompt">
            <div
              class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200"
            >
              <div class="font-medium">
                Please {{ gesturePrompt.action }}
              </div>
              <div class="mt-2 h-2 w-full rounded-full bg-blue-200 dark:bg-blue-700">
                <div
                  class="h-2 rounded-full bg-blue-600 transition-all duration-300 dark:bg-blue-400"
                  :style="{ width: gestureProgress + '%' }"
                />
              </div>
            </div>
          </div>

          <div class="camera-controls mt-6 space-x-2 text-center">
            <button
              class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-md border border-input bg-background px-4 py-2 text-sm font-medium ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
              @click="stopCamera"
            >
              Stop Camera
            </button>
            <button
              v-if="faceDetected && !processing"
              class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground ring-offset-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
              @click="captureAndVerify"
            >
              Verify Face
            </button>
          </div>
        </div>

        <div
          v-if="error"
          class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200"
        >
          {{ error }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useFaceDetection } from '@/composables/useFaceDetection'

const emit = defineEmits(['faceVerified', 'faceError'])

const {
  cameraActive,
  faceDetected,
  processing,
  error,
  gesturePrompt,
  gestureProgress,
  videoElement,
  canvasElement,
  startCamera,
  stopCamera,
  captureAndVerify,
} = useFaceDetection()

// Handle component cleanup
onUnmounted(() => {
  if (cameraActive.value) {
    stopCamera()
  }
})
</script>

<style scoped>
.camera-container {
  position: relative;
  max-width: 640px;
  margin: 0 auto;
}

.camera-video {
  width: 100%;
  height: auto;
  border-radius: 8px;
}

.camera-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.gesture-prompt {
  position: absolute;
  top: 10px;
  left: 10px;
  right: 10px;
  z-index: 10;
}

.camera-controls {
  text-align: center;
}
</style>

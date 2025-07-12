<template>
  <div class="face-detection-component">
    <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
      <div class="flex flex-col space-y-1.5 p-6">
        <h3 class="text-2xl font-semibold leading-none tracking-tight">Face Detection</h3>
      </div>
      <div class="p-6 pt-0">
        <div v-if="!cameraActive" class="text-center">
          <button @click="startCamera" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
            <i class="ti ti-camera mr-2"></i> Start Camera
          </button>
        </div>
        
        <div v-else class="camera-container">
          <video 
            ref="videoElement" 
            autoplay 
            muted 
            playsinline
            class="camera-video"
          ></video>
          <canvas 
            ref="canvasElement" 
            class="camera-overlay"
          ></canvas>
          
          <div v-if="gesturePrompt" class="gesture-prompt">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">
              <div class="font-medium">Please {{ gesturePrompt.action }}</div>
              <div class="w-full bg-blue-200 rounded-full h-2 mt-2 dark:bg-blue-700">
                <div 
                  class="bg-blue-600 h-2 rounded-full transition-all duration-300 dark:bg-blue-400" 
                  :style="{ width: gestureProgress + '%' }"
                ></div>
              </div>
            </div>
          </div>
          
          <div class="camera-controls mt-6 text-center space-x-2">
            <button @click="stopCamera" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
              Stop Camera
            </button>
            <button 
              v-if="faceDetected && !processing" 
              @click="captureAndVerify" 
              class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
              Verify Face
            </button>
          </div>
        </div>
        
        <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200 mt-4">
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
  captureAndVerify
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
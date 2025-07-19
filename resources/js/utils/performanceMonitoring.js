/**
 * Performance Monitoring System for Face Detection
 * Tracks system performance, resource usage, and optimization metrics
 */

class PerformanceMonitor {
  constructor() {
    this.metrics = {
      detectionTimes: [],
      memoryUsage: [],
      cpuUsage: [],
      fpsValues: [],
      errorCounts: {},
      sessionStats: {
        startTime: Date.now(),
        totalDetections: 0,
        successfulDetections: 0,
        failedDetections: 0,
        averageConfidence: 0,
      },
    }

    this.observers = []
    this.isMonitoring = false
    this.monitoringInterval = null
    this.performanceObserver = null

    this.thresholds = {
      maxDetectionTime: 1000, // ms
      minFPS: 15,
      maxMemoryUsage: 100, // MB
      maxCPUUsage: 80, // %
      minConfidence: 70, // %
    }

    this.initializeObservers()
  }

  /**
   * Initialize performance observers
   */
  initializeObservers() {
    // Performance Observer for measuring detection times
    if (typeof PerformanceObserver !== 'undefined') {
      this.performanceObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        entries.forEach((entry) => {
          if (entry.name.includes('face-detection')) {
            this.recordDetectionTime(entry.duration)
          }
        })
      })

      try {
        this.performanceObserver.observe({ entryTypes: ['measure'] })
      } catch (error) {
        console.warn('Performance Observer not supported:', error)
      }
    }

    // Memory usage observer
    if (typeof performance.memory !== 'undefined') {
      this.startMemoryMonitoring()
    }
  }

  /**
   * Start monitoring system performance
   */
  startMonitoring() {
    if (this.isMonitoring) return

    this.isMonitoring = true
    this.resetSessionStats()

    // Monitor every 1 second
    this.monitoringInterval = setInterval(() => {
      this.collectMetrics()
    }, 1000)

    console.log('Performance monitoring started')
  }

  /**
   * Stop monitoring system performance
   */
  stopMonitoring() {
    if (!this.isMonitoring) return

    this.isMonitoring = false

    if (this.monitoringInterval) {
      clearInterval(this.monitoringInterval)
      this.monitoringInterval = null
    }

    console.log('Performance monitoring stopped')
  }

  /**
   * Record face detection time
   */
  recordDetectionTime(duration) {
    this.metrics.detectionTimes.push({
      timestamp: Date.now(),
      duration: duration,
    })

    // Keep only last 100 measurements
    if (this.metrics.detectionTimes.length > 100) {
      this.metrics.detectionTimes.shift()
    }

    // Check if detection time exceeds threshold
    if (duration > this.thresholds.maxDetectionTime) {
      this.notifyObservers('detection-slow', {
        duration,
        threshold: this.thresholds.maxDetectionTime,
      })
    }
  }

  /**
   * Record detection result
   */
  recordDetection(result) {
    this.metrics.sessionStats.totalDetections++

    if (result.success) {
      this.metrics.sessionStats.successfulDetections++

      // Update average confidence
      const currentAvg = this.metrics.sessionStats.averageConfidence
      const totalSuccess = this.metrics.sessionStats.successfulDetections
      this.metrics.sessionStats.averageConfidence =
        (currentAvg * (totalSuccess - 1) + result.confidence) / totalSuccess
    } else {
      this.metrics.sessionStats.failedDetections++

      // Track error types
      const errorType = result.error || 'unknown'
      this.metrics.errorCounts[errorType] = (this.metrics.errorCounts[errorType] || 0) + 1
    }

    // Performance analysis
    this.analyzePerformance()
  }

  /**
   * Record FPS value
   */
  recordFPS(fps) {
    this.metrics.fpsValues.push({
      timestamp: Date.now(),
      fps: fps,
    })

    // Keep only last 60 measurements (1 minute worth)
    if (this.metrics.fpsValues.length > 60) {
      this.metrics.fpsValues.shift()
    }

    // Check if FPS is below threshold
    if (fps < this.thresholds.minFPS) {
      this.notifyObservers('fps-low', {
        fps,
        threshold: this.thresholds.minFPS,
      })
    }
  }

  /**
   * Collect system metrics
   */
  collectMetrics() {
    // Memory usage
    if (typeof performance.memory !== 'undefined') {
      const memoryInfo = performance.memory
      const memoryUsageData = {
        timestamp: Date.now(),
        used: Math.round(memoryInfo.usedJSHeapSize / 1024 / 1024), // MB
        total: Math.round(memoryInfo.totalJSHeapSize / 1024 / 1024), // MB
        limit: Math.round(memoryInfo.jsHeapSizeLimit / 1024 / 1024), // MB
      }

      this.metrics.memoryUsage.push(memoryUsageData)

      // Keep only last 300 measurements (5 minutes worth)
      if (this.metrics.memoryUsage.length > 300) {
        this.metrics.memoryUsage.shift()
      }

      // Check memory usage threshold
      if (memoryUsageData.used > this.thresholds.maxMemoryUsage) {
        this.notifyObservers('memory-high', {
          usage: memoryUsageData.used,
          threshold: this.thresholds.maxMemoryUsage,
        })
      }
    }

    // CPU usage (approximation)
    this.estimateCPUUsage()
  }

  /**
   * Estimate CPU usage based on frame timing
   */
  estimateCPUUsage() {
    const now = performance.now()
    const timeDiff = now - (this.lastCPUCheck || now)

    if (timeDiff > 0) {
      // Simple CPU estimation based on frame timing
      const cpuUsage = Math.min(100, (timeDiff / 16.67) * 100) // 60 FPS = 16.67ms per frame

      this.metrics.cpuUsage.push({
        timestamp: Date.now(),
        usage: cpuUsage,
      })

      // Keep only last 300 measurements
      if (this.metrics.cpuUsage.length > 300) {
        this.metrics.cpuUsage.shift()
      }

      // Check CPU usage threshold
      if (cpuUsage > this.thresholds.maxCPUUsage) {
        this.notifyObservers('cpu-high', {
          usage: cpuUsage,
          threshold: this.thresholds.maxCPUUsage,
        })
      }
    }

    this.lastCPUCheck = now
  }

  /**
   * Analyze overall performance
   */
  analyzePerformance() {
    const analysis = {
      timestamp: Date.now(),
      detectionPerformance: this.getDetectionPerformance(),
      systemPerformance: this.getSystemPerformance(),
      recommendations: this.getRecommendations(),
    }

    this.notifyObservers('performance-analysis', analysis)

    return analysis
  }

  /**
   * Get detection performance metrics
   */
  getDetectionPerformance() {
    const recentDetections = this.metrics.detectionTimes.slice(-20) // Last 20 detections
    const avgDetectionTime =
      recentDetections.length > 0
        ? recentDetections.reduce((sum, d) => sum + d.duration, 0) / recentDetections.length
        : 0

    const successRate =
      this.metrics.sessionStats.totalDetections > 0
        ? (this.metrics.sessionStats.successfulDetections /
            this.metrics.sessionStats.totalDetections) *
          100
        : 0

    return {
      averageDetectionTime: Math.round(avgDetectionTime),
      successRate: Math.round(successRate * 100) / 100,
      averageConfidence: Math.round(this.metrics.sessionStats.averageConfidence * 100) / 100,
      totalDetections: this.metrics.sessionStats.totalDetections,
      errorRate:
        Math.round(
          (this.metrics.sessionStats.failedDetections / this.metrics.sessionStats.totalDetections) *
            100 *
            100
        ) / 100,
    }
  }

  /**
   * Get system performance metrics
   */
  getSystemPerformance() {
    const recentMemory = this.metrics.memoryUsage.slice(-10) // Last 10 measurements
    const recentCPU = this.metrics.cpuUsage.slice(-10)
    const recentFPS = this.metrics.fpsValues.slice(-10)

    const avgMemoryUsage =
      recentMemory.length > 0
        ? recentMemory.reduce((sum, m) => sum + m.used, 0) / recentMemory.length
        : 0

    const avgCPUUsage =
      recentCPU.length > 0 ? recentCPU.reduce((sum, c) => sum + c.usage, 0) / recentCPU.length : 0

    const avgFPS =
      recentFPS.length > 0 ? recentFPS.reduce((sum, f) => sum + f.fps, 0) / recentFPS.length : 0

    return {
      memoryUsage: Math.round(avgMemoryUsage),
      cpuUsage: Math.round(avgCPUUsage),
      fps: Math.round(avgFPS),
      sessionDuration: Date.now() - this.metrics.sessionStats.startTime,
    }
  }

  /**
   * Get performance recommendations
   */
  getRecommendations() {
    const recommendations = []
    const detectionPerf = this.getDetectionPerformance()
    const systemPerf = this.getSystemPerformance()

    // Detection time recommendations
    if (detectionPerf.averageDetectionTime > 500) {
      recommendations.push({
        type: 'detection-optimization',
        priority: 'high',
        message:
          'Waktu deteksi terlalu lambat. Pertimbangkan untuk mengurangi resolusi input atau menggunakan model yang lebih ringan.',
        action: 'optimize-model',
      })
    }

    // Success rate recommendations
    if (detectionPerf.successRate < 80) {
      recommendations.push({
        type: 'accuracy-improvement',
        priority: 'medium',
        message: 'Tingkat keberhasilan deteksi rendah. Periksa kualitas kamera dan pencahayaan.',
        action: 'improve-conditions',
      })
    }

    // Memory usage recommendations
    if (systemPerf.memoryUsage > 80) {
      recommendations.push({
        type: 'memory-optimization',
        priority: 'high',
        message: 'Penggunaan memori tinggi. Implementasikan garbage collection atau kurangi cache.',
        action: 'optimize-memory',
      })
    }

    // FPS recommendations
    if (systemPerf.fps < 20) {
      recommendations.push({
        type: 'performance-optimization',
        priority: 'medium',
        message: 'Frame rate rendah. Kurangi interval deteksi atau optimasi algoritma.',
        action: 'optimize-fps',
      })
    }

    return recommendations
  }

  /**
   * Get comprehensive performance report
   */
  getPerformanceReport() {
    return {
      summary: {
        sessionDuration: Date.now() - this.metrics.sessionStats.startTime,
        totalDetections: this.metrics.sessionStats.totalDetections,
        successfulDetections: this.metrics.sessionStats.successfulDetections,
        failedDetections: this.metrics.sessionStats.failedDetections,
        averageConfidence: this.metrics.sessionStats.averageConfidence,
      },
      performance: {
        detection: this.getDetectionPerformance(),
        system: this.getSystemPerformance(),
      },
      metrics: {
        detectionTimes: this.metrics.detectionTimes.slice(-50), // Last 50
        memoryUsage: this.metrics.memoryUsage.slice(-60), // Last 60
        cpuUsage: this.metrics.cpuUsage.slice(-60),
        fpsValues: this.metrics.fpsValues.slice(-60),
      },
      errors: this.metrics.errorCounts,
      recommendations: this.getRecommendations(),
      timestamp: Date.now(),
    }
  }

  /**
   * Export performance data
   */
  exportPerformanceData(format = 'json') {
    const report = this.getPerformanceReport()

    switch (format) {
      case 'json':
        return JSON.stringify(report, null, 2)

      case 'csv':
        return this.convertToCSV(report)

      case 'summary':
        return this.generateSummaryReport(report)

      default:
        return report
    }
  }

  /**
   * Convert performance data to CSV format
   */
  convertToCSV(report) {
    const csvLines = []

    // Headers
    csvLines.push('Timestamp,DetectionTime,MemoryUsage,CPUUsage,FPS,Success,Confidence')

    // Combine all metrics by timestamp
    const allMetrics = []

    report.metrics.detectionTimes.forEach((d) => {
      allMetrics.push({
        timestamp: d.timestamp,
        detectionTime: d.duration,
        type: 'detection',
      })
    })

    // Sort by timestamp and create CSV rows
    allMetrics.sort((a, b) => a.timestamp - b.timestamp)
    allMetrics.forEach((metric) => {
      csvLines.push(
        `${metric.timestamp},${metric.detectionTime || ''},${metric.memoryUsage || ''},${metric.cpuUsage || ''},${metric.fps || ''},${metric.success || ''},${metric.confidence || ''}`
      )
    })

    return csvLines.join('\n')
  }

  /**
   * Generate summary report
   */
  generateSummaryReport(report) {
    const summary = report.summary
    const perf = report.performance

    return `
Performance Summary Report
=========================

Session Duration: ${Math.round(summary.sessionDuration / 1000)} seconds
Total Detections: ${summary.totalDetections}
Success Rate: ${((summary.successfulDetections / summary.totalDetections) * 100).toFixed(1)}%
Average Confidence: ${summary.averageConfidence.toFixed(1)}%

Detection Performance:
- Average Detection Time: ${perf.detection.averageDetectionTime}ms
- Error Rate: ${perf.detection.errorRate}%

System Performance:
- Memory Usage: ${perf.system.memoryUsage}MB
- CPU Usage: ${perf.system.cpuUsage}%
- Average FPS: ${perf.system.fps}

Recommendations:
${report.recommendations.map((r) => `- ${r.message}`).join('\n')}

Generated at: ${new Date().toISOString()}
    `.trim()
  }

  /**
   * Add performance observer
   */
  addObserver(callback) {
    this.observers.push(callback)
  }

  /**
   * Remove performance observer
   */
  removeObserver(callback) {
    this.observers = this.observers.filter((obs) => obs !== callback)
  }

  /**
   * Notify all observers
   */
  notifyObservers(event, data) {
    this.observers.forEach((callback) => {
      try {
        callback(event, data)
      } catch (error) {
        console.error('Observer callback error:', error)
      }
    })
  }

  /**
   * Reset session statistics
   */
  resetSessionStats() {
    this.metrics.sessionStats = {
      startTime: Date.now(),
      totalDetections: 0,
      successfulDetections: 0,
      failedDetections: 0,
      averageConfidence: 0,
    }

    this.metrics.errorCounts = {}
  }

  /**
   * Start memory monitoring
   */
  startMemoryMonitoring() {
    // Monitor memory usage every 5 seconds
    setInterval(() => {
      if (typeof performance.memory !== 'undefined') {
        const memoryInfo = performance.memory
        const memoryUsage = Math.round(memoryInfo.usedJSHeapSize / 1024 / 1024)

        // Trigger garbage collection if memory usage is high
        if (memoryUsage > this.thresholds.maxMemoryUsage && typeof window.gc === 'function') {
          window.gc()
        }
      }
    }, 5000)
  }

  /**
   * Optimize performance based on current metrics
   */
  optimizePerformance() {
    const recommendations = this.getRecommendations()
    const optimizations = []

    recommendations.forEach((rec) => {
      switch (rec.action) {
        case 'optimize-model':
          // Suggest model optimization
          optimizations.push({
            type: 'model',
            suggestion: 'Switch to lighter detection model',
            impact: 'Reduced detection time by ~30%',
          })
          break

        case 'optimize-memory':
          // Trigger garbage collection
          if (typeof window.gc === 'function') {
            window.gc()
            optimizations.push({
              type: 'memory',
              suggestion: 'Triggered garbage collection',
              impact: 'Freed unused memory',
            })
          }
          break

        case 'optimize-fps':
          // Suggest FPS optimization
          optimizations.push({
            type: 'fps',
            suggestion: 'Increase detection interval to 1000ms',
            impact: 'Improved frame rate',
          })
          break
      }
    })

    return optimizations
  }

  /**
   * Clean up resources
   */
  cleanup() {
    this.stopMonitoring()

    if (this.performanceObserver) {
      this.performanceObserver.disconnect()
      this.performanceObserver = null
    }

    this.observers = []
    this.metrics = {
      detectionTimes: [],
      memoryUsage: [],
      cpuUsage: [],
      fpsValues: [],
      errorCounts: {},
      sessionStats: {
        startTime: Date.now(),
        totalDetections: 0,
        successfulDetections: 0,
        failedDetections: 0,
        averageConfidence: 0,
      },
    }
  }
}

// Export singleton instance
export default new PerformanceMonitor()

// Export class for custom instances
export { PerformanceMonitor }

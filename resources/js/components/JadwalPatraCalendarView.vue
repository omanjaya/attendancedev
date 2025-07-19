<template>
  <div class="jadwal-patra-container">
    <!-- Header -->
    <div class="header-section">
      <h2 class="title">
        Penyusunan Jadwal Mengajar Guru
      </h2>

      <!-- Teacher Code Input Section -->
      <div class="teacher-input-section">
        <label>Masukkan Kode Guru:</label>
        <input
          v-model="teacherCode"
          type="text"
          placeholder="Contoh: JIN_28_3C"
          class="teacher-code-input"
          @keyup.enter="setTeacherCode"
        >
        <button class="btn-set-kode" @click="setTeacherCode">
          Set Kode
        </button>
        <button class="btn-logout" @click="showLogin">
          Logout
        </button>
        <span class="current-teacher">Kode Guru Saat Ini: <strong>{{ currentTeacherCode || 'BELUM DISET' }}</strong></span>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button class="btn-action btn-json" @click="exportJSON">
          Simpan Jadwal (JSON)
        </button>
        <button class="btn-action btn-json" @click="importJSON">
          Buat Jadwal (JSON)
        </button>
        <button class="btn-action btn-excel" @click="exportExcel">
          Export ke Excel
        </button>
        <button class="btn-action btn-undo" @click="undoAction">
          Undo (Ctrl+Z)
        </button>
        <button class="btn-action btn-reset" @click="resetSchedule">
          Reset Data
        </button>
      </div>
    </div>

    <!-- Schedule Grid -->
    <div class="schedule-grid-container">
      <table class="schedule-table">
        <!-- Header Row 1: Grade Levels -->
        <thead>
          <tr class="grade-header">
            <th rowspan="2" class="hari-header">
              HARI
            </th>
            <th :colspan="getClassCountByGrade(10)" class="grade-cell">
              Kelas X
            </th>
            <th :colspan="getClassCountByGrade(11)" class="grade-cell">
              Kelas XI
            </th>
            <th :colspan="getClassCountByGrade(12)" class="grade-cell">
              Kelas XII
            </th>
          </tr>
          <!-- Header Row 2: Class Numbers -->
          <tr class="class-header">
            <th
              v-for="classNum in getClassesByGrade(10)"
              :key="`x-${classNum}`"
              class="class-number-cell"
            >
              {{ classNum }}
            </th>
            <th
              v-for="classNum in getClassesByGrade(11)"
              :key="`xi-${classNum}`"
              class="class-number-cell"
            >
              {{ classNum }}
            </th>
            <th
              v-for="classNum in getClassesByGrade(12)"
              :key="`xii-${classNum}`"
              class="class-number-cell"
            >
              {{ classNum }}
            </th>
          </tr>
        </thead>

        <tbody>
          <!-- Time Slot Rows -->
          <tr v-for="day in days" :key="day" class="day-row">
            <!-- Day Column -->
            <td class="day-cell">
              {{ day }}
            </td>

            <!-- Class Cells for Each Grade -->
            <td
              v-for="classNum in getClassesByGrade(10)"
              :key="`${day}-x-${classNum}`"
              :class="getCellClass(day, 10, classNum)"
              class="schedule-cell"
              @click="handleCellClick(day, 10, classNum)"
            >
              <div class="cell-content">
                {{ getCellContent(day, 10, classNum) }}
              </div>
            </td>

            <td
              v-for="classNum in getClassesByGrade(11)"
              :key="`${day}-xi-${classNum}`"
              :class="getCellClass(day, 11, classNum)"
              class="schedule-cell"
              @click="handleCellClick(day, 11, classNum)"
            >
              <div class="cell-content">
                {{ getCellContent(day, 11, classNum) }}
              </div>
            </td>

            <td
              v-for="classNum in getClassesByGrade(12)"
              :key="`${day}-xii-${classNum}`"
              :class="getCellClass(day, 12, classNum)"
              class="schedule-cell"
              @click="handleCellClick(day, 12, classNum)"
            >
              <div class="cell-content">
                {{ getCellContent(day, 12, classNum) }}
              </div>
            </td>
          </tr>
        </tbody>

        <!-- Footer with totals -->
        <tfoot>
          <tr class="total-row">
            <td class="total-label">
              TOTAL JAM GURU
            </td>
            <td
              v-for="classNum in getClassesByGrade(10)"
              :key="`total-guru-x-${classNum}`"
              class="total-cell"
            >
              {{ getTotalHoursForClass(10, classNum) }}
            </td>
            <td
              v-for="classNum in getClassesByGrade(11)"
              :key="`total-guru-xi-${classNum}`"
              class="total-cell"
            >
              {{ getTotalHoursForClass(11, classNum) }}
            </td>
            <td
              v-for="classNum in getClassesByGrade(12)"
              :key="`total-guru-xii-${classNum}`"
              class="total-cell"
            >
              {{ getTotalHoursForClass(12, classNum) }}
            </td>
          </tr>
          <tr class="total-row">
            <td class="total-label">
              TOTAL JAM KELAS
            </td>
            <td
              v-for="classNum in getClassesByGrade(10)"
              :key="`total-kelas-x-${classNum}`"
              class="total-cell"
            >
              {{ getTotalClassHours(10, classNum) }}
            </td>
            <td
              v-for="classNum in getClassesByGrade(11)"
              :key="`total-kelas-xi-${classNum}`"
              class="total-cell"
            >
              {{ getTotalClassHours(11, classNum) }}
            </td>
            <td
              v-for="classNum in getClassesByGrade(12)"
              :key="`total-kelas-xii-${classNum}`"
              class="total-cell"
            >
              {{ getTotalClassHours(12, classNum) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Instructions -->
    <div class="instructions">
      <h3>Cara Penggunaan:</h3>
      <ol>
        <li>
          Masukkan kode guru di kolom "Masukkan Kode Guru" dan klik "Set Kode" atau tekan Enter.
        </li>
        <li>Klik pada kotak kosong di jadwal untuk menjadwalkan kode guru saat ini.</li>
        <li>Klik pada kotak berisi untuk menghapus kode guru dari jadwal.</li>
        <li>Gunakan "Ctrl+Z" untuk undo jadwal yang telah dihapus atau dibuat.</li>
        <li>
          Untuk melihat jadwal guru tertentu klik tombol mengajar untuk melihat mata pelajaran yang
          berbeda, dll akan menjadi merah semua rumusan perintah.
        </li>
        <li>Gunakan "Reset Data" untuk menghapus semua jadwal pada komputer Anda.</li>
        <li>
          Gunakan "Buat Jadwal (Upload JSON)" untuk mengunggah jadwal dari file JSON (otomatis dari
          komputer Anda).
        </li>
        <li>Jadwal otomatis tersimpan di browser (IndexedDB).</li>
        <li>
          Gunakan "Simpan Jadwal (Download JSON)" untuk mengunduh jadwal saat ini ke komputer.
        </li>
      </ol>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'

// Props
const props = defineProps({
  academicClasses: {
    type: Array,
    default: () => [],
  },
  timeSlots: {
    type: Array,
    default: () => [],
  },
})

// Reactive data
const teacherCode = ref('')
const currentTeacherCode = ref('')
const scheduleData = ref({})
const actionHistory = ref([])

// Days of week
const days = ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT']

// Computed
const getClassCountByGrade = (grade) => {
  // Always return 12 classes per grade like in the screenshot
  return 12
}

const getClassesByGrade = (grade) => {
  const classes = props.academicClasses.filter((cls) => cls.grade_level === grade)
  if (classes.length > 0) {
    return classes.map((cls) => cls.class_number || cls.section)
  }
  // Always show 12 classes per grade as in the screenshot
  return Array.from({ length: 12 }, (_, i) => i + 1)
}

// Methods
const setTeacherCode = () => {
  if (teacherCode.value.trim()) {
    currentTeacherCode.value = teacherCode.value.trim()
    teacherCode.value = ''
  }
}

const handleCellClick = (day, grade, classNum) => {
  const cellKey = `${day}-${grade}-${classNum}`

  if (scheduleData.value[cellKey]) {
    // Remove existing assignment
    actionHistory.value.push({
      type: 'remove',
      key: cellKey,
      value: scheduleData.value[cellKey],
    })
    delete scheduleData.value[cellKey]
  } else if (currentTeacherCode.value) {
    // Add new assignment
    actionHistory.value.push({
      type: 'add',
      key: cellKey,
      value: currentTeacherCode.value,
    })
    scheduleData.value[cellKey] = currentTeacherCode.value
  } else {
    alert('Silakan set kode guru terlebih dahulu!')
    return
  }

  saveToStorage()
}

const getCellClass = (day, grade, classNum) => {
  const cellKey = `${day}-${grade}-${classNum}`
  const hasData = scheduleData.value[cellKey]

  return {
    'has-teacher': hasData,
    clickable: true,
  }
}

const getCellContent = (day, grade, classNum) => {
  const cellKey = `${day}-${grade}-${classNum}`
  return scheduleData.value[cellKey] || ''
}

const getTotalHoursForClass = (grade, classNum) => {
  let total = 0
  days.forEach((day) => {
    const cellKey = `${day}-${grade}-${classNum}`
    if (scheduleData.value[cellKey]) {
      total++
    }
  })
  return total
}

const getTotalClassHours = (grade, classNum) => {
  // Same as getTotalHoursForClass for this implementation
  return getTotalHoursForClass(grade, classNum)
}

const undoAction = () => {
  if (actionHistory.value.length === 0) {return}

  const lastAction = actionHistory.value.pop()

  if (lastAction.type === 'add') {
    delete scheduleData.value[lastAction.key]
  } else if (lastAction.type === 'remove') {
    scheduleData.value[lastAction.key] = lastAction.value
  }

  saveToStorage()
}

const resetSchedule = () => {
  if (confirm('Apakah Anda yakin ingin menghapus semua jadwal?')) {
    scheduleData.value = {}
    actionHistory.value = []
    currentTeacherCode.value = ''
    saveToStorage()
  }
}

const exportJSON = () => {
  const data = {
    schedule: scheduleData.value,
    currentTeacher: currentTeacherCode.value,
    exported_at: new Date().toISOString(),
  }

  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `jadwal_${new Date().toISOString().split('T')[0]}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const importJSON = () => {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = '.json'

  input.onchange = (e) => {
    const file = e.target.files[0]
    if (!file) {return}

    const reader = new FileReader()
    reader.onload = (e) => {
      try {
        const data = JSON.parse(e.target.result)

        if (confirm('Import jadwal baru? Data sekarang akan diganti.')) {
          scheduleData.value = data.schedule || {}
          currentTeacherCode.value = data.currentTeacher || ''
          saveToStorage()
        }
      } catch (error) {
        alert('File JSON tidak valid!')
      }
    }
    reader.readAsText(file)
  }

  input.click()
}

const exportExcel = () => {
  // This would integrate with a library like SheetJS
  alert('Export Excel akan diimplementasikan dengan library SheetJS')
}

const showLogin = () => {
  alert('Fungsi logout akan redirect ke halaman login')
}

const saveToStorage = () => {
  const data = {
    schedule: scheduleData.value,
    currentTeacher: currentTeacherCode.value,
    history: actionHistory.value,
  }
  localStorage.setItem('jadwal_patra_data', JSON.stringify(data))
}

const loadFromStorage = () => {
  try {
    const saved = localStorage.getItem('jadwal_patra_data')
    if (saved) {
      const data = JSON.parse(saved)
      scheduleData.value = data.schedule || {}
      currentTeacherCode.value = data.currentTeacher || ''
      actionHistory.value = data.history || []
    }
  } catch (error) {
    console.error('Error loading from storage:', error)
  }
}

// Keyboard shortcuts
const handleKeyboard = (e) => {
  if (e.ctrlKey && e.key === 'z') {
    e.preventDefault()
    undoAction()
  }
}

// Lifecycle
onMounted(() => {
  loadFromStorage()
  document.addEventListener('keydown', handleKeyboard)
})
</script>

<style scoped>
.jadwal-patra-container {
  font-family: Arial, sans-serif;
  max-width: 100%;
  margin: 0 auto;
  padding: 20px;
  background: #f5f5f5;
}

.header-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  border: 1px solid #ddd;
}

.title {
  text-align: center;
  color: #2563eb;
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 20px;
}

.teacher-input-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  flex-wrap: wrap;
}

.teacher-input-section label {
  font-weight: bold;
  color: #333;
}

.teacher-code-input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  width: 200px;
}

.btn-set-kode {
  background: #2563eb;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}

.btn-logout {
  background: #dc2626;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}

.current-teacher {
  color: #333;
  font-size: 14px;
}

.action-buttons {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-action {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: bold;
}

.btn-json {
  background: #16a34a;
  color: white;
}

.btn-excel {
  background: #059669;
  color: white;
}

.btn-undo {
  background: #6b7280;
  color: white;
}

.btn-reset {
  background: #dc2626;
  color: white;
}

.schedule-grid-container {
  background: white;
  border-radius: 8px;
  overflow: auto;
  border: 1px solid #ddd;
  margin-bottom: 20px;
}

.schedule-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1000px;
}

.grade-header {
  background: #e5e7eb;
}

.class-header {
  background: #f3f4f6;
}

.grade-cell {
  background: #ddd6fe;
  color: #3730a3;
  font-weight: bold;
  text-align: center;
  padding: 12px;
  border: 1px solid #999;
}

.class-number-cell {
  background: #e0e7ff;
  color: #3730a3;
  font-weight: bold;
  text-align: center;
  padding: 8px;
  border: 1px solid #999;
  min-width: 40px;
}

.hari-header {
  background: #ddd6fe;
  color: #3730a3;
  font-weight: bold;
  text-align: center;
  padding: 12px;
  border: 1px solid #999;
  width: 80px;
}

.day-cell {
  background: #f3f4f6;
  font-weight: bold;
  text-align: center;
  padding: 15px 8px;
  border: 1px solid #999;
  writing-mode: vertical-rl;
  text-orientation: mixed;
}

.schedule-cell {
  border: 1px solid #ccc;
  padding: 0;
  height: 40px;
  min-width: 40px;
  text-align: center;
  cursor: pointer;
  background: white;
  transition: background-color 0.2s;
}

.schedule-cell:hover {
  background: #f0f9ff;
}

.schedule-cell.has-teacher {
  background: #dbeafe;
  color: #1e40af;
  font-weight: bold;
}

.schedule-cell.has-teacher:hover {
  background: #bfdbfe;
}

.cell-content {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
}

.total-row {
  background: #374151;
  color: white;
}

.total-label {
  background: #1f2937;
  color: white;
  font-weight: bold;
  text-align: center;
  padding: 8px;
  border: 1px solid #4b5563;
}

.total-cell {
  background: #374151;
  color: white;
  text-align: center;
  padding: 8px;
  border: 1px solid #4b5563;
  font-weight: bold;
}

.instructions {
  background: white;
  padding: 20px;
  border-radius: 8px;
  border: 1px solid #ddd;
}

.instructions h3 {
  color: #333;
  margin-bottom: 15px;
}

.instructions ol {
  color: #555;
  line-height: 1.6;
}

.instructions li {
  margin-bottom: 8px;
}

/* Responsive */
@media (max-width: 768px) {
  .teacher-input-section {
    flex-direction: column;
    align-items: flex-start;
  }

  .action-buttons {
    justify-content: center;
  }

  .schedule-grid-container {
    overflow-x: auto;
  }
}
</style>

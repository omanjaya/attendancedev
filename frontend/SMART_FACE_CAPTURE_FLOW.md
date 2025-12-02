# Smart Face Capture - Flow Documentation

## 🎯 **Flow Optimal untuk Pendaftaran Wajah**

Sistem pendaftaran wajah yang baru menggunakan **Smart Detection** dengan quality checks dan stability validation untuk memastikan hasil terbaik.

---

## **📊 Tahapan Pendaftaran**

### **1. Face Detection Phase** (Real-time)
**Status:** `detecting`

- ✅ Deteksi wajah secara kontinyu
- ✅ Validasi posisi frontal
- ✅ Cek pencahayaan (brightness)
- ✅ Validasi jarak (distance)

**Feedback to User:**
- 🔵 "Mencari wajah..." (belum detect)
- 🟡 "Wajah terdeteksi - Terlalu jauh/dekat" (quality issues)
- 🟢 "Wajah terdeteksi - Tahan posisi, jangan bergerak..." (ready)

---

### **2. Stability Check** (2 detik)
**Status:** Masih `detecting` dengan progress bar

**Requirements:**
- Wajah harus stabil (tidak bergerak > 30px)
- Pencahayaan: 50-220 brightness
- Ukuran wajah: 200-600px width
- Durasi: 2000ms (2 detik)

**Visual Feedback:**
- Progress bar menunjukkan stability: 0-100%
- Frame guide (dashed circle) as visual aid
- Status message dinamis

---

### **3. Auto-Capture** (Otomatis)
**Trigger:** Ketika stability >= 100%

**Actions:**
1. Stop detection loop
2. Capture image dari video stream
3. Convert to File object (JPEG, 95% quality)
4. Trigger `onCapture` callback

---

### **4. Processing Phase**
**Status:** `processing`

**Actions:**
1. Upload image ke `/api/v1/face/deepface/extract-embedding`
2. Backend proxy ke Python DeepFace service
3. DeepFace ekstrak 512-d embedding (ArcFace model)
4. Validasi kualitas gambar oleh DeepFace
5. Simpan ke database via `/api/v1/face-recognition/register`

**Visual Feedback:**
- Loading spinner
- "Memproses dengan DeepFace..."
- "Mengekstrak 512-d embedding"

---

### **5. Success/Error Phase**

**Success (`success`):**
- ✅ Show success overlay (green)
- ✅ "Wajah Anda telah terdaftar"
- ✅ Auto-close setelah 2.5 detik

**Error (`error`):**
- ❌ Show error overlay (red)
- ❌ Display error message
- ❌ Auto-restart detection setelah 3 detik

---

## **🔍 Quality Checks**

### **Client-Side Checks:**
| Check | Threshold | Purpose |
|-------|-----------|---------|
| **Brightness** | 50-220 | Pencahayaan cukup |
| **Face Size** | 200-600px | Jarak optimal |
| **Stability** | < 30px movement | No blur |
| **Duration** | 2000ms | Sufficient stability |

### **Server-Side Checks (DeepFace):**
| Check | Purpose |
|-------|---------|
| **Blur Detection** | Laplacian variance |
| **Face Detection** | RetinaFace detector |
| **Embedding Quality** | ArcFace confidence |
| **Liveness** | Anti-spoofing |

---

## **🎨 UI Components**

### **Detection Guide Frame:**
```tsx
<div className="border-4 border-dashed border-white/50 rounded-full w-64 h-80" />
```

### **Status Badge:**
- 🔵 Blue: Mencari wajah
- 🟡 Amber: Quality issues (too far/close, bad lighting)
- 🟢 Green: Perfect, processing

### **Progress Bar:**
```tsx
<div className="bg-white/20 h-2 rounded-full">
  <div style={{ width: `${stabilityProgress}%` }} />
</div>
```

---

## **⚡ Performance Optimizations**

1. **Canvas-based Detection:** Lightweight, no ML models on client
2. **Progressive Checks:** Fail fast pada quality issues
3. **Auto-retry on Error:** User experience yang baik
4. **Visual Feedback:** User selalu tahu apa yang harus dilakukan

---

## **🔧 Configuration**

```typescript
useSmartFaceCapture({
  stabilityDuration: 2000,      // 2 seconds stability required
  onQualityChange: (quality) => {...},
  onCapture: (imageFile) => {...}
})
```

---

## **📱 User Experience Flow**

```
1. User: Klik "Daftar Wajah"
   ↓
2. System: Aktifkan kamera & mulai detection
   ↓
3. System: "Mencari wajah..."
   ↓
4. System: "Wajah terdeteksi - Tahan posisi..."
   ↓
5. System: Progress bar 0→100% (2 detik)
   ↓
6. System: AUTO CAPTURE (tanpa button!)
   ↓
7. System: "Memproses dengan DeepFace..."
   ↓
8. System: "Berhasil! Wajah terdaftar" ✅
   ↓
9. System: Auto-close drawer
```

---

## **✅ Keuntungan Flow Baru**

| Aspek | Sebelum (Countdown) | Sesudah (Smart) |
|-------|---------------------|-----------------|
| **Akurasi** | ❌ User bisa bergerak saat countdown | ✅ Hanya capture saat stabil |
| **Kualitas** | ❌ Bisa blur/gelap/jauh | ✅ Validasi quality real-time |
| **UX** | ⚠️ Countdown membosankan | ✅ Feedback interaktif |
| **Success Rate** | ⚠️ Rendah (banyak reject) | ✅ Tinggi (pre-validated) |
| **User Effort** | ❌ Harus tekan tombol timing pas | ✅ Otomatis, tinggal diam |

---

## **🐛 Error Handling**

```typescript
try {
  // Capture & process
} catch (error) {
  setEnrollmentStep('error');
  setEnrollmentError(error.message);
  
  // Auto-restart setelah 3s
  setTimeout(() => {
    setEnrollmentStep('detecting');
    startDetection();
  }, 3000);
}
```

---

## **🎯 Best Practices**

1. ✅ **Always show visual feedback** - User harus tahu apa yang terjadi
2. ✅ **Progressive validation** - Fail fast, jangan tunggu sampai akhir
3. ✅ **Auto-retry on error** - Jangan biarkan user stuck
4. ✅ **Clear instructions** - Panduan yang jelas di setiap tahap
5. ✅ **Accessibility** - Pesan error yang informatif

---

## **📊 Metrics to Track**

- Average time to successful capture
- Success rate (first attempt)
- Most common quality failures
- User dropout rate per stage

---

**Status:** ✅ Implemented & Ready to Test
**Next:** Test on real devices (mobile & desktop)

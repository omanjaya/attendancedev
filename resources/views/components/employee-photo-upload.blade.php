@props(['name' => 'photo', 'required' => false, 'value' => null])

<div class="space-y-4">
    <x-ui.label for="{{ $name }}" value="Foto Profil" :required="$required" class="text-slate-700 dark:text-slate-300" />
    
    <div class="flex items-start space-x-6">
        <!-- Photo Preview -->
        <div class="relative group">
            <div class="w-32 h-32 bg-gray-100 dark:bg-gray-700 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center overflow-hidden transition-all duration-300 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20">
                <img id="photoPreview" 
                     src="{{ $value ? asset('storage/' . $value) : '' }}" 
                     alt="Photo Preview" 
                     class="w-full h-full object-cover rounded-lg {{ $value ? '' : 'hidden' }}"
                     style="display: {{ $value ? 'block' : 'none' }}">
                
                <div id="photoPlaceholder" class="text-center {{ $value ? 'hidden' : '' }}">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <p class="text-xs text-gray-500">Foto</p>
                </div>
            </div>
            
            <!-- Remove Photo Button -->
            <button type="button" 
                    id="removePhotoBtn" 
                    class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-xs transition-colors duration-200 {{ $value ? '' : 'hidden' }}"
                    onclick="removePhoto()">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <!-- Upload Controls -->
        <div class="flex-1 space-y-3">
            <div>
                <input type="file" 
                       id="{{ $name }}" 
                       name="{{ $name }}" 
                       accept="image/*"
                       class="hidden"
                       onchange="previewPhoto(this)">
                
                <x-ui.button type="button" 
                           variant="outline" 
                           onclick="document.getElementById('{{ $name }}').click()"
                           class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white hover:bg-white/40 focus:ring-2 focus:ring-blue-500/50 transition-all duration-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Pilih Foto
                </x-ui.button>
            </div>
            
            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                <p>• Format: JPG, PNG, GIF</p>
                <p>• Ukuran maksimal: 2MB</p>
                <p>• Resolusi disarankan: 300x300px</p>
                <p class="text-blue-600 dark:text-blue-400 font-medium">• Foto ini akan digunakan untuk deteksi wajah pada absensi</p>
            </div>
            
            <!-- Face Detection Status -->
            <div id="faceDetectionStatus" class="hidden">
                <div class="flex items-center space-x-2 text-sm">
                    <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
                    <span class="text-blue-600 dark:text-blue-400">Menganalisis wajah...</span>
                </div>
            </div>
            
            <!-- Face Enrollment Button -->
            <div id="faceEnrollmentSection" class="hidden">
                <x-ui.button type="button" 
                           variant="primary" 
                           onclick="startFaceEnrollment()"
                           class="w-full bg-blue-600 hover:bg-blue-700 text-white">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Daftarkan Wajah untuk Absensi
                </x-ui.button>
                <p class="text-xs text-gray-500 mt-1 text-center">Klik untuk menyimpan data wajah dan mengaktifkan deteksi otomatis</p>
            </div>
            
            <!-- Face Enrolled Status -->
            <div id="faceEnrolledStatus" class="hidden">
                <div class="flex items-center justify-center space-x-2 text-sm p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-green-700 dark:text-green-400 font-medium">Wajah sudah terdaftar untuk deteksi absensi</span>
                </div>
            </div>
        </div>
    </div>
    
    @error($name)
        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
    @enderror
</div>

<script>
// Global variable to store face descriptor
let currentFaceDescriptor = null;

function previewPhoto(input) {
    const preview = document.getElementById('photoPreview');
    const placeholder = document.getElementById('photoPlaceholder');
    const removeBtn = document.getElementById('removePhotoBtn');
    const faceDetectionStatus = document.getElementById('faceDetectionStatus');
    const enrollmentSection = document.getElementById('faceEnrollmentSection');
    const enrolledStatus = document.getElementById('faceEnrolledStatus');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran file terlalu besar. Maksimal 2MB.');
            input.value = '';
            return;
        }
        
        // Validate file type
        if (!file.type.match('image.*')) {
            alert('File harus berupa gambar (JPG, PNG, GIF).');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.classList.add('hidden');
            removeBtn.classList.remove('hidden');
            
            // Reset states
            enrollmentSection.classList.add('hidden');
            enrolledStatus.classList.add('hidden');
            
            // Start face detection analysis
            analyzeFaceInPhoto(e.target.result);
        };
        reader.readAsDataURL(file);
    }
}

async function analyzeFaceInPhoto(imageDataUrl) {
    const faceDetectionStatus = document.getElementById('faceDetectionStatus');
    const enrollmentSection = document.getElementById('faceEnrollmentSection');
    
    try {
        // Show analyzing status
        faceDetectionStatus.classList.remove('hidden');
        faceDetectionStatus.innerHTML = `
            <div class="flex items-center space-x-2 text-sm">
                <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
                <span class="text-blue-600 dark:text-blue-400">Menganalisis wajah...</span>
            </div>
        `;
        
        // Load face-api.js if not already loaded
        if (typeof faceapi === 'undefined') {
            await loadFaceApiLibrary();
        }
        
        // Create image element
        const img = new Image();
        img.src = imageDataUrl;
        
        await new Promise((resolve) => {
            img.onload = resolve;
        });
        
        // Detect face and extract descriptor
        const detection = await faceapi
            .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({
                inputSize: 512,
                scoreThreshold: 0.3
            }))
            .withFaceLandmarks()
            .withFaceDescriptor();
        
        if (detection) {
            currentFaceDescriptor = Array.from(detection.descriptor);
            
            // Show success status
            faceDetectionStatus.innerHTML = `
                <div class="flex items-center space-x-2 text-sm">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-green-600 dark:text-green-400">Wajah terdeteksi - Siap untuk enrollment</span>
                </div>
            `;
            
            // Show enrollment button
            enrollmentSection.classList.remove('hidden');
        } else {
            // No face detected
            faceDetectionStatus.innerHTML = `
                <div class="flex items-center space-x-2 text-sm">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <span class="text-red-600 dark:text-red-400">Wajah tidak terdeteksi. Pastikan foto menampilkan wajah dengan jelas.</span>
                </div>
            `;
        }
    } catch (error) {
        console.error('Face detection error:', error);
        faceDetectionStatus.innerHTML = `
            <div class="flex items-center space-x-2 text-sm">
                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                <span class="text-yellow-600 dark:text-yellow-400">Gagal menganalisis wajah. Foto akan disimpan tanpa data wajah.</span>
            </div>
        `;
    }
}

async function loadFaceApiLibrary() {
    // Load face-api.js from CDN if not already loaded
    if (typeof faceapi === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js';
        document.head.appendChild(script);
        
        await new Promise((resolve) => {
            script.onload = resolve;
        });
        
        // Load models
        await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
        await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
        await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
    }
}

async function startFaceEnrollment() {
    const enrollmentSection = document.getElementById('faceEnrollmentSection');
    const enrolledStatus = document.getElementById('faceEnrolledStatus');
    
    if (!currentFaceDescriptor) {
        alert('Tidak ada data wajah untuk didaftarkan. Silakan upload foto terlebih dahulu.');
        return;
    }
    
    try {
        // Show loading state
        enrollmentSection.innerHTML = `
            <div class="flex items-center justify-center space-x-2 text-sm p-3">
                <div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>
                <span class="text-blue-600">Mendaftarkan wajah...</span>
            </div>
        `;
        
        // Store face descriptor for form submission
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'face_descriptor';
        hiddenInput.value = JSON.stringify(currentFaceDescriptor);
        document.querySelector('form').appendChild(hiddenInput);
        
        // Show success status
        enrollmentSection.classList.add('hidden');
        enrolledStatus.classList.remove('hidden');
        
        // Optional: Save immediately if editing existing employee
        if (window.location.pathname.includes('/edit')) {
            await saveFaceDescriptorToServer();
        }
        
    } catch (error) {
        console.error('Face enrollment error:', error);
        alert('Gagal mendaftarkan wajah. Silakan coba lagi.');
        
        // Reset enrollment section
        enrollmentSection.innerHTML = `
            <button type="button" onclick="startFaceEnrollment()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Daftarkan Wajah untuk Absensi
            </button>
        `;
    }
}

async function saveFaceDescriptorToServer() {
    if (!currentFaceDescriptor) return;
    
    try {
        const response = await fetch('/api/face-verification/save-descriptor', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                face_descriptor: currentFaceDescriptor,
                confidence: 85
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('Face descriptor saved successfully');
        } else {
            console.error('Failed to save face descriptor:', result.message);
        }
    } catch (error) {
        console.error('Error saving face descriptor:', error);
    }
}

function removePhoto() {
    const input = document.getElementById('{{ $name }}');
    const preview = document.getElementById('photoPreview');
    const placeholder = document.getElementById('photoPlaceholder');
    const removeBtn = document.getElementById('removePhotoBtn');
    const faceDetectionStatus = document.getElementById('faceDetectionStatus');
    const enrollmentSection = document.getElementById('faceEnrollmentSection');
    const enrolledStatus = document.getElementById('faceEnrolledStatus');
    
    // Reset form
    input.value = '';
    preview.style.display = 'none';
    preview.src = '';
    placeholder.classList.remove('hidden');
    removeBtn.classList.add('hidden');
    faceDetectionStatus.classList.add('hidden');
    enrollmentSection.classList.add('hidden');
    enrolledStatus.classList.add('hidden');
    
    // Clear face descriptor
    currentFaceDescriptor = null;
    
    // Remove hidden face descriptor input if exists
    const faceDescriptorInput = document.querySelector('input[name="face_descriptor"]');
    if (faceDescriptorInput) {
        faceDescriptorInput.remove();
    }
}
</script>
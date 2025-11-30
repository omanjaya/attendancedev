<section>
    <header>
        <h2 class="text-xl font-semibold text-slate-800 dark:text-white">
            {{ __('Informasi Profil') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            {{ __("Perbarui informasi profil dan alamat email akun Anda.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div>
            <x-ui.label for="name" value="{{ __('Nama') }}" class="text-slate-700 dark:text-slate-300" />
            <x-ui.input id="name" name="name" type="text" class="mt-1 block w-full bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            @error('name')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-ui.label for="email" value="{{ __('Email') }}" class="text-slate-700 dark:text-slate-300" />
            <x-ui.input id="email" name="email" type="email" class="mt-1 block w-full bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            @error('email')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-slate-800 dark:text-slate-200">
                        {{ __('Alamat email Anda belum diverifikasi.') }}

                        <button form="send-verification" class="underline text-sm text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ __('Klik di sini untuk mengirim ulang email verifikasi.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-500">
                            {{ __('Tautan verifikasi baru telah dikirim ke alamat email Anda.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Face Recognition Enrollment Section -->
        <div class="mt-6 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Pendaftaran Pengenalan Wajah</h3>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400 mb-4">Unggah foto wajah yang jelas untuk mengaktifkan check-in/out biometrik.</p>

            <div class="space-y-4">
                <div>
                    <x-ui.label for="face_photo" value="Unggah Foto Wajah" class="text-slate-700 dark:text-slate-300" />
                    <input id="face_photo" name="face_photo" type="file" class="mt-1 block w-full bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" accept="image/*" />
                    @error('face_photo')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="relative w-full max-w-md mx-auto border border-white/30 rounded-lg overflow-hidden shadow-md">
                    <canvas id="face-photo-canvas" class="w-full h-auto"></canvas>
                    <div id="face-detection-status" class="absolute inset-0 flex items-center justify-center bg-black/50 text-white text-lg font-semibold" style="display: none;"></div>
                </div>

                <div class="flex items-center gap-4">
                    <x-ui.button type="button" id="enroll-face-button" disabled variant="primary">
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Daftarkan Wajah
                    </x-ui.button>
                    <p id="enrollment-message" class="text-sm text-slate-600 dark:text-slate-400"></p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-ui.button type="submit" variant="primary">
                <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                {{ __('Simpan') }}
            </x-ui.button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-500"
                >{{ __('Tersimpan.') }}</p>
            @endif
        </div>
    </form>
</section>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const facePhotoInput = document.getElementById('face_photo');
    const facePhotoCanvas = document.getElementById('face-photo-canvas');
    const faceDetectionStatus = document.getElementById('face-detection-status');
    const enrollFaceButton = document.getElementById('enroll-face-button');
    const enrollmentMessage = document.getElementById('enrollment-message');
    const ctx = facePhotoCanvas.getContext('2d');

    let faceDescriptor = null;
    let image = new Image();

    // Load face-api.js models
    async function loadModels() {
        enrollmentMessage.textContent = 'Memuat model pengenalan wajah...';
        enrollFaceButton.disabled = true;
        
        // Check if models are already loaded globally
        if (window.faceApiModelsReady) {
            enrollmentMessage.textContent = 'Model dimuat. Unggah foto.';
            return true;
        }
        
        try {
            const MODEL_URL = '/models';
            console.log('Memuat model wajah untuk pendaftaran...');
            
            // Load models sequentially to avoid race conditions
            enrollmentMessage.textContent = 'Memuat model deteksi wajah...';
            await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
            console.log('Model SSD MobileNet dimuat');
            
            enrollmentMessage.textContent = 'Memuat model landmark wajah...';
            await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
            console.log('Model landmark wajah dimuat');
            
            enrollmentMessage.textContent = 'Memuat model pengenalan wajah...';
            await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
            console.log('Model pengenalan wajah dimuat');
            
            // Verify models are loaded
            if (!faceapi.nets.ssdMobilenetv1.isLoaded || 
                !faceapi.nets.faceLandmark68Net.isLoaded || 
                !faceapi.nets.faceRecognitionNet.isLoaded) {
                throw new Error('Satu atau lebih model gagal dimuat dengan benar');
            }
            
            console.log('Semua model pendaftaran wajah berhasil dimuat');
            enrollmentMessage.textContent = 'Model dimuat. Unggah foto.';
            enrollmentMessage.style.color = 'green';
            window.faceApiModelsReady = true;
            return true;
        } catch (error) {
            console.error('Gagal memuat model face-api.js untuk pendaftaran:', error);
            enrollmentMessage.textContent = 'Error memuat model. Mohon segarkan halaman.';
            enrollmentMessage.style.color = 'red';
            return false;
        }
    }

    // Handle photo upload
    facePhotoInput.addEventListener('change', async function(event) {
        const file = event.target.files[0];
        if (!file) return;

        enrollmentMessage.textContent = 'Memproses foto...';
        enrollFaceButton.disabled = true;
        faceDetectionStatus.style.display = 'none';

        // Ensure models are loaded before processing
        if (!window.faceApiModelsReady) {
            enrollmentMessage.textContent = 'Memuat model pengenalan wajah...';
            const modelsLoaded = await loadModels();
            if (!modelsLoaded) {
                enrollmentMessage.textContent = 'Gagal memuat model. Mohon segarkan halaman.';
                enrollmentMessage.style.color = 'red';
                return;
            }
        }

        const reader = new FileReader();
        reader.onload = async function(e) {
            image.src = e.target.result;
            image.onload = async function() {
                // Wait a bit more to ensure models are fully ready
                await new Promise(resolve => setTimeout(resolve, 500));
                
                // Resize canvas to image size
                facePhotoCanvas.width = image.width;
                facePhotoCanvas.height = image.height;
                ctx.drawImage(image, 0, 0, image.width, image.height);

                // Detect face
                try {
                    enrollmentMessage.textContent = 'Mendeteksi wajah...';
                    
                    // Double-check that models are loaded before detection
                    if (!faceapi.nets.ssdMobilenetv1.isLoaded) {
                        throw new Error('Model SSD MobileNet tidak dimuat');
                    }
                    if (!faceapi.nets.faceLandmark68Net.isLoaded) {
                        throw new Error('Model landmark wajah tidak dimuat');
                    }
                    if (!faceapi.nets.faceRecognitionNet.isLoaded) {
                        throw new Error('Model pengenalan wajah tidak dimuat');
                    }
                    
                    const detections = await faceapi.detectAllFaces(image, new faceapi.SsdMobilenetv1Options({
                        minConfidence: 0.5
                    }))
                    .withFaceLandmarks()
                    .withFaceDescriptors();

                    console.log(`Ditemukan ${detections.length} wajah di gambar yang diunggah`);

                    if (detections.length > 0) {
                        const bestDetection = detections[0];
                        const confidence = Math.round(bestDetection.detection.score * 100);
                        faceDescriptor = Array.from(bestDetection.descriptor);

                        // Draw bounding box and landmarks
                        const resizedDetections = faceapi.resizeResults(bestDetection, { width: image.width, height: image.height });
                        faceapi.draw.drawDetections(facePhotoCanvas, resizedDetections);
                        faceapi.draw.drawFaceLandmarks(facePhotoCanvas, resizedDetections);

                        faceDetectionStatus.style.display = 'flex';
                        faceDetectionStatus.style.backgroundColor = 'rgba(16, 185, 129, 0.8)';
                        faceDetectionStatus.textContent = `Wajah Terdeteksi! (${confidence}% kepercayaan)`;
                        enrollmentMessage.textContent = `Wajah terdeteksi dengan ${confidence}% kepercayaan. Klik Daftarkan Wajah.`;
                        enrollmentMessage.style.color = 'green';
                        enrollFaceButton.disabled = false;
                        
                        console.log('Deteksi wajah berhasil:', { confidence, descriptorLength: faceDescriptor.length });
                    } else {
                        faceDescriptor = null;
                        faceDetectionStatus.style.display = 'flex';
                        faceDetectionStatus.style.backgroundColor = 'rgba(239, 68, 68, 0.8)';
                        faceDetectionStatus.textContent = 'Tidak Ada Wajah Terdeteksi!';
                        enrollmentMessage.textContent = 'Tidak ada wajah terdeteksi. Mohon pastikan pencahayaan baik dan hadapkan kamera langsung ke wajah.';
                        enrollmentMessage.style.color = 'red';
                        enrollFaceButton.disabled = true;
                    }
                } catch (detectionError) {
                    console.error('Kesalahan deteksi wajah:', detectionError);
                    faceDetectionStatus.style.display = 'flex';
                    faceDetectionStatus.style.backgroundColor = 'rgba(239, 68, 68, 0.8)';
                    faceDetectionStatus.textContent = 'Kesalahan Deteksi!';
                    enrollmentMessage.textContent = 'Terjadi kesalahan selama deteksi wajah. Mohon coba lagi.';
                    enrollmentMessage.style.color = 'red';
                    enrollFaceButton.disabled = true;
                }
            };
        };
        reader.readAsDataURL(file);
    });

    // Handle enroll button click
    enrollFaceButton.addEventListener('click', async function() {
        if (!faceDescriptor) {
            enrollmentMessage.textContent = 'Tidak ada data wajah untuk didaftarkan.';
            return;
        }

        enrollmentMessage.textContent = 'Mendaftarkan data wajah...';
        enrollFaceButton.disabled = true;

        try {
            const response = await fetch('/api/face-verification/save-descriptor', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ 
                    face_descriptor: faceDescriptor,
                    confidence: 85 // Default confidence for enrollment
                })
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Kesalahan HTTP:', response.status, response.statusText, errorText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                enrollmentMessage.textContent = 'Wajah berhasil didaftarkan!';
                enrollmentMessage.style.color = 'green';
            } else {
                enrollmentMessage.textContent = data.message || 'Gagal mendaftarkan data wajah.';
                enrollmentMessage.style.color = 'red';
                console.error('Pendaftaran gagal:', data);
            }
        } catch (error) {
            console.error('Kesalahan pendaftaran:', error);
            if (error.message.includes('401')) {
                enrollmentMessage.textContent = 'Kesalahan otentikasi. Mohon segarkan halaman dan coba lagi.';
            } else if (error.message.includes('422')) {
                enrollmentMessage.textContent = 'Data wajah tidak valid. Mohon coba unggah foto yang lebih jelas.';
            } else if (error.message.includes('SyntaxError')) {
                enrollmentMessage.textContent = 'Kesalahan server. Mohon segarkan halaman dan coba lagi.';
            } else {
                enrollmentMessage.textContent = 'Terjadi kesalahan selama pendaftaran. Mohon coba lagi.';
            }
            enrollmentMessage.style.color = 'red';
        } finally {
            enrollFaceButton.disabled = false;
        }
    });

    // Initial model load - wait for DOM to be fully ready
    setTimeout(() => {
        loadModels();
    }, 100);
});
</script>
@endpush
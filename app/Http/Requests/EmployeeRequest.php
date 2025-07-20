<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Unified Employee Form Request
 *
 * Handles validation for both create and update operations
 * Simplifies maintenance by having single validation source
 */
class EmployeeRequest extends FormRequest
{
    /**
     * Authorization check
     */
    public function authorize(): bool
    {
        return match ($this->method()) {
            'POST' => $this->user()->can('create_employees'),
            'PUT', 'PATCH' => $this->user()->can('edit_employees'),
            default => false
        };
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        $employee = $this->route('employee');
        $isUpdate = $employee !== null;

        return [
            // Basic Information
            'employee_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_id')->ignore($employee?->id),
            ],
            'full_name' => 'required|string|max:255',

            // Account Information
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($employee?->user_id),
            ],
            'password' => $isUpdate ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',

            // Employment Details
            'employee_type' => 'required|in:permanent,honorary,staff',
            'hire_date' => 'required|date|before_or_equal:today',
            'location_id' => 'nullable|exists:locations,id',
            'role' => 'required|exists:roles,name',

            // Compensation
            'salary_type' => 'required|in:hourly,monthly,fixed',
            'salary_amount' => 'required_if:salary_type,monthly,fixed|nullable|numeric|min:0',
            'hourly_rate' => 'required_if:salary_type,hourly|nullable|numeric|min:0',

            // Optional Fields
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|max:2048',
            'face_descriptor' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            // ID & Basic Info
            'employee_id.required' => 'ID pegawai harus diisi.',
            'employee_id.unique' => 'ID pegawai ini sudah digunakan.',
            'employee_id.max' => 'ID pegawai maksimal 50 karakter.',
            'full_name.required' => 'Nama lengkap harus diisi.',
            'full_name.max' => 'Nama lengkap maksimal 255 karakter.',

            // Account Info
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password harus diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',

            // Employment
            'employee_type.required' => 'Jenis pegawai harus dipilih.',
            'employee_type.in' => 'Jenis pegawai tidak valid.',
            'hire_date.required' => 'Tanggal bergabung harus diisi.',
            'hire_date.date' => 'Format tanggal tidak valid.',
            'hire_date.before_or_equal' => 'Tanggal bergabung tidak boleh di masa depan.',
            'location_id.exists' => 'Lokasi yang dipilih tidak valid.',
            'role.required' => 'Role harus dipilih.',
            'role.exists' => 'Role yang dipilih tidak valid.',

            // Compensation
            'salary_type.required' => 'Tipe gaji harus dipilih.',
            'salary_type.in' => 'Tipe gaji tidak valid.',
            'salary_amount.required_if' => 'Jumlah gaji diperlukan untuk pegawai bulanan/tetap.',
            'salary_amount.numeric' => 'Jumlah gaji harus berupa angka.',
            'salary_amount.min' => 'Jumlah gaji tidak boleh negatif.',
            'hourly_rate.required_if' => 'Tarif per jam diperlukan untuk pegawai per jam.',
            'hourly_rate.numeric' => 'Tarif per jam harus berupa angka.',
            'hourly_rate.min' => 'Tarif per jam tidak boleh negatif.',

            // Optional
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Set default active status for new employees
        if ($this->isMethod('POST') && ! $this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}

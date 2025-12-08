<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super-admin', 'Super Admin', 'admin', 'Admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:locations,name',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:5000',
            'wifi_ssid' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'type' => 'nullable|string|in:office,school,factory,warehouse,remote,other',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lokasi wajib diisi.',
            'name.unique' => 'Nama lokasi sudah digunakan.',
            'name.max' => 'Nama lokasi maksimal 255 karakter.',
            'address.max' => 'Alamat maksimal 500 karakter.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'radius_meters.required' => 'Radius wajib diisi.',
            'radius_meters.integer' => 'Radius harus berupa angka bulat.',
            'radius_meters.min' => 'Radius minimal 10 meter.',
            'radius_meters.max' => 'Radius maksimal 5000 meter.',
            'wifi_ssid.max' => 'WiFi SSID maksimal 255 karakter.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
            'type.in' => 'Tipe lokasi tidak valid.',
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}

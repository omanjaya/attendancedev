<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_employees');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'required|string|max:50|unique:employees,employee_id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'employee_type' => ['required', Rule::in(['permanent', 'honorary', 'staff'])],
            'hire_date' => 'required|date|before_or_equal:today',
            'salary_type' => ['required', Rule::in(['hourly', 'monthly', 'fixed'])],
            'salary_amount' => 'nullable|numeric|min:0|max:999999999.99',
            'hourly_rate' => 'nullable|numeric|min:0|max:999999.99',
            'location_id' => 'nullable|exists:locations,id',
            'role' => 'required|exists:roles,name',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'employee ID',
            'first_name' => 'first name',
            'last_name' => 'last name',
            'email' => 'email address',
            'password' => 'password',
            'phone' => 'phone number',
            'photo' => 'photo',
            'employee_type' => 'employee type',
            'hire_date' => 'hire date',
            'salary_type' => 'salary type',
            'salary_amount' => 'salary amount',
            'hourly_rate' => 'hourly rate',
            'location_id' => 'location',
            'role' => 'role',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'employee_id.unique' => 'This employee ID is already taken.',
            'email.unique' => 'This email address is already registered.',
            'hire_date.before_or_equal' => 'Hire date cannot be in the future.',
            'photo.image' => 'The file must be an image.',
            'photo.mimes' => 'The photo must be a file of type: jpeg, png, jpg, webp.',
            'photo.max' => 'The photo may not be greater than 2MB.',
            'salary_amount.max' => 'Salary amount cannot exceed 999,999,999.99.',
            'hourly_rate.max' => 'Hourly rate cannot exceed 999,999.99.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Ensure only one salary type is provided
            if ($this->salary_type === 'hourly' && $this->salary_amount) {
                $validator->errors()->add('salary_amount', 'Salary amount should not be provided for hourly employees.');
            }

            if ($this->salary_type === 'monthly' && $this->hourly_rate) {
                $validator->errors()->add('hourly_rate', 'Hourly rate should not be provided for monthly salary employees.');
            }

            if ($this->salary_type === 'hourly' && ! $this->hourly_rate) {
                $validator->errors()->add('hourly_rate', 'Hourly rate is required for hourly employees.');
            }

            if (in_array($this->salary_type, ['monthly', 'fixed']) && ! $this->salary_amount) {
                $validator->errors()->add('salary_amount', 'Salary amount is required for monthly/fixed salary employees.');
            }
        });
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'city_id' => ['required', Rule::exists('cities', 'id')],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'services' => ['array'],
            'services.*' => [Rule::exists('services', 'id')],
            'cnas_contract' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'numele clinicii',
            'city_id' => 'orașul',
            'address' => 'adresa',
            'phone' => 'telefonul',
            'email' => 'adresa de email',
            'website' => 'site-ul web',
            'description' => 'descrierea',
        ];
    }
}

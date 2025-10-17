<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKategoriBarangRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_kategori' => [
                'required',
                'string',
                'max:100',
                'unique:kategori_barang,nama_kategori'
            ],
            'kode_kategori' => [
                'required',
                'string',
                'max:10',
                'alpha_num',
                'uppercase',
                'unique:kategori_barang,kode_kategori'
            ],
            'deskripsi' => [
                'nullable',
                'string',
                'max:500'
            ],
            'aktif' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.string' => 'Nama kategori harus berupa teks.',
            'nama_kategori.max' => 'Nama kategori maksimal 100 karakter.',
            'nama_kategori.unique' => 'Nama kategori sudah digunakan.',
            
            'kode_kategori.required' => 'Kode kategori wajib diisi.',
            'kode_kategori.string' => 'Kode kategori harus berupa teks.',
            'kode_kategori.max' => 'Kode kategori maksimal 10 karakter.',
            'kode_kategori.alpha_num' => 'Kode kategori hanya boleh mengandung huruf dan angka.',
            'kode_kategori.uppercase' => 'Kode kategori harus menggunakan huruf kapital.',
            'kode_kategori.unique' => 'Kode kategori sudah digunakan.',
            
            'deskripsi.string' => 'Deskripsi harus berupa teks.',
            'deskripsi.max' => 'Deskripsi maksimal 500 karakter.',
            
            'aktif.boolean' => 'Status aktif harus berupa true atau false.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('kode_kategori')) {
            $this->merge([
                'kode_kategori' => strtoupper($this->kode_kategori)
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'nama_kategori' => 'nama kategori',
            'kode_kategori' => 'kode kategori',
            'deskripsi' => 'deskripsi',
            'aktif' => 'status aktif'
        ];
    }
}
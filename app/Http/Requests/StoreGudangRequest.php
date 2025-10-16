<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGudangRequest extends FormRequest
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
            'nama_gudang' => 'required|string|max:255|unique:gudang,nama_gudang',
            'alamat' => 'required|string|max:500',
            'total_kapasitas' => 'required|numeric|min:0|max:999999.99',
            'panjang' => 'required|numeric|min:0|max:9999.99',
            'lebar' => 'required|numeric|min:0|max:9999.99',
            'tinggi' => 'required|numeric|min:0|max:999.99',
            'aktif' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nama_gudang.required' => 'Nama gudang wajib diisi.',
            'nama_gudang.string' => 'Nama gudang harus berupa teks.',
            'nama_gudang.max' => 'Nama gudang maksimal 255 karakter.',
            'nama_gudang.unique' => 'Nama gudang sudah digunakan.',
            
            'alamat.required' => 'Alamat gudang wajib diisi.',
            'alamat.string' => 'Alamat gudang harus berupa teks.',
            'alamat.max' => 'Alamat gudang maksimal 500 karakter.',
            
            'total_kapasitas.required' => 'Total kapasitas wajib diisi.',
            'total_kapasitas.numeric' => 'Total kapasitas harus berupa angka.',
            'total_kapasitas.min' => 'Total kapasitas minimal 0.',
            'total_kapasitas.max' => 'Total kapasitas maksimal 999,999.99.',
            
            'panjang.required' => 'Panjang gudang wajib diisi.',
            'panjang.numeric' => 'Panjang gudang harus berupa angka.',
            'panjang.min' => 'Panjang gudang minimal 0.',
            'panjang.max' => 'Panjang gudang maksimal 9,999.99.',
            
            'lebar.required' => 'Lebar gudang wajib diisi.',
            'lebar.numeric' => 'Lebar gudang harus berupa angka.',
            'lebar.min' => 'Lebar gudang minimal 0.',
            'lebar.max' => 'Lebar gudang maksimal 9,999.99.',
            
            'tinggi.required' => 'Tinggi gudang wajib diisi.',
            'tinggi.numeric' => 'Tinggi gudang harus berupa angka.',
            'tinggi.min' => 'Tinggi gudang minimal 0.',
            'tinggi.max' => 'Tinggi gudang maksimal 999.99.',
            
            'aktif.boolean' => 'Status aktif harus berupa true atau false.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nama_gudang' => 'nama gudang',
            'alamat' => 'alamat',
            'total_kapasitas' => 'total kapasitas',
            'panjang' => 'panjang',
            'lebar' => 'lebar',
            'tinggi' => 'tinggi',
            'aktif' => 'status aktif'
        ];
    }
}
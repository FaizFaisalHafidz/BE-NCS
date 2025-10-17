<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAreaGudangRequest extends FormRequest
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
            'gudang_id' => 'required|integer|exists:gudang,id',
            'kode_area' => [
                'required',
                'string',
                'max:20',
                Rule::unique('area_gudang')->where(function ($query) {
                    return $query->where('gudang_id', $this->gudang_id);
                })
            ],
            'nama_area' => 'required|string|max:255',
            'koordinat_x' => 'required|numeric|min:0|max:9999.99',
            'koordinat_y' => 'required|numeric|min:0|max:9999.99',
            'panjang' => 'required|numeric|min:0.01|max:9999.99',
            'lebar' => 'required|numeric|min:0.01|max:9999.99',
            'tinggi' => 'required|numeric|min:0.01|max:999.99',
            'kapasitas' => 'sometimes|numeric|min:0|max:999999.99',
            'jenis_area' => 'required|string|in:rak,lantai,khusus',
            'tersedia' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'gudang_id.required' => 'Gudang wajib dipilih.',
            'gudang_id.integer' => 'Gudang harus berupa angka.',
            'gudang_id.exists' => 'Gudang yang dipilih tidak valid.',
            
            'kode_area.required' => 'Kode area wajib diisi.',
            'kode_area.string' => 'Kode area harus berupa teks.',
            'kode_area.max' => 'Kode area maksimal 20 karakter.',
            'kode_area.unique' => 'Kode area sudah digunakan dalam gudang ini.',
            
            'nama_area.required' => 'Nama area wajib diisi.',
            'nama_area.string' => 'Nama area harus berupa teks.',
            'nama_area.max' => 'Nama area maksimal 255 karakter.',
            
            'koordinat_x.required' => 'Koordinat X wajib diisi.',
            'koordinat_x.numeric' => 'Koordinat X harus berupa angka.',
            'koordinat_x.min' => 'Koordinat X minimal 0.',
            'koordinat_x.max' => 'Koordinat X maksimal 9,999.99.',
            
            'koordinat_y.required' => 'Koordinat Y wajib diisi.',
            'koordinat_y.numeric' => 'Koordinat Y harus berupa angka.',
            'koordinat_y.min' => 'Koordinat Y minimal 0.',
            'koordinat_y.max' => 'Koordinat Y maksimal 9,999.99.',
            
            'panjang.required' => 'Panjang area wajib diisi.',
            'panjang.numeric' => 'Panjang area harus berupa angka.',
            'panjang.min' => 'Panjang area minimal 0.01.',
            'panjang.max' => 'Panjang area maksimal 9,999.99.',
            
            'lebar.required' => 'Lebar area wajib diisi.',
            'lebar.numeric' => 'Lebar area harus berupa angka.',
            'lebar.min' => 'Lebar area minimal 0.01.',
            'lebar.max' => 'Lebar area maksimal 9,999.99.',
            
            'tinggi.required' => 'Tinggi area wajib diisi.',
            'tinggi.numeric' => 'Tinggi area harus berupa angka.',
            'tinggi.min' => 'Tinggi area minimal 0.01.',
            'tinggi.max' => 'Tinggi area maksimal 999.99.',
            
            'kapasitas.numeric' => 'Kapasitas harus berupa angka.',
            'kapasitas.min' => 'Kapasitas minimal 0.',
            'kapasitas.max' => 'Kapasitas maksimal 999,999.99.',
            
            'jenis_area.required' => 'Jenis area wajib dipilih.',
            'jenis_area.string' => 'Jenis area harus berupa teks.',
            'jenis_area.in' => 'Jenis area harus salah satu dari: rak, lantai, khusus.',
            
            'tersedia.boolean' => 'Status tersedia harus berupa true atau false.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'gudang_id' => 'gudang',
            'kode_area' => 'kode area',
            'nama_area' => 'nama area',
            'koordinat_x' => 'koordinat X',
            'koordinat_y' => 'koordinat Y',
            'panjang' => 'panjang',
            'lebar' => 'lebar',
            'tinggi' => 'tinggi',
            'kapasitas' => 'kapasitas',
            'jenis_area' => 'jenis area',
            'tersedia' => 'status tersedia'
        ];
    }
}
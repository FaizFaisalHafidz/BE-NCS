<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAreaGudangRequest extends FormRequest
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
        $areaGudang = $this->route('areaGudang') ?? $this->route('area_gudang');
        $areaId = $areaGudang ? $areaGudang->id : null;
        $gudangId = $areaGudang ? $areaGudang->gudang_id : $this->gudang_id;

        return [
            'kode_area' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('area_gudang')->where(function ($query) use ($gudangId) {
                    return $query->where('gudang_id', $gudangId);
                })->ignore($areaId)
            ],
            'nama_area' => 'sometimes|string|max:255',
            'koordinat_x' => 'sometimes|numeric|min:-180|max:180',
            'koordinat_y' => 'sometimes|numeric|min:-90|max:90',
            'panjang' => 'sometimes|numeric|min:0.01|max:9999.99',
            'lebar' => 'sometimes|numeric|min:0.01|max:9999.99',
            'tinggi' => 'sometimes|numeric|min:0.01|max:999.99',
            'kapasitas' => 'sometimes|numeric|min:0|max:999999.99',
            'jenis_area' => 'sometimes|string|in:rak,lantai,khusus',
            'tersedia' => 'sometimes|boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'kode_area.string' => 'Kode area harus berupa teks.',
            'kode_area.max' => 'Kode area maksimal 20 karakter.',
            'kode_area.unique' => 'Kode area sudah digunakan dalam gudang ini.',
            
            'nama_area.string' => 'Nama area harus berupa teks.',
            'nama_area.max' => 'Nama area maksimal 255 karakter.',
            
            'koordinat_x.numeric' => 'Koordinat X harus berupa angka.',
            'koordinat_x.min' => 'Koordinat X minimal -180 (longitude).',
            'koordinat_x.max' => 'Koordinat X maksimal 180 (longitude).',
            
            'koordinat_y.numeric' => 'Koordinat Y harus berupa angka.',
            'koordinat_y.min' => 'Koordinat Y minimal -90 (latitude).',
            'koordinat_y.max' => 'Koordinat Y maksimal 90 (latitude).',
            
            'panjang.numeric' => 'Panjang area harus berupa angka.',
            'panjang.min' => 'Panjang area minimal 0.01.',
            'panjang.max' => 'Panjang area maksimal 9,999.99.',
            
            'lebar.numeric' => 'Lebar area harus berupa angka.',
            'lebar.min' => 'Lebar area minimal 0.01.',
            'lebar.max' => 'Lebar area maksimal 9,999.99.',
            
            'tinggi.numeric' => 'Tinggi area harus berupa angka.',
            'tinggi.min' => 'Tinggi area minimal 0.01.',
            'tinggi.max' => 'Tinggi area maksimal 999.99.',
            
            'kapasitas.numeric' => 'Kapasitas harus berupa angka.',
            'kapasitas.min' => 'Kapasitas minimal 0.',
            'kapasitas.max' => 'Kapasitas maksimal 999,999.99.',
            
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
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PenempatanBarangRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $rules = [
            'gudang_id' => 'required|exists:gudang,id',
            'area_gudang_id' => 'required|exists:area_gudang,id',
            'barang_id' => 'required|exists:barang,id',
            'jumlah' => 'required|integer|min:1',
            'tanggal_penempatan' => 'required|date|after_or_equal:today',
            'tanggal_kadaluarsa' => 'nullable|date|after:tanggal_penempatan',
            'status' => ['required', Rule::in(['ditempatkan', 'direservasi', 'diambil'])],
            'keterangan' => 'nullable|string|max:1000',
        ];

        // Untuk update, beberapa field tidak wajib
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = [
                'jumlah' => 'sometimes|integer|min:1',
                'tanggal_kadaluarsa' => 'nullable|date',
                'status' => ['sometimes', Rule::in(['ditempatkan', 'direservasi', 'diambil'])],
                'keterangan' => 'nullable|string|max:1000',
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'gudang_id.required' => 'Gudang harus dipilih',
            'gudang_id.exists' => 'Gudang yang dipilih tidak valid',
            'area_gudang_id.required' => 'Area gudang harus dipilih',
            'area_gudang_id.exists' => 'Area gudang yang dipilih tidak valid',
            'barang_id.required' => 'Barang harus dipilih',
            'barang_id.exists' => 'Barang yang dipilih tidak valid',
            'jumlah.required' => 'Jumlah barang harus diisi',
            'jumlah.integer' => 'Jumlah barang harus berupa angka',
            'jumlah.min' => 'Jumlah barang minimal 1',
            'tanggal_penempatan.required' => 'Tanggal penempatan harus diisi',
            'tanggal_penempatan.date' => 'Format tanggal penempatan tidak valid',
            'tanggal_penempatan.after_or_equal' => 'Tanggal penempatan tidak boleh kurang dari hari ini',
            'tanggal_kadaluarsa.date' => 'Format tanggal kadaluarsa tidak valid',
            'tanggal_kadaluarsa.after' => 'Tanggal kadaluarsa harus setelah tanggal penempatan',
            'status.required' => 'Status penempatan harus dipilih',
            'status.in' => 'Status penempatan harus: ditempatkan, direservasi, atau diambil',
            'keterangan.max' => 'Keterangan maksimal 1000 karakter',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validasi bahwa area gudang harus sesuai dengan gudang yang dipilih
            if ($this->has('gudang_id') && $this->has('area_gudang_id')) {
                $areaGudang = \App\Models\AreaGudang::find($this->area_gudang_id);
                if ($areaGudang && $areaGudang->gudang_id != $this->gudang_id) {
                    $validator->errors()->add('area_gudang_id', 'Area gudang tidak sesuai dengan gudang yang dipilih');
                }
            }
        });
    }
}
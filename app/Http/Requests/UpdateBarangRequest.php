<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBarangRequest extends FormRequest
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
        $barangId = $this->route('barang');

        return [
            'kode_barang' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('barang', 'kode_barang')->ignore($barangId)
            ],
            'nama_barang' => [
                'sometimes',
                'required',
                'string',
                'max:255'
            ],
            'kategori_barang_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:kategori_barang,id'
            ],
            'panjang' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.1',
                'max:9999.99'
            ],
            'lebar' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.1',
                'max:9999.99'
            ],
            'tinggi' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.1',
                'max:9999.99'
            ],
            'berat' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.01',
                'max:99999.99'
            ],
            'mudah_pecah' => [
                'sometimes',
                'boolean'
            ],
            'prioritas' => [
                'sometimes',
                'string',
                Rule::in(['rendah', 'sedang', 'tinggi'])
            ],
            'deskripsi' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000'
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
            'kode_barang.required' => 'Kode barang wajib diisi.',
            'kode_barang.string' => 'Kode barang harus berupa teks.',
            'kode_barang.max' => 'Kode barang maksimal 20 karakter.',
            'kode_barang.unique' => 'Kode barang sudah digunakan.',
            
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'nama_barang.string' => 'Nama barang harus berupa teks.',
            'nama_barang.max' => 'Nama barang maksimal 255 karakter.',
            
            'kategori_barang_id.required' => 'Kategori barang wajib dipilih.',
            'kategori_barang_id.integer' => 'Kategori barang harus berupa angka.',
            'kategori_barang_id.exists' => 'Kategori barang yang dipilih tidak valid.',
            
            'panjang.required' => 'Panjang barang wajib diisi.',
            'panjang.numeric' => 'Panjang barang harus berupa angka.',
            'panjang.min' => 'Panjang barang minimal 0.1 cm.',
            'panjang.max' => 'Panjang barang maksimal 9999.99 cm.',
            
            'lebar.required' => 'Lebar barang wajib diisi.',
            'lebar.numeric' => 'Lebar barang harus berupa angka.',
            'lebar.min' => 'Lebar barang minimal 0.1 cm.',
            'lebar.max' => 'Lebar barang maksimal 9999.99 cm.',
            
            'tinggi.required' => 'Tinggi barang wajib diisi.',
            'tinggi.numeric' => 'Tinggi barang harus berupa angka.',
            'tinggi.min' => 'Tinggi barang minimal 0.1 cm.',
            'tinggi.max' => 'Tinggi barang maksimal 9999.99 cm.',
            
            'berat.required' => 'Berat barang wajib diisi.',
            'berat.numeric' => 'Berat barang harus berupa angka.',
            'berat.min' => 'Berat barang minimal 0.01 kg.',
            'berat.max' => 'Berat barang maksimal 99999.99 kg.',
            
            'mudah_pecah.boolean' => 'Status mudah pecah harus berupa true atau false.',
            
            'prioritas.string' => 'Prioritas harus berupa teks.',
            'prioritas.in' => 'Prioritas harus salah satu dari: rendah, sedang, tinggi.',
            
            'deskripsi.string' => 'Deskripsi harus berupa teks.',
            'deskripsi.max' => 'Deskripsi maksimal 1000 karakter.',
            
            'aktif.boolean' => 'Status aktif harus berupa true atau false.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'kode_barang' => 'kode barang',
            'nama_barang' => 'nama barang',
            'kategori_barang_id' => 'kategori barang',
            'panjang' => 'panjang',
            'lebar' => 'lebar',
            'tinggi' => 'tinggi',
            'berat' => 'berat',
            'mudah_pecah' => 'mudah pecah',
            'prioritas' => 'prioritas',
            'deskripsi' => 'deskripsi',
            'aktif' => 'status aktif'
        ];
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\KelompokUmur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    public function index()
    {
        return view('index');
    }

    public function find(Request $request)
    {
        $rules = [
            'usia' => 'required|numeric',
            'tahunOrBulan' => 'required|string|in:bulan,tahun',
            'gender' => 'required|string|in:laki-laki,perempuan',
        ];

        // Jika gender adalah perempuan, tambahkan validasi tambahan
        if ($request->gender == 'perempuan') {
            $rules['kondisiKhusus'] = 'required|string|in:hamil,menyusui,tidak-keduanya';

            // Jika kondisi khusus bukan "tidak-keduanya", tambahkan validasi untuk umur
            if ($request->kondisiKhusus != 'tidak-keduanya') {
                $rules['umur'] = 'required|numeric';
            }
        }

        // Definisikan pesan kesalahan khusus
        $messages = [
            'usia.required' => 'Usia wajib diisi.',
            'usia.numeric' => 'Usia harus berupa angka.',
            'tahunOrBulan.required' => 'Kolom tahun atau bulan wajib diisi.',
            'tahunOrBulan.in' => 'Kolom tahun atau bulan harus bernilai "bulan" atau "tahun".',
            'gender.required' => 'Jenis kelamin wajib diisi.',
            'gender.in' => 'Jenis kelamin harus bernilai "laki-laki" atau "perempuan".',
            'kondisiKhusus.required' => 'Kondisi khusus wajib diisi.',
            'kondisiKhusus.in' => 'Kondisi khusus harus bernilai "hamil", "menyusui", atau "tidak-keduanya".',
            'umur.required' => 'Umur hamil atau menyusui wajib diisi.',
            'umur.numeric' => 'Umur hamil atau menyusui harus berupa angka.',
        ];

        // Tangkap kesalahan validasi
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'messages' => $validator->errors()
            ], 422);
        }

        // Proses permintaan setelah validasi
        $usia = intval($request->usia);
        $tahunOrBulan = $request->tahunOrBulan;
        $status = $request->gender;
        if (($tahunOrBulan == 'bulan' && $usia >= 0 && $usia <= 11) || ($tahunOrBulan == 'tahun' && $usia >= 1 && $usia <= 9)) {
            $status = "bayi";
        }
        $tabel1 = KelompokUmur::with('gizi1')->where('batas_awal', '<=', $usia)->where('batas_akhir', '>=', $usia)->where('bulan_tahun', $tahunOrBulan)->where('status', $status)->first();
        $tabel2 = KelompokUmur::with('gizi2')->where('batas_awal', '<=', $usia)->where('batas_akhir', '>=', $usia)->where('bulan_tahun', $tahunOrBulan)->where('status', $status)->first();
        $tabel3 = KelompokUmur::with('gizi3')->where('batas_awal', '<=', $usia)->where('batas_akhir', '>=', $usia)->where('bulan_tahun', $tahunOrBulan)->where('status', $status)->first();

        if ($request->has('umur')) {
            $hamilMenyusui = $request->hamilMenyusui;
            $umur = intval($request->umur);
            $tabel4 = KelompokUmur::with('gizi1')->where('batas_awal', '<=', $umur)->where('batas_akhir', '>=', $umur)->where('bulan_tahun', 'bulan')->where('status', $hamilMenyusui)->first();
            $tabel5 = KelompokUmur::with('gizi2')->where('batas_awal', '<=', $umur)->where('batas_akhir', '>=', $umur)->where('bulan_tahun', 'bulan')->where('status', $hamilMenyusui)->first();
            $tabel6 = KelompokUmur::with('gizi3')->where('batas_awal', '<=', $umur)->where('batas_akhir', '>=', $umur)->where('bulan_tahun', 'bulan')->where('status', $hamilMenyusui)->first();
            $resultTabel1 = $this->sumArray($tabel1 ? $tabel1->gizi1->toArray() : [], $tabel4 ? $tabel4->gizi1->toArray() : []);
            $resultTabel2 = $this->sumArray($tabel2 ? $tabel2->gizi2->toArray() : [], $tabel5 ? $tabel5->gizi2->toArray() : []);
            $resultTabel3 = $this->sumArray($tabel3 ? $tabel3->gizi3->toArray() : [], $tabel6 ? $tabel6->gizi3->toArray() : []);
            $responseData = ["tabel1" => $resultTabel1, "tabel2" => $resultTabel2, "tabel3" => $resultTabel3];
        } else {
            $responseData = [
                "tabel1" => $tabel1 ? $tabel1->gizi1->toArray() : [],
                "tabel2" => $tabel2 ? $tabel2->gizi2->toArray() : [],
                "tabel3" => $tabel3 ? $tabel3->gizi3->toArray() : [],
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $responseData
        ], 200);
    }


    public function sumArray($array1, $array2)
    {
        $resultArray = [];

        foreach ($array1 as $key => $value) {
            $resultArray[$key] = $value;
        }

        foreach ($array2 as $key => $value) {
            if (array_key_exists($key, $resultArray)) {
                $resultArray[$key] += $value;
            } else {
                $resultArray[$key] = $value;
            }
        }

        return $resultArray;
    }
}

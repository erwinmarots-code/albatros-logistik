<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        return response()->json(['data' => $settings]);
    }

    public function getCompanyProfile()
    {
        $profile = [
            'company_name' => Setting::get('company_name', 'Albatros Logistik'),
            'company_address' => Setting::get('company_address', 'Jl. Logistik No. 1, Makassar'),
            'company_phone' => Setting::get('company_phone', '0411-1234567'),
            'company_email' => Setting::get('company_email', 'info@albatros.com'),
            'company_tax' => Setting::get('company_tax', '01.234.567.8-901.000'),
            'logo' => Setting::get('logo', null),
        ];
        return response()->json(['data' => $profile]);
    }

    public function updateCompanyProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_address' => 'required|string',
            'company_phone' => 'required|string|max:20',
            'company_email' => 'required|email|max:255',
            'company_tax' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Setting::set('company_name', $request->company_name);
        Setting::set('company_address', $request->company_address);
        Setting::set('company_phone', $request->company_phone);
        Setting::set('company_email', $request->company_email);
        Setting::set('company_tax', $request->company_tax);

        return response()->json([
            'message' => 'Profile perusahaan berhasil diperbarui',
            'data' => $request->all()
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldLogo = Setting::get('logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        $path = $request->file('logo')->store('logos', 'public');
        Setting::set('logo', $path);

        return response()->json([
            'message' => 'Logo berhasil diupload',
            'data' => ['logo' => $path]
        ]);
    }

    public function getCodeFormats()
    {
        $formats = [
            'resi_prefix' => Setting::get('resi_prefix', 'RESI'),
            'invoice_prefix' => Setting::get('invoice_prefix', 'INV'),
            'fuel_prefix' => Setting::get('fuel_prefix', 'FUEL'),
            'maintenance_prefix' => Setting::get('maintenance_prefix', 'MNT'),
            'resi_format' => Setting::get('resi_format', '{PREFIX}-{RANDOM}'),
            'invoice_format' => Setting::get('invoice_format', '{PREFIX}-{PO}'),
            'fuel_format' => Setting::get('fuel_format', '{PREFIX}-{DATE}-{RANDOM}'),
            'maintenance_format' => Setting::get('maintenance_format', '{PREFIX}-{DATE}-{RANDOM}'),
        ];
        return response()->json(['data' => $formats]);
    }

    public function updateCodeFormats(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resi_prefix' => 'nullable|string|max:20',
            'invoice_prefix' => 'nullable|string|max:20',
            'fuel_prefix' => 'nullable|string|max:20',
            'maintenance_prefix' => 'nullable|string|max:20',
            'resi_format' => 'nullable|string|max:100',
            'invoice_format' => 'nullable|string|max:100',
            'fuel_format' => 'nullable|string|max:100',
            'maintenance_format' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->all() as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json([
            'message' => 'Format kode berhasil diperbarui',
            'data' => $request->all()
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ShippingProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index()
    {
        $invoices = Invoice::with(['shippingProject.client', 'creator'])
            ->orderBy('id', 'desc')
            ->get();
        return response()->json(['data' => $invoices]);
    }

    /**
     * Show the form for creating a new invoice (not used in API).
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_project_id' => 'required|exists:shipping_projects,id|unique:invoices,shipping_project_id',
            'due_date'           => 'nullable|date|after_or_equal:today',
            'notes'              => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $project = ShippingProject::with('client')->findOrFail($request->shipping_project_id);

        // Pastikan project status completed
        if ($project->status !== 'completed') {
            return response()->json(['message' => 'Project belum selesai, tidak dapat dibuat invoice'], 400);
        }

        // Cek apakah sudah ada invoice untuk project ini
        if ($project->invoice) {
            return response()->json(['message' => 'Project ini sudah memiliki invoice'], 400);
        }

        // Total awal bisa diambil dari goods_value, nanti di edit manual di detail
        $total = $project->goods_value ?? 0;

        $invoice = Invoice::create([
            'shipping_project_id' => $project->id,
            'invoice_number'      => Invoice::generateInvoiceNumber(),
            'invoice_date'        => now(),
            'due_date'            => $request->due_date ?? now()->addDays(14),
            'total_amount'        => $total,
            'notes'               => $request->notes,
            'created_by'          => auth()->id() ?? 1,
        ]);

        return response()->json(['message' => 'Invoice berhasil dibuat', 'data' => $invoice], 201);
    }

    /**
     * Display the specified invoice.
     */
    public function show($id)
    {
        $invoice = Invoice::with(['shippingProject.client', 'shippingProject.deliveryTasks', 'creator'])
            ->findOrFail($id);
        return response()->json(['data' => $invoice]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status'      => 'sometimes|in:draft,sent,paid,cancelled',
            'due_date'    => 'nullable|date|after_or_equal:today',
            'notes'       => 'nullable|string',
            'total_amount'=> 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $invoice->update($request->all());
        return response()->json(['message' => 'Invoice berhasil diupdate', 'data' => $invoice]);
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();
        return response()->json(['message' => 'Invoice berhasil dihapus']);
    }

    /**
     * Get projects that are completed and not yet invoiced.
     */
    public function getAvailableProjects()
    {
        try {
            $projects = ShippingProject::with('client')
                ->where('status', 'completed')
                ->whereDoesntHave('invoice')
                ->get();
            return response()->json(['data' => $projects]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get detail invoice with all related data for the detail page.
     */
    public function detail($id)
    {
        $invoice = Invoice::with([
            'shippingProject.client',
            'shippingProject.deliveryTasks',
            'creator'
        ])->findOrFail($id);
        
        return response()->json(['data' => $invoice]);
    }
}
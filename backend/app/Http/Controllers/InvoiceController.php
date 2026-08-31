<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ShippingProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Traits\BranchScopeTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\InvoicesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    use BranchScopeTrait;

    public function index(Request $request)
    {
        try {
            $query = Invoice::with(['client', 'shippingProject']);
            $this->applyBranchFilter($query);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'LIKE', "%{$search}%")
                      ->orWhereHas('client', function ($q2) use ($search) {
                          $q2->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }

            $invoices = $query->orderBy('id', 'desc')->get();
            return response()->json(['data' => $invoices]);
        } catch (\Exception $e) {
            Log::error('Error fetching invoices: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat invoice'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'invoice_number'       => 'required|string|max:50|unique:invoices,invoice_number',
                'shipping_project_id'  => 'required|exists:shipping_projects,id',
                'client_id'            => 'required|exists:clients,id',
                'total_amount'         => 'required|numeric|min:0',
                'due_date'             => 'required|date',
                'status'               => 'nullable|in:draft,sent,paid,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();

            $existing = Invoice::where('shipping_project_id', $data['shipping_project_id'])->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Project ini sudah memiliki invoice: ' . $existing->invoice_number
                ], 422);
            }

            if ($user->role !== 'super_admin') {
                $data['branch_id'] = $user->branch_id;
            } else {
                $project = ShippingProject::find($data['shipping_project_id']);
                $data['branch_id'] = $project?->branch_id;
            }

            if (!$data['branch_id']) {
                $data['branch_id'] = $user->branch_id ?? 1;
            }

            $invoice = Invoice::create($data);

            return response()->json([
                'message' => 'Invoice berhasil dibuat',
                'data'    => $invoice
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating invoice: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal membuat invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $invoice = Invoice::with(['client', 'shippingProject'])
                ->findOrFail($id);

            $user = auth()->user();
            if ($user->role !== 'super_admin' && $invoice->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke invoice ini'], 403);
            }

            return response()->json(['data' => $invoice]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $invoice = Invoice::findOrFail($id);

            if ($user->role !== 'super_admin' && $invoice->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke invoice ini'], 403);
            }

            $validator = Validator::make($request->all(), [
                'invoice_number'       => 'sometimes|string|max:50|unique:invoices,invoice_number,' . $id,
                'shipping_project_id'  => 'sometimes|exists:shipping_projects,id',
                'client_id'            => 'sometimes|exists:clients,id',
                'total_amount'         => 'sometimes|numeric|min:0',
                'due_date'             => 'sometimes|date',
                'status'               => 'nullable|in:draft,sent,paid,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $invoice->update($request->all());
            return response()->json([
                'message' => 'Invoice berhasil diperbarui',
                'data'    => $invoice
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating invoice: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui invoice'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $invoice = Invoice::findOrFail($id);

            if ($user->role !== 'super_admin' && $invoice->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke invoice ini'], 403);
            }

            $invoice->delete();
            return response()->json(['message' => 'Invoice berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting invoice: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus invoice'], 500);
        }
    }

    public function getAvailableProjects()
    {
        try {
            $user = auth()->user();
            $query = ShippingProject::whereDoesntHave('invoice')
                ->where('status', 'completed');

            $this->applyBranchFilter($query);

            $projects = $query->with(['client'])->get(['id', 'no_po', 'client_id', 'contract_value']);
            return response()->json(['data' => $projects]);
        } catch (\Exception $e) {
            Log::error('Error fetching available projects: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal memuat project tersedia: ' . $e->getMessage()
            ], 500);
        }
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    /**
     * DOWNLOAD INVOICE PDF
     */
    public function printInvoice($id)
    {
        try {
            $invoice = Invoice::with(['client', 'shippingProject.deliveryTasks'])
                ->findOrFail($id);

            $user = auth()->user();
            if ($user->role !== 'super_admin' && $invoice->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke invoice ini'], 403);
            }

            $project = $invoice->shippingProject;
            $deliveryTasks = $project->deliveryTasks ?? collect();

            $data = [
                'invoice'       => $invoice,
                'client'        => $invoice->client,
                'project'       => $project,
                'deliveryTasks' => $deliveryTasks,
                'company'       => [
                    'name'    => 'Albatros Logistik',
                    'address' => 'Jl. Logistik No. 1, Makassar',
                    'phone'   => '0411-1234567',
                    'email'   => 'info@albatros.com',
                    'tax'     => '01.234.567.8-901.000',
                ],
                'date'          => now()->format('d-m-Y'),
                'currency'      => 'Rp',
            ];

            $pdf = Pdf::loadView('pdf.invoice', $data);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->download('INVOICE-' . $invoice->invoice_number . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error downloading invoice: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'message' => 'Gagal download invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PREVIEW INVOICE PDF
     */
    public function previewInvoice($id)
    {
        try {
            $invoice = Invoice::with(['client', 'shippingProject.deliveryTasks'])
                ->findOrFail($id);

            $user = auth()->user();
            if ($user->role !== 'super_admin' && $invoice->branch_id !== $user->branch_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke invoice ini'], 403);
            }

            $project = $invoice->shippingProject;
            $deliveryTasks = $project->deliveryTasks ?? collect();

            $data = [
                'invoice'       => $invoice,
                'client'        => $invoice->client,
                'project'       => $project,
                'deliveryTasks' => $deliveryTasks,
                'company'       => [
                    'name'    => 'Albatros Logistik',
                    'address' => 'Jl. Logistik No. 1, Makassar',
                    'phone'   => '0411-1234567',
                    'email'   => 'info@albatros.com',
                    'tax'     => '01.234.567.8-901.000',
                ],
                'date'          => now()->format('d-m-Y'),
                'currency'      => 'Rp',
            ];

            $pdf = Pdf::loadView('pdf.invoice', $data);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('INVOICE-' . $invoice->invoice_number . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error previewing invoice: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'message' => 'Gagal preview invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export invoices to Excel dengan filter
     * 🔥 Method baru untuk export Excel
     */
    public function export(Request $request)
    {
        try {
            if (!Gate::allows('exportModule', 'invoices')) {
                return response()->json(['message' => 'Anda tidak memiliki izin untuk mengekspor data ini.'], 403);
            }

            $query = Invoice::with(['client', 'shippingProject']);
            $this->applyBranchFilter($query);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'LIKE', "%{$search}%")
                      ->orWhereHas('client', function ($sub) use ($search) {
                          $sub->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('branch_id') && auth()->user()->role === 'super_admin') {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $query->orderBy('id', 'desc');
            $filename = 'invoices_' . date('Y-m-d_His') . '.xlsx';

            return Excel::download(new InvoicesExport($query), $filename);

        } catch (\Exception $e) {
            Log::error('Error exporting invoices: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal ekspor data: ' . $e->getMessage()], 500);
        }
    }
}
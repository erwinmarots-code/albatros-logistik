<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #1a202c;
            margin: 0;
            padding: 40px;
        }
        .invoice-box {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 40px;
            background: white;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #2b6cb0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #0d2b45;
            margin: 0;
        }
        .header .logo {
            font-size: 18px;
            font-weight: 700;
            color: #2b6cb0;
        }
        .header .logo i {
            font-size: 24px;
        }
        .invoice-title {
            font-size: 22px;
            font-weight: 600;
            color: #2b6cb0;
            margin-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: 600;
            color: #4a5568;
        }
        .info-value {
            color: #1a202c;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .table th {
            background: #f7fafc;
            text-align: left;
            padding: 10px 12px;
            font-weight: 600;
            color: #2d3748;
            border-bottom: 2px solid #e2e8f0;
        }
        .table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f3f5;
        }
        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }
        .total-label {
            font-weight: 700;
            font-size: 18px;
            margin-right: 20px;
        }
        .total-value {
            font-weight: 700;
            font-size: 18px;
            color: #2b6cb0;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge-draft { background: #e2e8f0; color: #475569; }
        .badge-sent { background: #dbeafe; color: #1e40af; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .text-muted { color: #6b7280; font-size: 13px; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <!-- Header -->
        <div class="header">
            <div>
                <div class="logo">🚢 Albatros Logistik</div>
                <div class="text-muted">Jl. Contoh No. 123, Makassar</div>
                <div class="text-muted">Telp: (0411) 123-4567 | Email: info@albatros.com</div>
            </div>
            <div>
                <div class="invoice-title">INVOICE</div>
                <div class="text-muted">#{{ $invoice->invoice_number }}</div>
            </div>
        </div>

        <!-- Info -->
        <div style="margin-bottom: 30px;">
            <div class="info-row">
                <span class="info-label">Tanggal Invoice</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($invoice->created_at)->format('d-m-Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Jatuh Tempo</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d-m-Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Status</span>
                <span class="info-value"><span class="badge badge-{{ $invoice->status }}">{{ $invoice->status }}</span></span>
            </div>
            <div class="info-row">
                <span class="info-label">Client</span>
                <span class="info-value">{{ $invoice->client->name ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Project</span>
                <span class="info-value">{{ $invoice->shippingProject->no_po ?? '-' }}</span>
            </div>
        </div>

        <!-- Items Table (jika ada invoice items) -->
        <table class="table">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th>Qty</th>
                    <th>Harga Satuan</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items ?? [] as $item)
                <tr>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td>{{ $item->quantity ?? 1 }}</td>
                    <td>Rp {{ number_format($item->unit_price ?? 0, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item->total ?? 0, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #6b7280;">Tidak ada item rincian.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Total -->
        <div class="total-row">
            <span class="total-label">Total</span>
            <span class="total-value">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
        </div>

        <!-- Footer -->
        <div class="footer">
            Terima kasih atas kepercayaan Anda. &bull; Invoice ini adalah dokumen resmi.
            <br>Albatros Logistik &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
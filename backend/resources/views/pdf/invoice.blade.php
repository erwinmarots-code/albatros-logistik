<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>INVOICE - {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            color: #1a202c;
            background: #fff;
            padding: 20px;
        }
        .invoice-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px 44px;
            border: 1px solid #e2e8f0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #1a4a7a;
            padding-bottom: 18px;
            margin-bottom: 22px;
        }
        .header-left .logo {
            font-size: 28px;
            font-weight: 800;
            color: #0d2b45;
        }
        .header-left .logo span { color: #2b6cb0; }
        .header-left .tagline {
            font-size: 13px;
            color: #4a5568;
            margin-top: 2px;
        }
        .header-right {
            text-align: right;
        }
        .header-right .invoice-title {
            font-size: 30px;
            font-weight: 700;
            color: #2b6cb0;
            letter-spacing: 2px;
        }
        .header-right .invoice-number {
            font-size: 16px;
            font-weight: 600;
            color: #1a202c;
            margin-top: 2px;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 16px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .status-draft   { background: #e2e8f0; color: #475569; }
        .status-sent    { background: #dbeafe; color: #1e40af; }
        .status-paid    { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        /* INFO TABLE */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .info-table td {
            vertical-align: top;
            padding: 4px 0;
            font-size: 13px;
            line-height: 1.6;
        }
        .info-table .left-col {
            width: 50%;
        }
        .info-table .right-col {
            width: 50%;
            text-align: right;
        }
        .info-table .label {
            font-weight: 600;
            color: #4a5568;
            display: inline-block;
            min-width: 80px;
        }

        .table-container {
            margin: 18px 0 22px 0;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table thead {
            background: #f7fafc;
            border-bottom: 2px solid #e2e8f0;
        }
        table thead th {
            padding: 10px 8px;
            text-align: left;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            color: #4a5568;
            letter-spacing: 0.3px;
        }
        table tbody td {
            padding: 8px 8px;
            border-bottom: 1px solid #edf2f7;
            vertical-align: middle;
        }
        table tbody tr:last-child td {
            border-bottom: none;
        }
        .text-right { text-align: right; }

        /* ===== TOTAL DI KANAN DENGAN Rp RAPI ===== */
        .totals-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
            width: 100%;
        }
        .totals-table {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 3px 0;
            font-size: 12px;
            color: #1a202c;
            vertical-align: top;
        }
        .totals-table .label-cell {
            font-weight: 600;
            color: #4a5568;
            width: 120px;
        }
        .totals-table .value-cell {
            text-align: right;
            font-weight: 600;
            padding-left: 20px;
            white-space: nowrap;
        }
        /* Rp dan angka dalam satu baris dengan spasi konsisten */
        .totals-table .currency-symbol {
            display: inline-block;
            width: 22px;
            text-align: left;
            font-weight: 600;
        }
        .totals-table .currency-value {
            display: inline-block;
            text-align: right;
            min-width: 80px;
            font-weight: 600;
        }
        .totals-table .total-row td {
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
        .totals-table .grand-total td {
            font-size: 14px;
            font-weight: 800;
            color: #0d2b45;
            border-top: 2px solid #2b6cb0;
            padding-top: 8px;
        }
        .totals-table .grand-total .value-cell .currency-value {
            color: #2b6cb0;
        }

        .notes {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #4a5568;
            line-height: 1.5;
        }
        .footer {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 13px;
            color: #2b6cb0;
            font-weight: 600;
        }
        @media print {
            body { background: #fff; padding: 10px; }
            .invoice-wrapper { border: none; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="invoice-wrapper">
    <!-- HEADER -->
    <div class="header">
        <div class="header-left">
            <div class="logo">Albatros <span>Logistik</span></div>
            <div class="tagline">Solusi Logistik Terpercaya</div>
        </div>
        <div class="header-right">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-number">#{{ $invoice->invoice_number ?? 'N/A' }}</div>
            <div><span class="status-badge status-{{ $invoice->status ?? 'draft' }}">{{ strtoupper($invoice->status ?? 'draft') }}</span></div>
        </div>
    </div>

    <!-- INFO -->
    <table class="info-table">
        <tr>
            <td class="left-col">
                <strong>KEPADA:</strong><br>
                {{ $client->name ?? '-' }}<br>
                {{ $client->address ?? '-' }}<br>
                Telp: {{ $client->phone ?? '-' }}<br>
                Email: {{ $client->email ?? '-' }}
            </td>
            <td class="right-col">
                <span class="label">No PO</span> : {{ $project->no_po ?? '-' }}<br>
                <span class="label">Tanggal Invoice</span> : {{ $date ?? now()->format('d-m-Y') }}<br>
                <span class="label">Periode</span> :
                {{ \Carbon\Carbon::parse($invoice->created_at ?? now())->format('d-m-Y') }}
                s.d
                {{ \Carbon\Carbon::parse($invoice->due_date ?? now())->format('d-m-Y') }}<br>
                <span class="label">Total Resi</span> : {{ $deliveryTasks->count() ?? 0 }} transaksi
            </td>
        </tr>
    </table>

    <!-- TABEL RESI -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No. Resi</th>
                    <th>Tgl Pickup</th>
                    <th>Penerima</th>
                    <th>Kota Tujuan</th>
                    <th>Jenis</th>
                    <th>Berat</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveryTasks as $task)
                @php
                    $receiverName = $task->receiver_name ?? $project->receiver_name ?? '-';
                    $receiverAddress = $task->receiver_address ?? $project->receiver_address ?? '';
                    $cityParts = explode(',', $receiverAddress);
                    $receiverCity = trim(end($cityParts)) ?: '-';
                    $weight = $project->weight_kg ?? 0;
                    $weightDisplay = $weight > 0 ? number_format($weight, 0) . ' Kg' : '-';
                @endphp
                <tr>
                    <td>{{ $task->no_resi ?? '-' }}</td>
                    <td>{{ $task->tanggal ? \Carbon\Carbon::parse($task->tanggal)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $receiverName }}</td>
                    <td>{{ $receiverCity }}</td>
                    <td>{{ $project->goods_description ?? 'paket' }}</td>
                    <td>{{ $weightDisplay }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center; padding:20px;">Tidak ada data resi</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ===== TOTAL DI KANAN DENGAN Rp RAPI ===== -->
    <div class="totals-wrapper">
        <table class="totals-table">
            <tr>
                <td class="label-cell">Subtotal</td>
                <td class="value-cell">
                    <span class="currency-symbol">Rp</span>
                    <span class="currency-value">{{ number_format($invoice->total_amount ?? 0, 0, ',', '.') }}</span>
                </td>
            </tr>
            <tr>
                <td class="label-cell">Pajak (0%)</td>
                <td class="value-cell">
                    <span class="currency-symbol">Rp</span>
                    <span class="currency-value">0</span>
                </td>
            </tr>
            <tr class="total-row">
                <td class="label-cell" style="font-weight:700;">Total</td>
                <td class="value-cell" style="font-weight:700;">
                    <span class="currency-symbol">Rp</span>
                    <span class="currency-value">{{ number_format($invoice->total_amount ?? 0, 0, ',', '.') }}</span>
                </td>
            </tr>
            <tr class="grand-total">
                <td>TOTAL TAGIHAN</td>
                <td class="value-cell">
                    <span class="currency-symbol">Rp</span>
                    <span class="currency-value">{{ number_format($invoice->total_amount ?? 0, 0, ',', '.') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- NOTES -->
    <div class="notes">
        <p><strong>Catatan:</strong> Invoice ini wajib dibayar sesuai dengan tanggal jatuh tempo yang tertera ({{ \Carbon\Carbon::parse($invoice->due_date ?? now())->format('d-m-Y') }}).</p>
        <p>Pembayaran dapat dilakukan melalui transfer bank ke rekening perusahaan. Terima kasih atas kepercayaan Anda.</p>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        Terima kasih atas kerjasama Anda,<br>
        <strong>{{ $company['name'] ?? 'Albatros Logistik' }}</strong>
    </div>
</div>

</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Struk Pemesanan</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px; /* diperbesar dari 12px */
            line-height: 1.4;
            margin: 20px;
            width: 350px; /* diperbesar dari 300px */
        }
        .container {
            border: 1px dashed #000;
            padding: 20px; /* padding diperbesar */
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 16px; /* diperbesar */
            margin: 0;
            font-weight: bold;
        }
        .header p {
            margin: 2px 0;
            font-size: 13px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .details p {
            margin: 4px 0;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }
        .details p label {
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>GrainDryer Struk Pemesanan</h1>
            <p>GrainDryer IoT</p>
            <div class="divider"></div>
        </div>
        <div class="details">
            <p><label>No. Struk:</label> <span>{{ $pesanan->nomor_struk }}</span></p>
            <p><label>Tanggal:</label> <span>{{ $tanggal }}</span></p>
            <p><label>Waktu:</label> <span>{{ $waktu }}</span></p>
            <p><label>Nama Pelanggan:</label> <span>{{ $user->name }}</span></p>
            <p><label>Email:</label> <span>{{ $user->email }}</span></p>
            <div class="divider"></div>
            <p><label>Paket:</label> <span>{{ $paket->nama_paket }}</span></p>
            <p><label>Total:</label> <span>Rp {{ number_format($paket->harga, 0, ',', '.') }}</span></p>
            <div class="divider"></div>
            <p><label>Alamat Pengiriman:</label> <span>{{ $pesanan->alamat }}</span></p>
            <p><label>Catatan:</label> <span>{{ $pesanan->catatan ?? '-' }}</span></p>
        </div>
        <div class="footer">
            <p>Terima kasih atas pemesanan Anda!</p>
            <p>Tim Kami akan menghubungi Anda Segera!</p>
            <p>GrainDryer IoT - Solusi Pertanian Modern</p>
        </div>
    </div>
</body>
</html>

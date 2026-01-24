<?php
include '../../config/koneksi.php';

$id_bon = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

$sql = "SELECT b.*, m.nama_barang, m.satuan 
        FROM bon_permintaan b 
        LEFT JOIN master_barang m ON b.id_barang = m.id_barang 
        WHERE b.id_bon = '$id_bon'";

$query = mysqli_query($koneksi, $sql);
$data  = mysqli_fetch_array($query);

if (!$data) {
    echo "<div style='text-align:center; padding:50px;'><h3>Data Tidak Ditemukan</h3><a href='pengambilan.php'>Kembali</a></div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bon A5 - <?= $data['no_permintaan'] ?></title>
     <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    /* 1. Atur Ukuran Kertas Secara Fisik */
    @page {
        size: A5 landscape;
        margin: 0; /* Kita atur margin di level kontainer saja */
    }

    body { 
        font-family: 'Courier New', Courier, monospace; 
        background-color: #f0f0f0; /* Warna ini hanya muncul di layar */
        margin: 0;
        padding: 0;
    }

    /* 2. Kontainer Utama */
    .container-a5 { 
        background: white !important; 
        width: 210mm; 
        height: 148mm; 
        margin: 10px auto; 
        padding: 10mm; 
        border: 1px solid #ccc;
        box-sizing: border-box;
        position: relative;
        /* Memaksa warna muncul saat diprint */
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* 3. Aturan Khusus Saat Tombol Print Ditekan */
    @media print {
        body { 
            background: none; 
        }
        .container-a5 { 
            margin: 0; 
            border: none; 
            box-shadow: none;
            width: 210mm;
            height: 148mm;
        }
        .no-print { 
            display: none !important; 
        }
        
        /* Memastikan tabel tetap memiliki garis saat diprint */
        .table-bordered th, .table-bordered td {
            border: 1px solid black !important;
        }
    }

    .header-bon { border-bottom: 2px solid #000; margin-bottom: 10px; }
    .table-items th { background-color: #eeeeee !important; }
    .ttd-section { position: absolute; bottom: 10mm; left: 10mm; right: 10mm; }
    .ttd-box { height: 40px; }
</style>
</head>
<body onload="window.print()">

    <div class="container-a5">
        <div class="header-bon d-flex justify-content-between align-items-end pb-2">
            <div>
                <h5 class="fw-bold mb-0">BUKTI PERMINTAAN BARANG</h5>
                <small>PT. MUTIARA CAHAYA PLASTINDO</small>
            </div>
            <div class="text-end">
                <h6 class="mb-0">NO: <?= $data['no_permintaan'] ?></h6>
                <small>Tgl: <?= date('d/m/Y', strtotime($data['tgl_keluar'])) ?></small>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-6">
                <table>
                    <tr><td>Penerima</td><td>: <b><?= strtoupper($data['penerima']) ?></b></td></tr>
                </table>
            </div>
            <div class="col-6 text-end">
                <table>
                    <tr><td>Keperluan</td><td>: <?= strtoupper($data['keperluan']) ?></td></tr>
                </table>
            </div>
        </div>

        <table class="table table-bordered border-dark table-items">
            <thead class="text-center small">
                <tr>
                    <th width="5%">NO</th>
                    <th>NAMA BARANG / ITEMS</th>
                    <th width="15%">QTY</th>
                    <th width="15%">SATUAN</th>
                </tr>
            </thead>
            <tbody>
                <tr style="height: 60px; vertical-align: middle;">
                    <td class="text-center">1</td>
                    <td class="fw-bold"><?= strtoupper($data['nama_barang']) ?></td>
                    <td class="text-center fw-bold fs-5"><?= number_format($data['qty_keluar'], 0) ?></td>
                    <td class="text-center"><?= strtoupper($data['satuan']) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="row ttd-section text-center">
            <div class="col-4">
                <small>Menyerahkan,</small>
                <div class="ttd-box"></div>
                <small class="fw-bold">( ____________ )</small>
            </div>
            <div class="col-4">
                <small>Penerima,</small>
                <div class="ttd-box"></div>
                <small class="fw-bold">( ____________ )</small>
            </div>
            <div class="col-4">
                <small>Mengetahui,</small>
                <div class="ttd-box"></div>
                <small class="fw-bold">( ____________ )</small>
            </div>
        </div>
    </div>

    <div class="text-center no-print mt-3">
        <button onclick="window.print()" class="btn btn-primary">Cetak</button>
        <a href="pengambilan.php" class="btn btn-secondary">Kembali</a>
    </div>

</body>
</html>
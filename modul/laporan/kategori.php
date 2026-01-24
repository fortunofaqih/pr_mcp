<?php
session_start();
include '../../config/koneksi.php';

if ($_SESSION['status'] != "login") {
    header("location:../../login.php?pesan=belum_login");
    exit;
}

$bulan_filter = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_filter = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Array Nama Bulan Indonesia
$bulan_indo = [
    '01' => 'JANUARI', '02' => 'FEBRUARI', '03' => 'MARET', '04' => 'APRIL',
    '05' => 'MEI', '06' => 'JUNI', '07' => 'JULI', '08' => 'AGUSTUS',
    '09' => 'SEPTEMBER', '10' => 'OKTOBER', '11' => 'NOVEMBER', '12' => 'DESEMBER'
];
$nama_bulan = $bulan_indo[$bulan_filter];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kategori - MCP</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border-radius: 12px; }
        .table thead { background-color: #2d3436; color: white; }
        .progress { border-radius: 10px; background-color: #e9ecef; }
        
        /* Styling Khusus Cetak */
        @media print { 
            .no-print { display: none !important; } 
            body { background-color: white; }
            .card { box-shadow: none !important; border: 1px solid #ddd; }
            .print-header { display: block !important; }
        }
        .print-header { display: none; text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    
    <div class="print-header">
        <h3 class="mb-0">PT. MUTIARACAHAYA PLASTINDO</h3>
        <p class="mb-0">LAPORAN ANALISIS PENGELUARAN PER KATEGORI</p>
        <small>Periode: <?= $nama_bulan ?> <?= $tahun_filter ?></small>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h4 class="fw-bold text-dark mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>ANALISIS KATEGORI</h4>
            <p class="text-muted small mb-0">Memantau distribusi biaya pembelian barang</p>
        </div>
        <div class="d-flex gap-2">
            <form action="" method="GET" class="d-flex gap-2">
                <select name="bulan" class="form-select form-select-sm">
                    <?php foreach($bulan_indo as $m => $nm): ?>
                        <option value="<?= $m ?>" <?= ($m == $bulan_filter) ? "selected" : "" ?>><?= $nm ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun" class="form-select form-select-sm">
                    <?php for ($y=date('Y'); $y>=2024; $y--): ?>
                        <option value="<?= $y ?>" <?= ($y == $tahun_filter) ? "selected" : "" ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <a href="../../index.php" class="btn btn-danger btn-sm"><i class="fas fa-rotate-left"></i> Kembali</a>
                <button type="submit" class="btn btn-primary btn-sm px-3">FILTER</button>
            </form>
            <button onclick="window.print()" class="btn btn-success btn-sm"><i class="fas fa-print me-1"></i> CETAK</button>
            
        </div>
    </div>

    <div class="row">
        <?php
            $total_all_query = mysqli_query($koneksi, "SELECT SUM(harga) as grand_total, COUNT(*) as total_transaksi FROM pembelian WHERE MONTH(tgl_beli) = '$bulan_filter' AND YEAR(tgl_beli) = '$tahun_filter'");
            $summary = mysqli_fetch_assoc($total_all_query);
            $grand_total = $summary['grand_total'] ?? 0;
        ?>
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase small mb-1" style="opacity: 0.8;">Total Biaya Periode Ini</h6>
                        <h2 class="fw-bold mb-0">Rp <?= number_format($grand_total, 0, ',', '.') ?></h2>
                    </div>
                    <div class="text-end">
                        <h6 class="text-uppercase small mb-1" style="opacity: 0.8;">Total Transaksi</h6>
                        <h3 class="fw-bold mb-0"><?= $summary['total_transaksi'] ?? 0 ?> <small style="font-size: 1rem;">Record</small></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-dark">RINCIAN DISTRIBUSI</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-uppercase">
                                    <th class="ps-4">Kategori Pembelian</th>
                                    <th class="text-center">Frekuensi</th>
                                    <th class="text-end pe-4">Total Biaya</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q = mysqli_query($koneksi, "
                                    SELECT kategori_beli, COUNT(*) as jml_transaksi, SUM(harga) as subtotal
                                    FROM pembelian
                                    WHERE MONTH(tgl_beli) = '$bulan_filter' AND YEAR(tgl_beli) = '$tahun_filter'
                                    GROUP BY kategori_beli
                                    ORDER BY subtotal DESC
                                ");

                                if(mysqli_num_rows($q) > 0) {
                                    while($data = mysqli_fetch_array($q)) {
                                        $persen = ($grand_total > 0) ? ($data['subtotal'] / $grand_total) * 100 : 0;
                                        $kategori_nama = !empty($data['kategori_beli']) ? $data['kategori_beli'] : "TIDAK TERKATEGORI";
                                        
                                        // Warna progress bar dinamis
                                        $color = "bg-primary";
                                        if($persen > 50) $color = "bg-danger";
                                        else if($persen > 25) $color = "bg-warning";
                                        ?>
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-dark"><?= $kategori_nama ?></span>
                                                    <small class="text-muted"><?= number_format($persen, 1) ?>%</small>
                                                </div>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar <?= $color ?>" style="width: <?= $persen ?>%"></div>
                                                </div>
                                            </td>
                                            <td class="text-center fw-bold text-muted"><?= $data['jml_transaksi'] ?> <small>x</small></td>
                                            <td class="text-end pe-4 fw-bold text-dark">Rp <?= number_format($data['subtotal'], 0, ',', '.') ?></td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='3' class='text-center py-5 text-muted'>Belum ada data pada periode ini</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 no-print">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="small fw-bold text-muted text-uppercase mb-3">Informasi Laporan</h6>
                    <div class="d-flex align-items-start mb-3">
                        <i class="fas fa-info-circle text-primary mt-1 me-2"></i>
                        <p class="small text-muted mb-0">Laporan ini mengelompokkan pengeluaran berdasarkan kategori untuk membantu manajemen melihat alokasi dana terbesar.</p>
                    </div>
                    <div class="alert alert-warning py-2 mb-0">
                        <small><i class="fas fa-exclamation-triangle me-1"></i> Data diurutkan dari biaya tertinggi.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
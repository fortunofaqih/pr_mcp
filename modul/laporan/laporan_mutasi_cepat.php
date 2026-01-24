<?php
session_start();
include '../../config/koneksi.php';

if ($_SESSION['status'] != "login") {
    header("location:../../login.php");
    exit;
}

// 1. Ambil Parameter Filter
$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$huruf_awal  = $_GET['huruf_awal'] ?? 'A';
$huruf_akhir = $_GET['huruf_akhir'] ?? 'I';

/** * 2. QUERY ALGORITMA SATU PINTU (Sangat Cepat)
 * Kita menghitung semua angka dalam satu query agar database 
 * hanya bekerja satu kali, bukan ribuan kali.
 */
$sql = "SELECT 
            m.id_barang, 
            m.nama_barang, 
            m.satuan,
            -- Hitung Saldo Awal (Transaksi sebelum tgl_mulai)
            COALESCE(awal.total_awal, 0) as stok_awal,
            -- Hitung Mutasi Masuk & Keluar (Dalam Periode)
            COALESCE(mutasi.m_masuk, 0) as masuk,
            COALESCE(mutasi.m_keluar, 0) as keluar,
            -- Hitung Stok Akhir
            (COALESCE(awal.total_awal, 0) + COALESCE(mutasi.m_masuk, 0) - COALESCE(mutasi.m_keluar, 0)) as stok_akhir
        FROM master_barang m
        
        -- Subquery Saldo Awal
        LEFT JOIN (
            SELECT id_barang, 
            SUM(CASE WHEN tipe_transaksi = 'MASUK' THEN qty ELSE -qty END) as total_awal
            FROM tr_stok_log 
            WHERE tgl_log < '$tgl_mulai 00:00:00'
            GROUP BY id_barang
        ) awal ON m.id_barang = awal.id_barang

        -- Subquery Mutasi Periode
        LEFT JOIN (
            SELECT id_barang,
            SUM(CASE WHEN tipe_transaksi = 'MASUK' THEN qty ELSE 0 END) as m_masuk,
            SUM(CASE WHEN tipe_transaksi = 'KELUAR' THEN qty ELSE 0 END) as m_keluar
            FROM tr_stok_log
            WHERE tgl_log BETWEEN '$tgl_mulai 00:00:00' AND '$tgl_selesai 23:59:59'
            GROUP BY id_barang
        ) mutasi ON m.id_barang = mutasi.id_barang

        WHERE LEFT(m.nama_barang, 1) BETWEEN '$huruf_awal' AND '$huruf_akhir'
        ORDER BY m.nama_barang ASC";

$query = mysqli_query($koneksi, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Mutasi Cepat</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f4f7f6; font-size: 13px; }
        .table-primary-dark { background: #00008B; color: white; }
        .bg-awal { background-color: #f8f9fa; }
        .bg-akhir { background-color: #fffdf0; font-weight: bold; }
        @media print {
            .no-print { display: none; }
            @page { size: landscape; }
        }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 no-print">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="fw-bold small">Huruf: <?= $huruf_awal ?> - <?= $huruf_akhir ?></label>
                    <div class="input-group input-group-sm">
                        <select name="huruf_awal" class="form-select"><?php foreach(range('A','Z') as $l) echo "<option ".($l==$huruf_awal?'selected':'').">$l</option>"; ?></select>
                        <select name="huruf_akhir" class="form-select"><?php foreach(range('A','Z') as $l) echo "<option ".($l==$huruf_akhir?'selected':'').">$l</option>"; ?></select>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="fw-bold small">Mulai</label>
                    <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= $tgl_mulai ?>">
                </div>
                <div class="col-md-2">
                    <label class="fw-bold small">Selesai</label>
                    <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= $tgl_selesai ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-dark w-100">Filter Data</button>
                </div>
                
                <div class="col-md-4 text-end">
                <a href="../../index.php" class="btn btn-danger px-2"><i class="fas fa-rotate-left"></i>Kembali</a>    
                <button type="button" onclick="window.print()" class="btn btn-sm btn-primary">Cetak Laporan</button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="text-center mb-4">
                <h4 class="fw-bold mb-1">LAPORAN MUTASI BARANG GUDANG</h4>
                <p class="text-muted">Periode: <?= date('d/m/Y', strtotime($tgl_mulai)) ?> - <?= date('d/m/Y', strtotime($tgl_selesai)) ?></p>
            </div>

            <table class="table table-bordered table-sm">
                <thead>
                    <tr class="table-primary-dark text-center">
                        <th rowspan="2" class="align-middle">NO</th>
                        <th rowspan="2" class="align-middle">NAMA BARANG</th>
                        <th rowspan="2" class="align-middle">SATUAN</th>
                       <!-- <th rowspan="2" class="align-middle bg-opacity-10 text-dark">STOK AWAL</th>-->
                        <th colspan="2">MUTASI PERIODE</th>
                        <th rowspan="2" class="align-middle bg-opacity-10 text-dark">STOK AKHIR</th>
                    </tr>
                    <tr class="table-primary-dark text-center">
                        <th>MASUK (+)</th>
                        <th>KELUAR (-)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    while($row = mysqli_fetch_array($query)): 
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="fw-bold text-uppercase"><?= $row['nama_barang'] ?></td>
                        <td class="text-center"><?= $row['satuan'] ?></td>
                        <!--<td class="text-center bg-awal"><?= number_format($row['stok_awal'], 0) ?></td>-->
                        <td class="text-center text-success"><?= $row['masuk'] > 0 ? number_format($row['masuk'], 0) : '-' ?></td>
                        <td class="text-center text-danger"><?= $row['keluar'] > 0 ? number_format($row['keluar'], 0) : '-' ?></td>
                        <td class="text-center bg-akhir"><?= number_format($row['stok_akhir'], 0) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
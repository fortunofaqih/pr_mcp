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
$search_nama = mysqli_real_escape_string($koneksi, $_GET['search_nama'] ?? '');
$filter_rak  = mysqli_real_escape_string($koneksi, $_GET['filter_rak'] ?? '');

// 2. Query
$sql = "SELECT 
            m.id_barang, m.nama_barang, m.satuan, m.lokasi_rak,
            COALESCE(awal.total_awal, 0) as stok_awal,
            COALESCE(mutasi.m_masuk, 0) as masuk,
            COALESCE(mutasi.m_keluar, 0) as keluar,
            (COALESCE(awal.total_awal, 0) + COALESCE(mutasi.m_masuk, 0) - COALESCE(mutasi.m_keluar, 0)) as stok_akhir
        FROM master_barang m
        LEFT JOIN (
            SELECT id_barang, SUM(CASE WHEN tipe_transaksi = 'MASUK' THEN qty ELSE -qty END) as total_awal
            FROM tr_stok_log WHERE tgl_log < '$tgl_mulai 00:00:00' GROUP BY id_barang
        ) awal ON m.id_barang = awal.id_barang
        LEFT JOIN (
            SELECT id_barang, SUM(CASE WHEN tipe_transaksi = 'MASUK' THEN qty ELSE 0 END) as m_masuk,
            SUM(CASE WHEN tipe_transaksi = 'KELUAR' THEN qty ELSE 0 END) as m_keluar
            FROM tr_stok_log WHERE tgl_log BETWEEN '$tgl_mulai 00:00:00' AND '$tgl_selesai 23:59:59' GROUP BY id_barang
        ) mutasi ON m.id_barang = mutasi.id_barang
        WHERE 1=1";

if ($search_nama != '') {
    $sql .= " AND m.nama_barang LIKE '%$search_nama%'";
} elseif ($filter_rak != '') {
    $sql .= " AND m.lokasi_rak = '$filter_rak'";
} else {
    $sql .= " AND LEFT(m.nama_barang, 1) BETWEEN '$huruf_awal' AND '$huruf_akhir'";
}

$sql .= " ORDER BY m.lokasi_rak ASC, m.nama_barang ASC";
$query = mysqli_query($koneksi, $sql);

$data_tabel = [];
$rak_list_for_jump = [];
while($row = mysqli_fetch_assoc($query)) {
    $data_tabel[] = $row;
    if (!empty($row['lokasi_rak']) && !in_array($row['lokasi_rak'], $rak_list_for_jump)) {
        $rak_list_for_jump[] = $row['lokasi_rak'];
    }
}
$list_rak = mysqli_query($koneksi, "SELECT DISTINCT lokasi_rak FROM master_barang WHERE lokasi_rak != '' ORDER BY lokasi_rak ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Mutasi & SO</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html { scroll-behavior: smooth; }
        body { background:#f4f7f6; font-size: 11px; }
        
        /* Warna Biru MCP Baru (Tulisan Putih) */
        .table-mcp { background-color: #0d6efd !important; color: #ffffff !important; }
        .table thead th { 
            position: sticky; top: 0; z-index: 10; 
            background-color: #0d6efd !important; 
            color: white !important;
            border: 1px solid #ffffff !important;
            text-transform: uppercase;
        }

        .sticky-filter { position: sticky; top: 0; z-index: 1020; background: #f4f7f6; padding-top: 10px; }
        .table-scroll-container { max-height: 55vh; overflow-y: auto; background: white; border: 1px solid #dee2e6; }
        tr[id^="target-"] { scroll-margin-top: 150px; }
        .bg-akhir { background-color: #fffdf0 !important; font-weight: bold; }
        .cek-box { width: 16px; height: 16px; border: 1px solid #000; display: inline-block; }

        /* Tanda Tangan */
        .ttd-container { margin-top: 30px; display: flex; justify-content: space-between; text-align: center; }
        .ttd-box { width: 200px; }
        .ttd-space { height: 60px; }

        @media print { 
            .no-print { display: none !important; } 
            .table-scroll-container { max-height: none !important; overflow: visible !important; border: none !important; }
            body { background: white; padding: 0; }
            .card { border: none !important; box-shadow: none !important; }
            @page { size: landscape; margin: 0.5cm; }
        }
    </style>
</head>
<body class="p-3">

<div class="container-fluid">
    <div class="sticky-filter no-print">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="fw-bold small text-muted">ALFABET</label>
                        <div class="input-group input-group-sm">
                            <select name="huruf_awal" class="form-select"><?php foreach(range('A','Z') as $l) echo "<option ".($l==$huruf_awal?'selected':'').">$l</option>"; ?></select>
                            <select name="huruf_akhir" class="form-select"><?php foreach(range('A','Z') as $l) echo "<option ".($l==$huruf_akhir?'selected':'').">$l</option>"; ?></select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="fw-bold small text-muted">CARI NAMA</label>
                        <input type="text" name="search_nama" class="form-control form-control-sm" value="<?= $search_nama ?>" placeholder="Nama barang...">
                    </div>
                    <div class="col-md-2">
                        <label class="fw-bold small text-muted">RAK</label>
                        <select name="filter_rak" class="form-select form-select-sm">
                            <option value="">-- SEMUA RAK --</option>
                            <?php 
                            mysqli_data_seek($list_rak, 0); 
                            while($rk = mysqli_fetch_array($list_rak)) echo "<option ".($filter_rak==$rk['lokasi_rak']?'selected':'').">".$rk['lokasi_rak']."</option>"; 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold small text-muted">PERIODE</label>
                        <div class="input-group input-group-sm">
                            <input type="date" name="tgl_mulai" class="form-control" value="<?= $tgl_mulai ?>">
                            <input type="date" name="tgl_selesai" class="form-control" value="<?= $tgl_selesai ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-1 justify-content-end">
                            <button type="submit" class="btn btn-sm btn-dark px-3 fw-bold">
                                <i class="fas fa-filter me-1"></i> FILTER
                            </button>
                            <a href="laporan_mutasi_cepat.php" class="btn btn-sm btn-outline-secondary px-3">
                                <i class="fas fa-sync me-1"></i> RESET
                            </a>
                            <a href="../../index.php" class="btn btn-sm btn-danger px-3 fw-bold">
                                <i class="fas fa-arrow-left me-1"></i> KEMBALI
                            </a>
                        </div>
                    </div>
                </form>

                <?php if ($filter_rak == '' && count($rak_list_for_jump) > 1): ?>
                <div class="mt-2 pt-2 border-top">
                    <small class="fw-bold text-muted me-2">LOKASI:</small>
                    <?php foreach($rak_list_for_jump as $rk): $target = preg_replace("/[^A-Za-z0-9]/", "", $rk); ?>
                        <a href="#target-<?= $target ?>" class="btn btn-outline-primary btn-sm fw-bold mb-1" style="font-size: 9px; padding: 1px 6px;"><?= $rk ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="text-center mb-4">
                <h4 class="fw-bold mb-0">LAPORAN MUTASI & STOK OPNAME GUDANG</h4>
                <p class="text-muted fw-bold">Periode: <?= date('d/m/Y', strtotime($tgl_mulai)) ?> s/d <?= date('d/m/Y', strtotime($tgl_selesai)) ?></p>
                
                <div class="no-print mt-2 d-flex justify-content-center gap-2">
                    <button type="button" onclick="window.print()" class="btn btn-sm btn-primary px-4 fw-bold">
                        <i class="fas fa-print me-2"></i> CETAK SO (PDF)
                    </button>
                    <a href="export_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-sm btn-success px-4 fw-bold">
                        <i class="fas fa-file-excel me-2"></i> EXPORT EXCEL
                    </a>
                </div>
            </div>

            <div class="table-scroll-container">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-mcp text-center align-middle">
                        <tr>
                            <th width="30">NO</th>
                            <th>NAMA BARANG</th>
                            <th width="80">RAK</th>
                            <th width="80">SATUAN</th>
                            <th width="80">MASUK</th>
                            <th width="80">KELUAR</th>
                            <th width="100">STOK AKHIR</th>
                            <th width="100">STOK FISIK</th>
                            <th width="40">CEK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; $last_rak = null; 
                        foreach($data_tabel as $row): 
                            if ($filter_rak == '' && $row['lokasi_rak'] !== $last_rak): 
                                $rak_id = preg_replace("/[^A-Za-z0-9]/", "", $row['lokasi_rak'] ?: 'TANPARAK');
                        ?>
                            <tr id="target-<?= $rak_id ?>" class="table-light fw-bold no-print">
                                <td colspan="9" class="ps-3 py-1 text-primary bg-light border-bottom border-primary">
                                    <i class="fas fa-warehouse me-2"></i> LOKASI RAK: <?= $row['lokasi_rak'] ?: 'TANPA RAK' ?>
                                </td>
                            </tr>
                        <?php $last_rak = $row['lokasi_rak']; endif; ?>
                        <tr class="align-middle">
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="fw-bold text-uppercase"><?= $row['nama_barang'] ?></td>
                            <td class="text-center bg-light small"><?= $row['lokasi_rak'] ?: '-' ?></td>
                            <td class="text-center"><?= $row['satuan'] ?></td>
                            <td class="text-center text-success fw-bold"><?= $row['masuk'] ?: '-' ?></td>
                            <td class="text-center text-danger fw-bold"><?= $row['keluar'] ?: '-' ?></td>
                            <td class="text-center bg-akhir fs-6"><?= number_format($row['stok_akhir'], 0) ?></td>
                            <td style="border-bottom: 2px solid #000 !important; background:#fff;"></td>
                            <td class="text-center"><div class="cek-box"></div></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="ttd-container mt-5">
                <div class="ttd-box">
                    <p class="mb-0 small">Dibuat Oleh,</p>
                    <div class="ttd-space"></div>
                    <p class="fw-bold border-top pt-1 text-uppercase">Admin Gudang</p>
                </div>
                <div class="ttd-box">
                    <p class="mb-0 small">Diperiksa Oleh,</p>
                    <div class="ttd-space"></div>
                    <p class="fw-bold border-top pt-1 text-uppercase">Kepala Gudang</p>
                </div>
                <div class="ttd-box">
                    <p class="mb-0 small">Diketahui Oleh,</p>
                    <div class="ttd-space"></div>
                    <p class="fw-bold border-top pt-1 text-uppercase">Manager / Direksi</p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
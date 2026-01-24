<?php
session_start();
include '../../config/koneksi.php';

// Proteksi Login
if ($_SESSION['status'] != "login") {
    header("location:../../login.php?pesan=belum_login");
    exit;
}

// Ambil filter bulan, tahun & search (default bulan & tahun saat ini)
$bulan  = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun  = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', 
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', 
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <title>Laporan Mobil - <?= $nama_bulan[$bulan] ?> <?= $tahun ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .table-laporan {
            font-size: 0.8rem;
        }
        
        .table-laporan thead th {
            background-color: #2c3e50;
            color: #ffffff;
            text-align: center;
            vertical-align: middle;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px;
        }

        .harga-detail {
            font-size: 0.75rem;
            color: #6c757d;
            font-style: italic;
        }

        .font-angka {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
        }

        .card-laporan {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        @media print {
            .no-print { display: none !important; }
            body { background-color: white !important; padding: 0; }
            .container-fluid { width: 100% !important; padding: 0 !important; }
            .card-laporan { box-shadow: none !important; border: 1px solid #ddd !important; }
            .table-laporan thead th { background-color: #2c3e50 !important; color: white !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    
    <div class="card card-laporan mb-4 no-print">
        <div class="card-body">
            <h5 class="card-title mb-3"><i class="fas fa-search me-2"></i>Filter & Cari</h5>
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Bulan</label>
                    <select name="bulan" class="form-select form-select-sm">
                        <?php foreach ($nama_bulan as $m => $nama): ?>
                            <option value="<?= $m ?>" <?= ($bulan == $m) ? 'selected' : '' ?>><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Tahun</label>
                    <select name="tahun" class="form-select form-select-sm">
                        <?php 
                        $thn_skrg = date('Y');
                        for($y = $thn_skrg - 2; $y <= $thn_skrg + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= ($tahun == $y) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Cari Driver / Plat Nomor</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik nama atau plat..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-5">
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="fas fa-sync-alt me-1"></i> Tampilkan
                    </button>
                    <button type="button" onclick="window.print()" class="btn btn-sm btn-secondary px-3">
                        <i class="fas fa-print me-1"></i> Cetak Laporan
                    </button>
                    <a href="../../index.php" class="btn btn-danger btn-sm fw-bold ms-2"><i class="fas fa-rotate-left me-1"></i> KEMBALI</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-laporan">
        <div class="card-body p-4">
            
            <div class="text-center mb-4">
                <h4 class="fw-bold mb-0 text-uppercase">Laporan Mobil</h4>
                <p class="text-muted mb-0">Periode: <?= $nama_bulan[$bulan] ?> <?= $tahun ?></p>
                <?php if($search): ?>
                    <p class="badge bg-info text-dark mt-2">Pencarian: "<?= htmlspecialchars($search) ?>"</p>
                <?php endif; ?>
                <div style="border-bottom: 3px double #2c3e50; width: 100px; margin: 10px auto;"></div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm table-laporan">
                    <thead>
                        <tr>
                            <th width="40">No</th>
                            <th width="150">Nama Driver</th>
                            <th width="120">Plat Nomor</th>
                            <th>Pembelian (Detail Barang)</th>
                            <th width="180">Total Pengeluaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Logika WHERE dinamis untuk filter bulan, tahun, dan pencarian
                        $where_clause = "WHERE MONTH(r.tgl_request) = '$bulan' AND YEAR(r.tgl_request) = '$tahun'";
                        
                        if ($search != '') {
                            $where_clause .= " AND (m.driver_tetap LIKE '%$search%' OR m.plat_nomor LIKE '%$search%')";
                        }

                        $query_sql = "SELECT 
                                        m.driver_tetap, 
                                        m.plat_nomor, 
                                        GROUP_CONCAT(
                                            CONCAT(
                                                '• ', rd.nama_barang_manual, 
                                                ' (', rd.jumlah, ' ', rd.satuan, ') ',
                                                '<span class=\"harga-detail\">@Rp', FORMAT(rd.harga_satuan_estimasi, 0, 'id_ID'), '</span>'
                                            ) 
                                            SEPARATOR '<br>'
                                        ) as rincian_barang,
                                        SUM(rd.jumlah * rd.harga_satuan_estimasi) as total_biaya
                                      FROM master_mobil m
                                      INNER JOIN tr_request_detail rd ON m.id_mobil = rd.id_mobil
                                      INNER JOIN tr_request r ON rd.id_request = r.id_request
                                      $where_clause
                                      GROUP BY m.id_mobil
                                      ORDER BY m.plat_nomor ASC";

                        $result = mysqli_query($koneksi, $query_sql);
                        $no = 1;
                        $grand_total = 0;

                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $grand_total += $row['total_biaya'];
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="px-2"><?= strtoupper($row['driver_tetap']) ?></td>
                                <td class="text-center fw-bold text-primary"><?= $row['plat_nomor'] ?></td>
                                <td class="px-3 py-2"><?= $row['rincian_barang'] ?></td>
                                <td class="text-end px-3 font-angka">
                                    Rp <?= number_format($row['total_biaya'], 0, ',', '.') ?>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Data tidak ditemukan untuk periode atau kata kunci tersebut.</td></tr>";
                        }
                        ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end px-3 py-2">TOTAL KESELURUHAN :</td>
                            <td class="text-end px-3 py-2 text-danger font-angka" style="font-size: 0.9rem;">
                                Rp <?= number_format($grand_total, 0, ',', '.') ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row mt-5">
                <div class="col-8"></div>
                <div class="col-4 text-center" style="font-size: 0.85rem;">
                    <p class="mb-5">Surabaya, <?= date('d') ?> <?= $nama_bulan[date('m')] ?> <?= date('Y') ?></p>
                    <br><br>
                    <p class="fw-bold mb-0 text-decoration-underline">____________________</p>
                    <p class="text-muted">Manager</p>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
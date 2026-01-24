<?php
if (!isset($koneksi)) { include "../../config/koneksi.php"; }

// 1. Parameter Filter
$alokasi_filter = isset($_GET['alokasi_filter']) ? $_GET['alokasi_filter'] : 'SEMUA';
$keyword        = isset($_GET['keyword']) ? mysqli_real_escape_string($koneksi, strtoupper($_GET['keyword'])) : '';
$mode           = isset($_GET['mode']) ? $_GET['mode'] : 'kata'; 

// 2. Persiapan Data (Double-Pass untuk mencari harga termurah)
$data_tampil = [];
$harga_array = [];

$filter_sql = " WHERE 1=1 ";
if ($keyword != '') {
    if ($mode == 'abjad') { $filter_sql .= " AND p.nama_barang_beli LIKE '$keyword%' "; } 
    else { $filter_sql .= " AND p.nama_barang_beli LIKE '%$keyword%' "; }
}
if ($alokasi_filter != 'SEMUA') {
    $filter_sql .= " AND p.alokasi_stok = '$alokasi_filter' ";
}

$sql = "SELECT p.*, m.merk as merk_master 
        FROM pembelian p 
        LEFT JOIN master_barang m ON p.nama_barang_beli = m.nama_barang 
        $filter_sql 
        ORDER BY p.tgl_beli DESC LIMIT 200";

$query = mysqli_query($koneksi, $sql);
while($row = mysqli_fetch_assoc($query)) {
    $data_tampil[] = $row;
    if($row['harga'] > 0) { $harga_array[] = $row['harga']; }
}

$harga_termurah = (count($harga_array) > 0) ? min($harga_array) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perbandingan Harga - MCP System</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --mcp-blue: #0000FF; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; font-size: 0.85rem; }
        .navbar-mcp { background: var(--mcp-blue); color: white; }
        .search-box { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 20px; border-left: 6px solid var(--mcp-blue); }
        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 15px; }
        
        /* WARNA BADGE ALOKASI */
        .badge-langsung { background-color: #fff3e0; color: #e65100; border: 1px solid #ffb74d; font-size: 0.7rem; font-weight: 700; }
        .badge-stok { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #81c784; font-size: 0.7rem; font-weight: 700; }
        
        /* HIGHLIGHT TERMURAH */
        .row-termurah { background-color: #f0fff4 !important; border-left: 5px solid #198754; }
        .badge-termurah { background-color: #198754; color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
    </style>
</head>
<body class="pb-5">

<nav class="navbar navbar-mcp mb-4 shadow-sm">
    <div class="container-fluid px-4">
        <span class="navbar-brand fw-bold text-white small"><i class="fas fa-balance-scale me-2"></i> DATA PERBANDINGAN HARGA</span>
        <a href="../../index.php" class="btn btn-danger btn-sm px-3 fw-bold"><i class="fas fa-rotate-left me-1"></i> KEMBALI</a>
    </div>
</nav>

<div class="container-fluid px-4">
    
    <div class="search-box mb-4">
        <form action="" method="GET">
            <input type="hidden" name="page" value="data_perbandingan">
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold text-muted">1. PILIH ALOKASI</label>
                    <select name="alokasi_filter" class="form-select border-primary fw-bold" onchange="this.form.submit()">
                        <option value="SEMUA" <?= $alokasi_filter == 'SEMUA' ? 'selected' : '' ?>>SEMUA DATA</option>
                        <option value="LANGSUNG PAKAI" <?= $alokasi_filter == 'LANGSUNG PAKAI' ? 'selected' : '' ?>>LANGSUNG PAKAI</option>
                        <option value="MASUK STOK" <?= $alokasi_filter == 'MASUK STOK' ? 'selected' : '' ?>>MASUK STOK</option>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold text-muted">2. METODE SEARCH</label>
                    <div class="d-flex gap-3 pt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" id="m1" value="abjad" <?= $mode == 'abjad' ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-bold" for="m1">AWALAN</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode" id="m2" value="kata" <?= $mode == 'kata' ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-bold" for="m2">KATA ACAK</label>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold text-muted">3. KETIK NAMA BARANG</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-keyboard"></i></span>
                        <input type="text" name="keyword" class="form-control" placeholder="CARI BARANG..." value="<?= $keyword ?>" autofocus>
                    </div>
                </div>

                <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">CARI</button>
                    <a href="?page=data_perbandingan" class="btn btn-warning"><i class="fas fa-sync"></i></a>
                </div>
            </div>
        </form>
    </div>

    <div class="table-container shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100">
               <thead>
                <tr class="small text-uppercase bg-light border-bottom">
                    <th class="text-center" width="5%">No</th>
                    <th class="text-center" width="10%">Tgl Beli</th>
                    <th class="text-start"  width="30%">Nama Barang & Merk</th>
                    <th class="text-start"  width="20%">Supplier</th>
                    <th class="text-center" width="15%">Alokasi</th>
                    <th class="text-end"    width="20%">Harga Satuan</th>
                </tr>
                </thead>

                <tbody class="small text-uppercase">
                    <?php
                    $no = 1;
                    foreach($data_tampil as $row): 
                        $is_termurah = ($row['harga'] == $harga_termurah && $harga_termurah > 0);
                        $merk_tampil = !empty($row['merk_beli']) ? $row['merk_beli'] : $row['merk_master'];
                    ?>
                    <tr class="<?= $is_termurah ? 'row-termurah' : '' ?>">
                        <td class="text-center text-muted"><?= $no++ ?></td>
                        <td class="text-center">
                            <div class="fw-bold"><?= date('d/m/y', strtotime($row['tgl_beli'])) ?></div>
                            <small class="text-muted" style="font-size: 9px;"><?= $row['no_request'] ?></small>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= $row['nama_barang_beli'] ?></div>
                            <div class="text-primary fw-bold" style="font-size: 10px;">
                                <i class="fas fa-tag me-1"></i> <?= $merk_tampil ?: '-' ?>
                            </div>
                        </td>
                        <td class="fw-bold text-muted"><?= $row['supplier'] ?></td>
                        <td class="text-center">
                            <?php if($row['alokasi_stok'] == 'LANGSUNG PAKAI'): ?>
                                <span class="badge badge-langsung px-3 rounded-pill">LANGSUNG PAKAI</span>
                                <?php if($row['plat_nomor']): ?>
                                    <div class="mt-1 small fw-bold text-dark" style="font-size: 10px;"><?= $row['plat_nomor'] ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-stok px-3 rounded-pill">MASUK STOK</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if($is_termurah): ?>
                                <span class="badge-termurah mb-1"><i class="fas fa-check-circle"></i> TERMURAH</span><br>
                            <?php endif; ?>
                            <span class="fw-bold <?= $is_termurah ? 'text-success fs-6' : 'text-danger' ?>">
                                Rp <?= number_format($row['harga'], 0, ',', '.') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($data_tampil)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted small italic">Data tidak ditemukan untuk kata kunci tersebut.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
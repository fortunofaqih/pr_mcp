<?php
if (!isset($koneksi)) { include "../../config/koneksi.php"; }

$alokasi_filter = isset($_GET['alokasi_filter']) ? $_GET['alokasi_filter'] : 'SEMUA';
$keyword        = isset($_GET['keyword']) ? mysqli_real_escape_string($koneksi, strtoupper($_GET['keyword'])) : '';
$mode           = isset($_GET['mode']) ? $_GET['mode'] : 'kata'; 

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
        .search-box, .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 20px; }
        .row-termurah { background-color: #f0fff4 !important; border-left: 5px solid #198754; }
        .badge-termurah { background-color: #198754; color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-manual { background-color: #e9ecef; color: #495057; border: 1px solid #ced4da; font-size: 9px; }
    </style>
</head>
<body class="pb-5">

<nav class="navbar navbar-mcp mb-4 shadow-sm">
    <div class="container-fluid px-4">
        <span class="navbar-brand fw-bold text-white small"><i class="fas fa-balance-scale me-2"></i> DATA PERBANDINGAN HARGA</span>
        <div>
            <a href="form_input_historis.php" class="btn btn-success btn-sm fw-bold me-2"><i class="fas fa-plus-circle me-1"></i> INPUT DATA BUKU</a>
            <a href="../../index.php" class="btn btn-danger btn-sm px-3 fw-bold"><i class="fas fa-rotate-left me-1"></i> KEMBALI</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="search-box mb-4">
        <form action="" method="GET">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">ALOKASI</label>
                    <select name="alokasi_filter" class="form-select border-primary fw-bold" onchange="this.form.submit()">
                        <option value="SEMUA" <?= $alokasi_filter == 'SEMUA' ? 'selected' : '' ?>>SEMUA DATA</option>
                        <option value="LANGSUNG PAKAI" <?= $alokasi_filter == 'LANGSUNG PAKAI' ? 'selected' : '' ?>>LANGSUNG PAKAI</option>
                        <option value="MASUK STOK" <?= $alokasi_filter == 'MASUK STOK' ? 'selected' : '' ?>>MASUK STOK</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">CARI NAMA BARANG</label>
                    <input type="text" name="keyword" class="form-control" placeholder="KETIK NAMA BARANG..." value="<?= $keyword ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">CARI</button>
                    <a href="data_perbandingan.php" class="btn btn-warning"><i class="fas fa-sync"></i></a>
                </div>
            </div>
        </form>
    </div>

    <div class="table-container shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100">
                <thead>
                    <tr class="small text-uppercase bg-light border-bottom">
                        <th class="text-center">No</th>
                        <th class="text-center">Tgl Beli</th>
                        <th>Nama Barang & Merk</th>
                        <th>Supplier</th>
                        <th class="text-center">Sumber</th>
                        <th class="text-end">Harga Satuan</th>
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
                            <div class="text-primary fw-bold" style="font-size: 10px;"><i class="fas fa-tag me-1"></i> <?= $merk_tampil ?: '-' ?></div>
                        </td>
                        <td class="fw-bold text-muted"><?= $row['supplier'] ?></td>
                        <td class="text-center">
                            <?php if($row['sumber_data'] == 'MANUAL'): ?>
                                <span class="badge badge-manual"><i class="fas fa-book me-1"></i> BUKU</span>
                            <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 9px;">SISTEM</span>
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
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
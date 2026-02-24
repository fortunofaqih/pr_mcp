<?php
session_start();
include '../../config/koneksi.php';

if ($_SESSION['status'] != "login") {
    header("location:../../login.php?pesan=belum_login");
    exit;
}

// 1. TANGKAP FILTER
$abjad_filter = isset($_GET['abjad']) ? mysqli_real_escape_string($koneksi, strtoupper($_GET['abjad'])) : '';
$tgl_min      = isset($_GET['tgl_min']) ? $_GET['tgl_min'] : '';
$tgl_max      = isset($_GET['tgl_max']) ? $_GET['tgl_max'] : '';
$keyword      = isset($_GET['keyword']) ? mysqli_real_escape_string($koneksi, strtoupper($_GET['keyword'])) : '';

// 2. QUERY STRING
$query_string = "tgl_min=$tgl_min&tgl_max=$tgl_max&keyword=$keyword";

// 3. BANGUN SQL FILTER
$filter_sql = " WHERE 1=1 ";
if ($abjad_filter != '' && $abjad_filter != 'ALL') {
    $filter_sql .= " AND p.nama_barang_beli LIKE '$abjad_filter%' ";
}
if ($tgl_min != '' && $tgl_max != '') {
    $filter_sql .= " AND p.tgl_beli_barang BETWEEN '$tgl_min' AND '$tgl_max' ";
}
if ($keyword != '') {
    $filter_sql .= " AND (p.nama_barang_beli LIKE '%$keyword%' OR p.supplier LIKE '%$keyword%' OR p.plat_nomor LIKE '%$keyword%') ";
}

// 4. AMBIL DATA
$sql = "SELECT p.*, m.merk as merk_master, m.satuan as satuan_master 
        FROM pembelian p 
        LEFT JOIN master_barang m ON p.nama_barang_beli = m.nama_barang 
        $filter_sql 
        ORDER BY p.tgl_beli_barang DESC, p.id_pembelian DESC LIMIT 500";

$query = mysqli_query($koneksi, $sql);
$data_tampil = [];
$harga_array = [];

while($row = mysqli_fetch_assoc($query)) {
    $data_tampil[] = $row;
    if($row['harga'] > 0) { $harga_array[] = (float)$row['harga']; }
}

$harga_termurah = (count($harga_array) > 0) ? min($harga_array) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Realisasi Pembelian - MCP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --mcp-blue: #0000FF; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; font-size: 0.82rem; }
        .navbar-mcp { background: var(--mcp-blue); color: white; }
        .search-box, .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 20px; }
        .row-termurah { background-color: #f0fff4 !important; border-left: 5px solid #198754; }
        .badge-termurah { background-color: #198754; color: white; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: bold; display: inline-block; margin-bottom: 3px; }
        .alphabet-nav { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 15px; }
        .btn-abjad { padding: 4px 9px; font-size: 10px; font-weight: bold; border: 1px solid #dee2e6; background: white; color: #333; text-decoration: none; border-radius: 4px; }
        .btn-abjad.active, .btn-abjad:hover { background: var(--mcp-blue); color: white; border-color: var(--mcp-blue); }
        .text-plat { background: #333; color: #fff; padding: 2px 5px; border-radius: 3px; font-weight: bold; font-family: monospace; font-size: 10px; }
        .btn-xs { padding: 0.25rem 0.4rem; font-size: 0.75rem; }
        table thead th { vertical-align: middle; background-color: #212529 !important; color: white; }
    </style>
</head>
<body class="pb-5">

<nav class="navbar navbar-mcp mb-4 shadow-sm">
    <div class="container-fluid px-4 text-white">
        <span class="navbar-brand fw-bold text-white small"><i class="fas fa-book me-2"></i> BUKU REALISASI PEMBELIAN</span>
        <div>
            <a href="export_excel_pembelian.php?abjad=<?= $abjad_filter ?>&<?= $query_string ?>" class="btn btn-success btn-sm px-3 fw-bold me-2">
                <i class="fas fa-file-excel me-1"></i> EXPORT
            </a>
            <a href="../../index.php" class="btn btn-danger btn-sm px-3 fw-bold"><i class="fas fa-arrow-left me-1"></i> KEMBALI</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="search-box mb-4">
        <form action="" method="GET">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">RENTANG TANGGAL NOTA</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="tgl_min" class="form-control" value="<?= $tgl_min ?>">
                        <input type="date" name="tgl_max" class="form-control" value="<?= $tgl_max ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">PENCARIAN</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="keyword" class="form-control text-uppercase" placeholder="BARANG / TOKO / PLAT..." value="<?= $keyword ?>">
                        <button type="submit" class="btn btn-primary fw-bold px-4">CARI DATA</button>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="data_pembelian.php" class="btn btn-warning btn-sm w-100 fw-bold"><i class="fas fa-sync me-1"></i> RESET</a>
                </div>
            </div>
            
            <div class="alphabet-nav">
                <a href="?abjad=ALL&<?= $query_string ?>" class="btn-abjad <?= ($abjad_filter == '' || $abjad_filter == 'ALL') ? 'active' : '' ?>">ALL</a>
                <?php foreach (range('A', 'Z') as $char): ?>
                    <a href="?abjad=<?= $char ?>&<?= $query_string ?>" class="btn-abjad <?= ($abjad_filter == $char) ? 'active' : '' ?>"><?= $char ?></a>
                <?php endforeach; ?>
            </div>
        </form>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100">
                <thead class="table-dark">
                    <tr class="text-nowrap small text-center">
                        <th width="100">TGL NOTA</th>
                        <th width="150">SUPPLIER</th>
                        <th class="text-start">NAMA BARANG</th>
                        <th width="70">QTY</th>
                        <th width="120">HARGA</th>
                        <th width="130">TOTAL</th>
                        <th width="150">ALOKASI / UNIT</th>
                        <th class="text-start">KETERANGAN</th>
                        <th width="80">AKSI</th>
                    </tr>
                </thead>
                <tbody class="text-uppercase">
                    <?php 
                    if(!empty($data_tampil)):
                        foreach($data_tampil as $row): 
                            $total_bayar = $row['qty'] * $row['harga'];
                            $is_termurah = ($row['harga'] == $harga_termurah && $harga_termurah > 0);
                            $merk_tampil = !empty($row['merk_beli']) ? $row['merk_beli'] : ($row['merk_master'] ?? '-');
                            $satuan = !empty($row['satuan_master']) ? $row['satuan_master'] : 'PCS';
                            
                            // FORMAT TANGGAL AMAN
                            $tgl_display = ($row['tgl_beli_barang'] == '0000-00-00' || empty($row['tgl_beli_barang'])) 
                                           ? '<span class="text-muted small">-</span>' 
                                           : date('d/m/y', strtotime($row['tgl_beli_barang']));
                    ?>
                    <tr class="<?= $is_termurah ? 'row-termurah' : '' ?>">
                        <td class="text-center fw-bold text-muted"><?= $tgl_display ?></td>
                        <td class="small"><?= substr($row['supplier'], 0, 25) ?></td>
                        <td>
                            <div class="fw-bold"><?= $row['nama_barang_beli'] ?></div>
                            <small class="text-primary fw-bold" style="font-size: 10px;"><?= $merk_tampil ?></small>
                        </td>
                        <td class="text-center">
                            <div class="fw-bold"><?= (float)$row['qty'] ?></div>
                            <div class="text-muted fw-bold" style="font-size: 9px;"><?= strtoupper($satuan) ?></div>
                        </td>
                        <td class="text-end">
                            <?php if($is_termurah): ?>
                                <span class="badge-termurah"><i class="fas fa-check-circle"></i> TERMURAH</span><br>
                            <?php endif; ?>
                            <span class="fw-bold <?= $is_termurah ? 'text-success' : '' ?>">
                                <?= number_format($row['harga'], 0, ',', '.') ?>
                            </span>
                        </td>
                        <td class="text-end fw-bold text-danger"><?= number_format($total_bayar, 0, ',', '.') ?></td>
                        <td>
                            <span class="badge bg-secondary mb-1" style="font-size: 9px;"><?= $row['alokasi_stok'] ?></span><br>
                            <?php if(!empty($row['plat_nomor'])): ?>
                                <span class="text-plat"><?= $row['plat_nomor'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small fw-bold text-start"><?= $row['keterangan'] ?: '-' ?></td>
                        <td class="text-center">
                        <div class="btn-group">
                            <button class="btn btn-xs btn-warning btn-edit" 
                                data-id="<?= $row['id_pembelian'] ?>"
                                data-tgl="<?= $row['tgl_beli_barang'] ?>"
                                data-barang="<?= $row['nama_barang_beli'] ?>"
                                data-merk="<?= $row['merk_beli'] ?>"
                                data-supplier="<?= $row['supplier'] ?>"
                                data-qty="<?= $row['qty'] ?>"
                                data-harga="<?= $row['harga'] ?>"
                                data-alokasi="<?= $row['alokasi_stok'] ?>"
                                data-driver="<?= $row['driver'] ?>"
                                data-plat="<?= $row['plat_nomor'] ?>"
                                data-ket="<?= $row['keterangan'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                    
                            <button class="btn btn-xs btn-info text-white" onclick="aksiRetur('<?= $row['id_pembelian'] ?>', '<?= addslashes($row['nama_barang_beli']) ?>', '<?= (float)$row['qty'] ?>', '<?= addslashes($row['supplier']) ?>')">
                                <i class="fas fa-undo"></i>
                            </button>
                    
                            <a href="hapus_pembelian_double.php?id=<?= $row['id_pembelian'] ?>" 
                               class="btn btn-xs btn-danger" 
                               onclick="return confirm('PERINGATAN!\n\nData ini akan dihapus permanen. Menghapus data pembelian akan merubah total pengeluaran di dashboard.\n\nYakin ingin menghapus?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                        
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="9" class="text-center p-4 text-muted">Data tidak ditemukan sesuai filter.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditBeli" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form action="proses_edit_pembelian.php" method="POST">
                <div class="modal-header bg-warning">
                    <h6 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>EDIT DATA REALISASI</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_pembelian" id="edit_id">
                    
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="small fw-bold">TANGGAL NOTA</label>
                            <input type="date" name="tgl_beli_barang" id="edit_tgl" class="form-control form-control-sm border-primary" required>
                        </div>
                        <div class="col-md-8">
                            <label class="small fw-bold">NAMA BARANG</label>
                            <input type="text" name="nama_barang" id="edit_barang" class="form-control form-control-sm fw-bold text-uppercase">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">MERK</label>
                            <input type="text" name="merk_beli" id="edit_merk" class="form-control form-control-sm text-uppercase">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">SUPPLIER / TOKO</label>
                            <input type="text" name="supplier" id="edit_supplier" class="form-control form-control-sm text-uppercase">
                        </div>
                    </div>

                    <div class="row g-2 mb-3 p-2 bg-light border rounded">
                        <div class="col-md-4">
                            <label class="small fw-bold text-primary">QTY</label>
                            <input type="number" name="qty" id="edit_qty" class="form-control form-control-sm hitung" step="0.01">
                        </div>
                        <div class="col-md-8">
                            <label class="small fw-bold text-primary">TOTAL BAYAR (GLOBAL)</label>
                            <input type="number" id="edit_total_global" class="form-control form-control-sm hitung">
                            <input type="hidden" name="harga" id="edit_harga">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">ALOKASI STOK</label>
                            <select name="alokasi_stok" id="edit_alokasi" class="form-select form-select-sm fw-bold">
                                <option value="LANGSUNG PAKAI">LANGSUNG PAKAI</option>
                                <option value="MASUK STOK">MASUK STOK</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">DRIVER</label>
                            <input type="text" name="driver" id="edit_driver" class="form-control form-control-sm text-uppercase">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="small fw-bold">PLAT NOMOR</label>
                            <input type="text" name="plat_nomor" id="edit_plat" class="form-control form-control-sm text-uppercase">
                        </div>
                        <div class="col-md-8">
                            <label class="small fw-bold">KETERANGAN / CATATAN</label>
                            <input type="text" name="keterangan" id="edit_ket" class="form-control form-control-sm text-uppercase">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">SIMPAN PERUBAHAN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function aksiRetur(id, barang, qty, supplier) {
    let msg = `RETUR BARANG?\n--------------------------\nBarang : ${barang}\nQty    : ${qty}\nToko   : ${supplier}\n--------------------------\nMasukkan alasan retur:`;
    let alasan = prompt(msg);
    if (alasan) {
        window.location.href = `proses_retur_pembelian.php?id=${id}&alasan=${encodeURIComponent(alasan)}`;
    }
}

$(document).ready(function() {
    $(document).on('click', '.btn-edit', function() {
        var d = $(this).data();
        $('#edit_id').val(d.id);
        $('#edit_tgl').val(d.tgl); // Isi tanggal nota
        $('#edit_barang').val(d.barang);
        $('#edit_merk').val(d.merk);
        $('#edit_supplier').val(d.supplier);
        $('#edit_qty').val(d.qty);
        
        var total = parseFloat(d.qty) * parseFloat(d.harga);
        $('#edit_total_global').val(Math.round(total));
        $('#edit_harga').val(d.harga);
        
        $('#edit_alokasi').val(d.alokasi);
        $('#edit_driver').val(d.driver);
        $('#edit_plat').val(d.plat);
        $('#edit_ket').val(d.ket);
        
        $('#modalEditBeli').modal('show');
    });

    $('.hitung').on('input', function() {
        var qty = parseFloat($('#edit_qty').val()) || 0;
        var total = parseFloat($('#edit_total_global').val()) || 0;
        if(qty > 0) {
            var harga_satuan = Math.round(total / qty);
            $('#edit_harga').val(harga_satuan);
        }
    });
});
</script>
</body>
</html>
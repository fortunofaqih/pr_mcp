<?php
session_start();
include '../../config/koneksi.php';

if ($_SESSION['status'] != "login") {
    header("location:../../login.php?pesan=belum_login");
    exit;
}

// 1. Ambil Kamus Barang untuk Datalist
$daftar_master = mysqli_query($koneksi, "SELECT nama_barang FROM master_barang WHERE status_aktif='AKTIF' ORDER BY nama_barang ASC");
$kamus_barang = "";
while($m = mysqli_fetch_array($daftar_master)){
    $kamus_barang .= '<option value="'.strtoupper($m['nama_barang']).'">';
}

// 2. Query Utama dengan JOIN ke Master Barang
$q = mysqli_query($koneksi, "SELECT p.*, m.merk as merk_master 
                             FROM pembelian p 
                             LEFT JOIN master_barang m ON p.nama_barang_beli = m.nama_barang 
                             ORDER BY p.id_pembelian DESC LIMIT 500");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Realisasi Pembelian - MCP</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --mcp-blue: #0000FF; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        input, select { text-transform: uppercase; }
        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 20px; }
        .navbar-mcp { background: var(--mcp-blue); color: white; }
        .dataTables_filter input { border: 1px solid var(--mcp-blue); font-weight: bold; }
        .btn-xs { padding: 0.25rem 0.4rem; font-size: 0.7rem; }
    </style>
</head>
<body class="pb-5">

<datalist id="list_barang_master"><?= $kamus_barang ?></datalist>

<nav class="navbar navbar-mcp mb-4 shadow-sm">
    <div class="container-fluid px-4">
        <span class="navbar-brand fw-bold"><i class="fas fa-book me-2"></i> BUKU REALISASI PEMBELIAN</span>
        <a href="../../index.php" class="btn btn-danger btn-sm px-3 fw-bold"><i class="fas fa-arrow-left me-1"></i> KEMBALI</a>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="table-container shadow-sm">
        
        <div class="row g-3 mb-4 bg-light p-3 rounded-3 border">
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">DARI TANGGAL</label>
                <input type="date" id="min" class="form-control form-control-sm border-primary">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">SAMPAI TANGGAL</label>
                <input type="date" id="max" class="form-control form-control-sm border-primary">
            </div>
        </div>

        <div class="table-responsive">
            <table id="tabelLaporanBeli" class="table table-hover table-bordered align-middle w-100" style="font-size: 0.75rem;">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center">TGL</th>
                        <th>NO. PR</th>
                        <th>SUPPLIER</th>
                        <th>NAMA BARANG</th>
                        <th>MERK</th>
                        <th class="text-center">QTY</th>
                        <th class="text-end">HARGA</th>
                        <th class="text-end">TOTAL</th>
                        <th class="text-center">ALOKASI</th>
                        <th>PEMESAN</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="text-uppercase text-nowrap">
                    <?php 
                    while($d = mysqli_fetch_array($q)){ 
                        $total = $d['qty'] * $d['harga'];
                        // Logika Merk: Prioritas Merk Beli, jika kosong ambil dari Master
                        $merk_tampil = !empty($d['merk_beli']) ? $d['merk_beli'] : $d['merk_master'];
                    ?>
                    <tr>
                        <td class="text-center"><?= date('d/m/y', strtotime($d['tgl_beli'])) ?></td>
                        <td class="fw-bold text-primary small"><?= $d['no_request'] ?: '-' ?></td>
                        <td class="small"><?= $d['supplier'] ?></td>
                        <td class="fw-bold"><?= $d['nama_barang_beli'] ?></td>
                        <td><?= $merk_tampil ?: '-' ?></td>
                        <td class="text-center fw-bold"><?= $d['qty'] ?></td>
                        <td class="text-end"><?= number_format($d['harga'], 0, ',', '.') ?></td>
                        <td class="text-end fw-bold text-danger"><?= number_format($total, 0, ',', '.') ?></td>
                        <td class="text-center">
                            <span class="badge <?= ($d['alokasi_stok'] == 'MASUK STOK' ? 'bg-info' : 'bg-secondary') ?>" style="font-size: 9px;">
                                <?= $d['alokasi_stok'] ?>
                            </span>
                        </td>
                        <td class="small"><?= $d['nama_pemesan'] ?></td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-xs btn-warning btn-edit" 
                                        data-id="<?= $d['id_pembelian'] ?>"
                                        data-barang="<?= $d['nama_barang_beli'] ?>"
                                        data-merk="<?= $merk_tampil ?>"
                                        data-supplier="<?= $d['supplier'] ?>"
                                        data-qty="<?= $d['qty'] ?>"
                                        data-harga="<?= $d['harga'] ?>"
                                        data-alokasi="<?= $d['alokasi_stok'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <a href="javascript:void(0);" 
                                    class="btn btn-xs btn-danger" 
                                    onclick="
                                            let info = 'RETUR BARANG\n';
                                            info += '--------------------------\n';
                                            info += 'No. Request : <?= $d['no_request'] ?>\n';
                                            info += 'Nama Barang : <?= $d['nama_barang_beli'] ?>\n';
                                            info += 'Qty         : <?= (float)$d['qty'] ?>\n';
                                            info += 'Toko/Supplier: <?= $d['supplier'] ?>\n';
                                            info += '--------------------------\n';
                                            info += 'Masukkan alasan retur/pengembalian:';
                                            
                                            let alasan = prompt(info); 
                                            if(alasan) { 
                                                window.location.href='proses_retur_pembelian.php?id=<?= $d['id_pembelian'] ?>&alasan=' + encodeURIComponent(alasan); 
                                            }">
                                        <i class="fas fa-undo"></i> RETUR
                                    </a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditBeli" tabindex="-1">
    <div class="modal-dialog shadow-lg">
        <div class="modal-content">
            <form action="proses_edit_pembelian.php" method="POST">
                <div class="modal-header bg-warning py-2 fw-bold">
                    <h6 class="modal-title"><i class="fas fa-edit me-2"></i>KOREKSI DATA PEMBELIAN</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <input type="hidden" name="id_pembelian" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NAMA BARANG</label>
                        <input type="text" name="nama_barang" id="edit_barang" class="form-control fw-bold border-primary shadow-sm" list="list_barang_master" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">MERK (DAPAT DISESUAIKAN)</label>
                        <input type="text" name="merk_beli" id="edit_merk" class="form-control fw-bold" placeholder="Ketik Merk...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">SUPPLIER</label>
                        <input type="text" name="supplier" id="edit_supplier" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-muted">QTY</label>
                            <input type="number" name="qty" id="edit_qty" class="form-control text-center fw-bold" step="0.01" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-muted">HARGA SATUAN</label>
                            <input type="number" name="harga" id="edit_harga" class="form-control text-end fw-bold" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">ALOKASI STOK</label>
                        <select name="alokasi" id="edit_alokasi" class="form-select">
                            <option value="LANGSUNG PAKAI">LANGSUNG PAKAI</option>
                            <option value="MASUK STOK">MASUK STOK</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold">SIMPAN PERUBAHAN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#tabelLaporanBeli').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 50,
        "language": {
            "search": "<strong>CARI APAPUN:</strong>"
        }
    });

    // Filter Tanggal
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            var min = $('#min').val();
            var max = $('#max').val();
            var dateStr = data[0].split('/'); 
            var date = new Date('20' + dateStr[2], dateStr[1] - 1, dateStr[0]);

            if (min === "" && max === "") return true;
            var minDate = min ? new Date(min) : null;
            var maxDate = max ? new Date(max) : null;

            if (minDate && date < minDate) return false;
            if (maxDate && date > maxDate) return false;
            return true;
        }
    );

    $('#min, #max').on('change', function() { table.draw(); });

    // Handle Klik Edit
    $(document).on('click', '.btn-edit', function() {
        var data = $(this).data();
        $('#edit_id').val(data.id);
        $('#edit_barang').val(data.barang);
        $('#edit_merk').val(data.merk);
        $('#edit_supplier').val(data.supplier);
        $('#edit_qty').val(data.qty);
        $('#edit_harga').val(data.harga);
        $('#edit_alokasi').val(data.alokasi);
        $('#modalEditBeli').modal('show');
    });
});
</script>
</body>
</html>
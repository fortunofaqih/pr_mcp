<?php
session_start();
include '../../config/koneksi.php';

if ($_SESSION['status'] != "login") {
    header("location:../../login.php?pesan=belum_login");
    exit;
}

// 1. Ambil Kamus Barang untuk Datalist (Autocompletion)
$daftar_master = mysqli_query($koneksi, "SELECT nama_barang FROM master_barang WHERE status_aktif='AKTIF' ORDER BY nama_barang ASC");
$kamus_barang = "";
while($m = mysqli_fetch_array($daftar_master)){
    $kamus_barang .= '<option value="'.strtoupper($m['nama_barang']).'">';
}

// 2. Query Utama (Limit 500 untuk performa)
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --mcp-blue: #0000FF; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; font-size: 0.8rem; }
        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 20px; }
        .navbar-mcp { background: var(--mcp-blue); color: white; }
        .alphabet-filter { display: flex; flex-wrap: wrap; gap: 3px; margin-bottom: 15px; }
        .btn-abjad { padding: 3px 7px; font-size: 10px; font-weight: bold; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 4px; transition: 0.2s; }
        .btn-abjad:hover, .btn-abjad.active { background: var(--mcp-blue); color: white; border-color: var(--mcp-blue); }
        .text-plat { background: #333; color: #fff; padding: 2px 5px; border-radius: 3px; font-weight: bold; font-family: monospace; }
        .btn-xs { padding: 0.2rem 0.4rem; font-size: 0.7rem; }
    </style>
</head>
<body class="pb-5">

<datalist id="list_barang_master"><?= $kamus_barang ?></datalist>

<nav class="navbar navbar-mcp mb-4 shadow-sm">
    <div class="container-fluid px-4">
        <span class="navbar-brand fw-bold text-white small"><i class="fas fa-book me-2"></i> BUKU REALISASI PEMBELIAN</span>
        <a href="../../index.php" class="btn btn-danger btn-sm px-3 fw-bold"><i class="fas fa-arrow-left me-1"></i> KEMBALI</a>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="table-container">
        <div class="row g-3 mb-3 border-bottom pb-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">RENTANG TANGGAL</label>
                <div class="input-group input-group-sm">
                    <input type="date" id="min" class="form-control border-primary">
                    <input type="date" id="max" class="form-control border-primary">
                </div>
            </div>
            <div class="col-md-9">
                <label class="form-label small fw-bold text-muted">PENCARIAN HURUF DEPAN BARANG</label>
                <div class="alphabet-filter">
                    <button class="btn-abjad active" data-letter="">ALL</button>
                    <?php foreach(range('A','Z') as $char) echo "<button class='btn-abjad' data-letter='$char'>$char</button>"; ?>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tabelLaporanBeli" class="table table-hover table-bordered align-middle w-100">
                <thead class="table-dark">
                    <tr class="text-nowrap">
                        <th>TGL</th>
                        <th>SUPPLIER</th>
                        <th>NAMA BARANG</th>
                        <th>MERK</th>
                        <th>QTY</th>
                        <th class="text-end">HARGA</th>
                        <th class="text-end">TOTAL</th>
                        <th>UNIT/DRIVER</th>
                        <th>ALOKASI</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="text-uppercase">
                <?php 
                while($d = mysqli_fetch_array($q)){ 
                    $total_bayar = $d['qty'] * $d['harga'];
                    $merk_tampil = !empty($d['merk_beli']) ? $d['merk_beli'] : $d['merk_master'];
                ?>
                <tr>
                    <td class="text-center small"><?= date('d/m/y', strtotime($d['tgl_beli'])) ?></td>
                    
                    <td class="small"><?= substr($d['supplier'], 0, 15) ?></td>
                    
                    <td class="fw-bold"><?= $d['nama_barang_beli'] ?></td>
                    
                    <td class="small"><?= $merk_tampil ?: '-' ?></td>
                    
                    <td class="text-center fw-bold"><?= (float)$d['qty'] ?></td>
                    
                    <td class="text-end"><?= number_format($d['harga'], 0, ',', '.') ?></td>
                    
                    <td class="text-end fw-bold text-danger"><?= number_format($total_bayar, 0, ',', '.') ?></td>
                    
                    <td>
                        <?php if(!empty($d['plat_nomor'])): ?>
                            <span class="text-plat small"><?= $d['plat_nomor'] ?></span><br>
                        <?php endif; ?>
                        <small class="text-muted"><?= $d['driver'] ?: '-' ?></small>
                    </td>
                    
                    <td><span class="badge bg-secondary" style="font-size: 9px;"><?= $d['alokasi_stok'] ?></span></td>
                    
                    <td class="text-center">
                        <div class="btn-group">
                            <button type="button" class="btn btn-xs btn-warning btn-edit" 
                                    data-id="<?= $d['id_pembelian'] ?>"
                                    data-barang="<?= $d['nama_barang_beli'] ?>"
                                    data-merk="<?= $merk_tampil ?>"
                                    data-supplier="<?= $d['supplier'] ?>"
                                    data-qty="<?= $d['qty'] ?>"
                                    data-harga="<?= $d['harga'] ?>"
                                    data-alokasi="<?= $d['alokasi_stok'] ?>"
                                    data-driver="<?= $d['driver'] ?>"
                                    data-plat="<?= $d['plat_nomor'] ?>"
                                    data-ket="<?= $d['keterangan'] ?>">
                                <i class="fas fa-edit"> Edit</i>
                            </button>

                            <a href="javascript:void(0);" 
                            class="btn btn-xs btn-danger" 
                            onclick="
                                    let info = 'RETUR BARANG (PENGEMBALIAN)\n';
                                    info += '--------------------------\n';
                                    info += 'Barang : <?= addslashes($d['nama_barang_beli']) ?>\n';
                                    info += 'Qty    : <?= (float)$d['qty'] ?>\n';
                                    info += 'Toko   : <?= addslashes($d['supplier']) ?>\n';
                                    info += '--------------------------\n';
                                    info += 'Masukkan alasan retur:';
                                    
                                    let alasan = prompt(info); 
                                    if(alasan) { 
                                        window.location.href='proses_retur_pembelian.php?id=<?= $d['id_pembelian'] ?>&alasan=' + encodeURIComponent(alasan); 
                                    }">
                                <i class="fas fa-undo"></i> RETUR KE TOKO
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
    <div class="modal-dialog modal-md shadow-lg">
        <div class="modal-content">
            <form action="proses_edit_pembelian.php" method="POST">
                <div class="modal-header bg-warning py-2">
                    <h6 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>KOREKSI PEMBELIAN</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <input type="hidden" name="id_pembelian" id="edit_id">
                    
                    <div class="mb-2">
                        <label class="small fw-bold text-muted">NAMA BARANG</label>
                        <input type="text" name="nama_barang" id="edit_barang" class="form-control fw-bold border-primary" list="list_barang_master" required>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="small fw-bold text-muted">MERK</label>
                            <input type="text" name="merk_beli" id="edit_merk" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">SUPPLIER</label>
                            <input type="text" name="supplier" id="edit_supplier" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-2 bg-white p-2 border rounded">
                        <div class="col-4">
                            <label class="small fw-bold text-muted">QTY</label>
                            <input type="number" name="qty" id="edit_qty" class="form-control hitung text-center fw-bold" step="0.01" required>
                        </div>
                        <div class="col-4">
                            <label class="small fw-bold text-muted">TOTAL BAYAR</label>
                            <input type="number" id="edit_total_global" class="form-control hitung text-end" placeholder="Global">
                        </div>
                        <div class="col-4">
                            <label class="small fw-bold text-primary">HRG SATUAN</label>
                            <input type="number" name="harga" id="edit_harga" class="form-control text-end fw-bold border-primary" readonly>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="small fw-bold text-muted">DRIVER</label>
                            <input type="text" name="driver" id="edit_driver" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">PLAT NOMOR</label>
                            <input type="text" name="plat_nomor" id="edit_plat" class="form-control" placeholder="L 1234 AB">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="small fw-bold text-muted">ALOKASI STOK</label>
                        <select name="alokasi" id="edit_alokasi" class="form-select">
                            <option value="LANGSUNG PAKAI">LANGSUNG PAKAI</option>
                            <option value="MASUK STOK">MASUK STOK</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="small fw-bold text-muted">KETERANGAN</label>
                        <textarea name="keterangan" id="edit_keterangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
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
    // Inisialisasi DataTable
    var table = $('#tabelLaporanBeli').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 50,
        "language": { "search": "<strong>CARI DATA:</strong>" }
    });

    // 1. Filter Abjad (Nama Barang di kolom index 2)
    $('.btn-abjad').on('click', function() {
        $('.btn-abjad').removeClass('active');
        $(this).addClass('active');
        var letter = $(this).data('letter');
        table.column(2).search('^' + letter, true, false).draw();
    });

    // 2. Filter Tanggal
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
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
    });
    $('#min, #max').on('change', function() { table.draw(); });

    // 3. Kalkulator Otomatis di Modal
    $('.hitung').on('input', function() {
        var qty = parseFloat($('#edit_qty').val()) || 0;
        var total = parseFloat($('#edit_total_global').val()) || 0;
        if(qty > 0 && total > 0) {
            $('#edit_harga').val(Math.round(total / qty));
        }
    });

    // 4. Handle Tombol Edit (Mapping Data ke Modal)
    $(document).on('click', '.btn-edit', function() {
        var d = $(this).data();
        $('#edit_id').val(d.id);
        $('#edit_barang').val(d.barang);
        $('#edit_merk').val(d.merk);
        $('#edit_supplier').val(d.supplier);
        $('#edit_qty').val(d.qty);
        $('#edit_harga').val(d.harga);
        $('#edit_alokasi').val(d.alokasi);
        $('#edit_driver').val(d.driver);
        $('#edit_plat').val(d.plat);
        $('#edit_keterangan').val(d.ket);
        
        // Isi total awal agar kalkulator sinkron
        $('#edit_total_global').val(d.qty * d.harga);
        
        $('#modalEditBeli').modal('show');
    });
});
</script>
</body>
</html>
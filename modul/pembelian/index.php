<?php
session_start();
include '../../config/koneksi.php';

// Proteksi Login
if ($_SESSION['status'] != "login") {
    header("location:../../login.php?pesan=belum_login");
    exit;
}

// --- KAMUS BARANG ---
$daftar_master = mysqli_query($koneksi, "SELECT nama_barang FROM master_barang WHERE status_aktif='AKTIF' ORDER BY nama_barang ASC");
$kamus_barang = "";
while($m = mysqli_fetch_array($daftar_master)){
    $kamus_barang .= '<option value="'.strtoupper($m['nama_barang']).'">';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DASHBOARD PEMBELIAN - MCP</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --mcp-blue: #0000FF; --mcp-dark: #1a1a1a; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        input, textarea { text-transform: uppercase; }
        .nav-tabs .nav-link.active { background-color: var(--mcp-blue); color: white; border: none; }
        .nav-tabs .nav-link { color: #555; font-weight: bold; border: none; margin-right: 5px; }
        .modal-xl { max-width: 95%; }
        .bg-waiting { background-color: #fffdf0; }
        @media (min-width: 992px) { .modal-body { max-height: 75vh; overflow-y: auto; } }
        .preview-pr-container { padding: 15px; background: white; border: 1px solid #dee2e6; }
        .table-preview { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-preview th, .table-preview td { border: 1px solid #000; padding: 8px; font-size: 13px; }
        .table-preview th { background: #f2f2f2 !important; text-align: center; }
    </style>
</head>
<body>

<datalist id="list_barang_master"><?= $kamus_barang ?></datalist>

<nav class="navbar navbar-dark mb-4 shadow-sm" style="background: var(--mcp-blue);">
    <div class="container">
        <span class="navbar-brand fw-bold"><i class="fas fa-shopping-cart me-2"></i>MODUL PEMBELIAN</span>
        <a href="../../index.php" class="btn btn-danger btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
</nav>

<div class="container pb-5">
    <ul class="nav nav-tabs mb-3 shadow-sm bg-white p-2 rounded-3" id="pembelianTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#request-list">
                <i class="fas fa-clipboard-list me-2"></i>1. ANTREAN REQUEST (PR)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pembelian-list">
                <i class="fas fa-history me-2"></i>2. BUKU REALISASI PEMBELIAN
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="request-list">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">NO. PR</th>
                                    <th>TANGGAL</th>
                                    <th>PEMESAN</th>
                                    <th>TIPE REQUEST</th>
                                    <th class="text-center">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q_req = mysqli_query($koneksi, "SELECT * FROM tr_request WHERE status_request = 'PENDING' ORDER BY id_request DESC");
                                while($r = mysqli_fetch_array($q_req)) :
                                    $boleh_beli = true;
                                    $status_label = "SIAP DIBELI";
                                    $bg_row = ""; $badge_class = "bg-success";
                                    if($r['kategori_pr'] == 'BESAR' && $r['status_approval'] == 'PENDING') {
                                        $boleh_beli = false;
                                        $status_label = "WAITING APPROVAL";
                                        $bg_row = "bg-waiting"; $badge_class = "bg-warning text-dark";
                                    }
                                ?>
                                <tr class="<?= $bg_row ?>">
                                    <td class="ps-3 fw-bold text-primary">
                                        <?= $r['no_request'] ?><br>
                                        <span class="badge <?= $r['kategori_pr'] == 'BESAR' ? 'bg-danger' : 'bg-success' ?>" style="font-size: 0.65rem;"><?= $r['kategori_pr'] ?></span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($r['tgl_request'])) ?></td>
                                    <td>
                                        <span class="fw-bold"><?= strtoupper($r['nama_pemesan']) ?></span><br>
                                        <small class="badge <?= $badge_class ?>" style="font-size: 0.6rem;">
                                            <i class="fas <?= $boleh_beli ? 'fa-check-circle' : 'fa-clock' ?> me-1"></i><?= $status_label ?>
                                        </small>
                                    </td>
                                    <td><small class="text-muted"><?= $r['kategori_pr'] ?? '-' ?></small></td>
                                    <td class="text-center">
                                        <button onclick="viewPR(<?= $r['id_request'] ?>)" class="btn btn-sm btn-info text-white me-1"><i class="fas fa-eye"></i></button>
                                        <a href="../transaksi/cetak_pr.php?id=<?= $r['id_request'] ?>" target="_blank" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-print"></i></a>
                                        <?php if($boleh_beli): ?>
                                            <button onclick="prosesBeli(<?= $r['id_request'] ?>)" class="btn btn-sm btn-primary px-3"><i class="fas fa-shopping-cart me-1"></i> Beli</button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary px-3" disabled><i class="fas fa-lock me-1"></i> Lock</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pembelian-list">
             <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">DARI TANGGAL</label>
                            <input type="date" id="min" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">SAMPAI TANGGAL</label>
                            <input type="date" id="max" class="form-control form-control-sm">
                        </div>
                    </div>
                    <table id="tabelRealisasi" class="table table-hover table-bordered w-100" style="font-size: 0.75rem;">
                        <thead class="table-dark">
                            <tr>
                                <th>Tgl Beli</th><th>No. PR</th><th>Supplier</th><th>Nama Barang</th><th>Qty</th><th>Harga</th><th>Total</th><th>Kategori</th><th>Alokasi</th><th>Pemesan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q = mysqli_query($koneksi, "SELECT * FROM pembelian ORDER BY tgl_beli DESC");
                            while($d = mysqli_fetch_array($q)){
                                $total = $d['qty'] * $d['harga'];
                                echo "<tr>
                                    <td>".date('d-m-Y', strtotime($d['tgl_beli']))."</td>
                                    <td>".($d['no_request'] ?? '-')."</td>
                                    <td>".$d['supplier']."</td>
                                    <td>".$d['nama_barang_beli']."</td>
                                    <td class='text-center'>".$d['qty']."</td>
                                    <td class='text-end'>".number_format($d['harga'])."</td>
                                    <td class='text-end fw-bold'>".number_format($total)."</td>
                                    <td>".$d['kategori_beli']."</td>
                                    <td><span class='badge ".($d['alokasi_stok'] == 'MASUK STOK' ? 'bg-info' : 'bg-secondary')."'>".$d['alokasi_stok']."</span></td>
                                    <td>".$d['nama_pemesan']."</td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalView" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title"><i class="fas fa-search me-2"></i>DETAIL PURCHASE REQUEST</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light" id="kontenView"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content shadow-lg">
            <form action="proses_tambah.php" method="POST" id="formBeli">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title fw-bold small"><i class="fas fa-shopping-bag me-2"></i>FORM REALISASI PEMBELIAN</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-2 mb-3 bg-light p-2 rounded small">
                        <div class="col-md-4">
                            <label class="fw-bold">PR TERKAIT</label>
                            <select id="pilih_pr" name="id_request" class="form-select form-select-sm border-primary">
                            <option value="">-- BELANJA TANPA PR --</option>
                            <?php
                            // Query ini memastikan kita hanya mengambil PR yang:
                            // 1. Status Requestnya masih PENDING
                            // 2. Kategori KECIL (otomatis boleh) ATAU Kategori BESAR yang sudah APPROVED
                            $sql_opt = mysqli_query($koneksi, "SELECT * FROM tr_request 
                                        WHERE status_request = 'PENDING' 
                                        AND (kategori_pr='KECIL' OR (kategori_pr='BESAR' AND status_approval='APPROVED'))");
                            
                            while($opt = mysqli_fetch_array($sql_opt)){
                                // Tambahan: Cek apakah PR ini punya barang yang statusnya APPROVED
                                // (Khusus untuk kategori BESAR)
                                $id_r = $opt['id_request'];
                                $cek_item = mysqli_query($koneksi, "SELECT id_detail FROM tr_request_detail 
                                            WHERE id_request = '$id_r' AND status_item != 'REJECTED'");
                                
                                if(mysqli_num_rows($cek_item) > 0) {
                                    echo "<option value='".$opt['id_request']."'>".$opt['no_request']." (".$opt['nama_pemesan'].")</option>";
                                }
                            }
                            ?>
                        </select>
                        </div>
                        <div class="col-md-2">
                            <label class="fw-bold">TANGGAL BELI</label>
                            <input type="date" name="tgl_beli" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="fw-bold">USER PEMESAN</label>
                            <input type="text" name="nama_pemesan" id="nama_pemesan" class="form-control form-control-sm" required>
                        </div>
                       <!-- <div class="col-md-2">
                            <label class="fw-bold">DRIVER</label>
                            <input type="text" name="driver" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="fw-bold">PLAT MOBIL</label>
                            <input type="text" name="plat_nomor" class="form-control form-control-sm">
                        </div>-->
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle" id="tabelBeli">
                            <thead class="table-dark small text-center">
                                <tr>
                                    <th width="20%">Nama Barang</th><th width="15%">Supplier</th><th width="8%">Qty</th><th width="12%">Harga</th><th width="12%">Kategori</th><th width="12%">Alokasi</th><th width="12%">Subtotal</th><th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="containerBarang">
                                <tr class="baris-beli">
                                    <td><input type="text" name="nama_barang[]" class="form-control form-control-sm" list="list_barang_master" required></td>
                                    <td><input type="text" name="supplier[]" class="form-control form-control-sm" required></td>
                                    <td><input type="number" name="qty[]" class="form-control form-control-sm b-qty text-center" step="0.01" required></td>
                                    <td><input type="number" name="harga_satuan[]" class="form-control form-control-sm b-harga text-end" required></td>
                                    <td>
                                        <select name="kategori_beli[]" class="form-select form-select-sm" required>
                                            <option value="BENGKEL MOBIL">BENGKEL MOBIL</option>
                                            <option value="BENGKEL LISTRIK">BENGKEL LISTRIK</option>
                                            <option value="KANTOR">KANTOR</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="alokasi_stok[]" class="form-select form-select-sm">
                                            <option value="LANGSUNG PAKAI">LANGSUNG PAKAI</option>
                                            <option value="MASUK STOK">MASUK STOK</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm b-total bg-light fw-bold text-end" readonly></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-baris border-0"><i class="fas fa-times"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <h3 class="mb-0 fw-bold text-primary" id="grandTotalDisplay">Rp 0</h3>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">BATAL</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow">SIMPAN</button>
                    </div>
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
    var table = $('#tabelRealisasi').DataTable({ "order": [[0, "desc"]], "pageLength": 25 });

    $('#min, #max').on('change', function() { table.draw(); });

    // Ajax Pilih PR
    $(document).on('change', '#pilih_pr', function(){
        let id = $(this).val();
        
        if(id != "") {
            // 1. Ambil Detail Barang
            $.ajax({
                url: 'get_pr_detail.php',
                type: 'GET',
                data: {id: id},
                success: function(html){
                    $("#containerBarang").html(html);
                    hitungSemua();
                }
            });

            // 2. Ambil Nama Pemesan
            $.get('get_pr_data.php', {id: id}, function(res){
                // Menggunakan res langsung karena get_pr_data sudah kirim header JSON
                if(res.nama_pemesan) {
                    $('#nama_pemesan').val(res.nama_pemesan).prop('readonly', true);
                }
            }, 'json');
        } else {
            // Jika tanpa PR, bersihkan input nama pemesan
            $('#nama_pemesan').val("").prop('readonly', false);
            // Kosongkan tabel atau beri 1 baris kosong
            $("#containerBarang").html(`
                <tr class="baris-beli">
                    <td><input type="text" name="nama_barang[]" class="form-control form-control-sm" list="list_barang_master" required></td>
                    <td><input type="text" name="supplier[]" class="form-control form-control-sm" required></td>
                    <td><input type="number" name="qty[]" class="form-control form-control-sm b-qty text-center" step="0.01" required></td>
                    <td><input type="number" name="harga_satuan[]" class="form-control form-control-sm b-harga text-end" required></td>
                    <td>
                        <select name="kategori_beli[]" class="form-select form-select-sm" required>
                            <option value="BENGKEL MOBIL">BENGKEL MOBIL</option>
                            <option value="BENGKEL LISTRIK">BENGKEL LISTRIK</option>
                            <option value="KANTOR">KANTOR</option>
                        </select>
                    </td>
                    <td>
                        <select name="alokasi_stok[]" class="form-select form-select-sm">
                            <option value="LANGSUNG PAKAI">LANGSUNG PAKAI</option>
                            <option value="MASUK STOK">MASUK STOK</option>
                        </select>
                    </td>
                    <td><input type="text" class="form-control form-control-sm b-total bg-light fw-bold text-end" readonly></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-baris border-0"><i class="fas fa-times"></i></button></td>
                </tr>
            `);
            hitungSemua();
        }
    });

    $(document).on('input', '.b-qty, .b-harga', function(){ hitungSemua(); });
    $(document).on('click', '.remove-baris', function(){ $(this).closest('tr').remove(); hitungSemua(); });
});

function hitungSemua() {
    let grandTotal = 0;
    $('.baris-beli').each(function(){
        let q = parseFloat($(this).find('.b-qty').val()) || 0;
        let h = parseFloat($(this).find('.b-harga').val()) || 0;
        let sub = q * h;
        // Tampilkan subtotal dengan format ribuan
        $(this).find('.b-total').val(sub.toLocaleString('id-ID'));
        grandTotal += sub;
    });
    // Tampilkan Grand Total dengan format Rupiah
    $('#grandTotalDisplay').text(new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(grandTotal));
}

function prosesBeli(id) {
    $('#formBeli')[0].reset(); // Reset form agar bersih
    $('#modalTambah').modal('show');
    // Beri sedikit delay agar modal terbuka sempurna sebelum trigger ajax
    setTimeout(function(){
        $('#pilih_pr').val(id).trigger('change');
    }, 300);
}

function viewPR(id) {
    $('#modalView').modal('show');
    $('#kontenView').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i> Memuat Data...</div>');
    $.ajax({
        url: 'ajax_view_pr.php',
        type: 'GET',
        data: {id: id},
        success: function(response) {
            $('#kontenView').html(response);
        }
    });
}
</script>
</body>
</html>
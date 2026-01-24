<?php
session_start();
include '../../config/koneksi.php';

if ($_SESSION['status'] != "login") {
    header("location:../../login.php?pesan=belum_login");
    exit;
}

$id = $_GET['id'];
// Ambil data Header
$query_h = mysqli_query($koneksi, "SELECT * FROM tr_request WHERE id_request = '$id'");
$h = mysqli_fetch_array($query_h);

if($h['status_request'] != 'PENDING') {
    echo "<script>alert('Data sudah diproses, tidak bisa diedit!'); window.location='pr.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Request - MCP System</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --mcp-blue: #0000FF; }
        body { background-color: #f4f7f6; font-size: 0.85rem; }
        .table-input thead { background: var(--mcp-blue); color: white; font-size: 0.75rem; text-transform: uppercase; }
        input, select { text-transform: uppercase; }
        .info-audit { font-size: 0.75rem; color: #6c757d; background: #eee; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body class="py-4">
<div class="container-fluid">
    <form action="proses_edit_request.php" method="POST">
        <input type="hidden" name="id_request" value="<?= $h['id_request'] ?>">
        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-primary"><i class="fas fa-edit me-2"></i> EDIT PURCHASE REQUEST</h5>
                <div class="info-audit">
                    <i class="fas fa-history me-1"></i> 
                    Dibuat oleh: <strong><?= $h['created_by'] ?></strong> 
                    <?php if(!empty($h['updated_by'])): ?>
                        | Terakhir diedit: <strong><?= $h['updated_by'] ?></strong> (<?= date('d/m/Y H:i', strtotime($h['updated_at'])) ?>)
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">NOMOR REQUEST</label>
                        <input type="text" name="no_request" class="form-control bg-light fw-bold" value="<?= $h['no_request'] ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">TANGGAL REQUEST</label>
                        <input type="date" name="tgl_request" class="form-control" value="<?= $h['tgl_request'] ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">NAMA PEMESAN</label>
                        <input type="text" name="nama_pemesan" class="form-control" value="<?= $h['nama_pemesan'] ?>" required>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-input" id="tableItem">
                        <thead>
                            <tr class="text-center">
                                <th>Nama Barang</th>
                                <th width="12%">Kategori</th>
                                <th width="15%">Kwalifikasi (Merk)</th>
                                <th width="12%">Mobil</th>
                                <th width="7%">Qty</th>
                                <th width="10%">Satuan</th>
                                <th width="12%">Harga</th>
                                <th width="3%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // PERBAIKAN: Query JOIN ke master_barang agar nama barang biasa terdeteksi
                            $query_d = mysqli_query($koneksi, "SELECT d.*, b.nama_barang as nama_barang_master 
                                                               FROM tr_request_detail d 
                                                               LEFT JOIN master_barang b ON d.id_barang = b.id_barang 
                                                               WHERE d.id_request = '$id'");
                            
                            while($d = mysqli_fetch_array($query_d)) {
                                // PERBAIKAN: Logika pilih nama barang (Manual atau Master)
                                $nama_tampil = !empty($d['nama_barang_manual']) ? $d['nama_barang_manual'] : $d['nama_barang_master'];
                            ?>
                            <tr class="item-row">
                                <td><input type="text" name="nama_barang[]" class="form-control form-control-sm" value="<?= $nama_tampil ?>" required></td>
                                <td>
                                    <select name="kategori_request[]" class="form-select form-select-sm">
                                        <option value="BENGKEL MOBIL" <?= $d['kategori_barang'] == 'BENGKEL MOBIL' ? 'selected' : '' ?>>BENGKEL MOBIL</option>
                                        <option value="KANTOR" <?= $d['kategori_barang'] == 'KANTOR' ? 'selected' : '' ?>>KANTOR</option>
                                        <option value="BANGUNAN" <?= $d['kategori_barang'] == 'BANGUNAN' ? 'selected' : '' ?>>BANGUNAN</option>
                                        <option value="LAIN-LAIN" <?= $d['kategori_barang'] == 'LAIN-LAIN' ? 'selected' : '' ?>>LAIN-LAIN</option>
                                    </select>
                                </td>
                                <td><input type="text" name="kwalifikasi[]" class="form-control form-control-sm" value="<?= $d['kwalifikasi'] ?>" placeholder="MERK/SPEK"></td>
                                <td>
                                    <select name="id_mobil[]" class="form-select form-select-sm">
                                        <option value="0">-- NON MOBIL --</option>
                                        <?php
                                        $mbl = mysqli_query($koneksi, "SELECT id_mobil, plat_nomor FROM master_mobil WHERE status_aktif='AKTIF' ORDER BY plat_nomor ASC");
                                        while($m = mysqli_fetch_array($mbl)){
                                            $sel = ($m['id_mobil'] == $d['id_mobil']) ? 'selected' : '';
                                            echo "<option value='".$m['id_mobil']."' $sel>".$m['plat_nomor']."</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td><input type="number" name="jumlah[]" class="form-control form-control-sm text-center" value="<?= $d['jumlah'] ?>" step="0.01" required></td>
                                <td><input type="text" name="satuan[]" class="form-control form-control-sm" value="<?= $d['satuan'] ?>" required></td>
                                <td><input type="number" name="harga[]" class="form-control form-control-sm text-end" value="<?= $d['harga_satuan_estimasi'] ?>"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-row border-0"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" id="addRow" class="btn btn-sm btn-success fw-bold"><i class="fas fa-plus me-1"></i> Tambah Baris</button>
            </div>
            <div class="card-footer bg-white py-3">
                <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm">SIMPAN PERUBAHAN</button>
                <a href="pr.php" class="btn btn-danger px-4 fw-bold">BATAL</a>
            </div>
        </div>
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    $("#addRow").click(function(){
        var newRow = $('.item-row:first').clone();
        newRow.find('input').val('');
        newRow.find('select').val('0');
        newRow.find('.form-select').first().val('BENGKEL MOBIL');
        $("#tableItem tbody").append(newRow);
    });
    $(document).on('click', '.remove-row', function(){
        if($(".item-row").length > 1) $(this).closest('tr').remove();
    });
});
</script>
</body>
</html>
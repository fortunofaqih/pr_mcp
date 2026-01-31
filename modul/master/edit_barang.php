<?php
session_start();
include '../../config/koneksi.php';

if ($_SESSION['status'] != "login") {
    header("location:../../login.php?pesan=belum_login");
    exit;
}

$id = mysqli_real_escape_string($koneksi, $_GET['id']);

// Ambil data barang DAN ambil nilai dari log pertama sebagai Stok Awal
$sql = "SELECT b.*, 
        (SELECT qty FROM tr_stok_log WHERE id_barang = b.id_barang ORDER BY id_log ASC LIMIT 1) as stok_awal_log
        FROM master_barang b 
        WHERE b.id_barang='$id'";

$query = mysqli_query($koneksi, $sql);
$data = mysqli_fetch_array($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang - MCP System</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .card { border-radius: 15px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .header-edit { background: #00008B; color: white; border-radius: 15px 15px 0 0; padding: 20px; }
        input, select { text-transform: uppercase; }
        .audit-section { background: #f8f9fa; border-left: 4px solid #0dcaf0; }
    </style>
</head>
<body class="py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="header-edit text-center">
                    <h5 class="m-0 fw-bold text-uppercase"><i class="fas fa-edit me-2"></i> Update Data Barang</h5>
                </div>
                <div class="card-body p-4">
                    <form action="update_barang.php" method="POST">
                        <input type="hidden" name="id_barang" value="<?php echo $data['id_barang']; ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">NAMA BARANG / ITEMS</label>
                            <input type="text" name="nama_barang" class="form-control" value="<?php echo $data['nama_barang']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">MERK / BRAND</label>
                            <input type="text" name="merk" class="form-control" value="<?= $data['merk']; ?>">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">LOKASI RAK</label>
                                <input type="text" name="lokasi_rak" class="form-control" value="<?php echo $data['lokasi_rak']; ?>" placeholder="Contoh: RAK-A1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">SATUAN UTAMA</label>
                                <select name="satuan" class="form-select">
                                    <?php 
                                    $satuan_list = ['PCS', 'SET', 'DUS', 'PACK', 'METER', 'CM','LEMBAR','KG', 'LITER','ML','LONJOR','SET','ROLL','PACK','UNIT','DRUM','SAK','BOTOL','TUBE','GALON','IKAT','LEMBAR','PAIL'];
                                    foreach($satuan_list as $s) {
                                        $selected = ($data['satuan'] == $s) ? 'selected' : '';
                                        echo "<option value='$s' $selected>$s</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <div class="bg-light p-3 rounded border mb-4">
                            <h6 class="fw-bold text-primary mb-3 small text-uppercase"><i class="fas fa-warehouse me-2"></i> Posisi Stok Gudang</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">SALDO AWAL</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control bg-white" value="<?php echo $data['stok_awal_log']; ?>" readonly>
                                        <span class="input-group-text small"><?php echo $data['satuan']; ?></span>
                                    </div>
                                    <div style="font-size: 0.65rem;" class="text-muted mt-1">* Stok saat pendaftaran pertama</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-danger text-uppercase">Sisa (Stok Akhir)</label>
                                    <div class="input-group border-danger">
                                        <input type="number" name="stok_akhir" class="form-control border-danger fw-bold text-danger" value="<?php echo $data['stok_akhir']; ?>">
                                        <span class="input-group-text bg-danger text-white border-danger small"><?php echo $data['satuan']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">STATUS AKTIF</label>
                            <select name="status_aktif" class="form-select">
                                <option value="AKTIF" <?php if($data['status_aktif'] == 'AKTIF') echo 'selected'; ?>>AKTIF</option>
                                <option value="NONAKTIF" <?php if($data['status_aktif'] == 'NONAKTIF') echo 'selected'; ?>>NONAKTIF</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">KATEGORI BARANG</label>
                            <select name="kategori" class="form-select" required>
                                <?php 
                                // Daftar kategori sesuai permintaan Bapak
                                $kategori_list = [
                                    'UMUM'   => 'KANTOR (ATK/UMUM)',
                                    'BANGUNAN' => 'BANGUNAN',
                                    'LAS' => 'LAS',
                                    'MOBIL'    => 'BENGKEL - MOBIL',
                                    'LISTRIK'  => 'BENGKEL - LISTRIK',
                                    'DINAMO'   => 'BENGKEL - DINAMO',
                                    'BUBUT'    => 'BENGKEL - BUBUT'
                                ];

                                foreach($kategori_list as $key => $val) {
                                    // Jika kategori di database sama dengan daftar, maka beri label 'selected'
                                    $sel = ($data['kategori'] == $key) ? 'selected' : '';
                                    echo "<option value='$key' $sel>$val</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg shadow fw-bold">
                                <i class="fas fa-save me-2"></i> SIMPAN PERUBAHAN
                            </button>
                            <a href="data_barang.php" class="btn btn-danger">BATAL / KEMBALI</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
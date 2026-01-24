<?php
session_start();
include '../../config/koneksi.php';

if ($_SESSION['status'] != "login") {
    header("location:../../login.php");
    exit;
}

// Generate No. Pemusnahan Otomatis (PMS-202601-0001)
$bulan = date('Ym');
$query_no = mysqli_query($koneksi, "SELECT MAX(no_pemusnahan) as max_no FROM tr_pemusnahan WHERE no_pemusnahan LIKE 'PMS-$bulan-%'");
$data_no = mysqli_fetch_array($query_no);
$no_urut = (int) substr($data_no['max_no'] ?? '', -4);
$no_urut++;
$no_pms = "PMS-" . $bulan . "-" . sprintf("%04s", $no_urut);

// Proses Simpan Pemusnahan
if(isset($_POST['simpan_pemusnahan'])){
    $no_pms_input = $_POST['no_pemusnahan'];
    $tgl          = $_POST['tgl_pemusnahan'];
    $id_barang    = $_POST['id_barang'];
    $qty          = (int)$_POST['qty_dimusnahkan']; // Pastikan integer
    $satuan       = $_POST['satuan'];
    $metode       = $_POST['metode_pemusnahan'];
    $nilai_jual   = (int)($_POST['nilai_jual_scrap'] ?? 0); // Pastikan integer
    $alasan       = mysqli_real_escape_string($koneksi, strtoupper($_POST['alasan_pemusnahan']));
    $id_user      = $_SESSION['id_user'];

    mysqli_begin_transaction($koneksi);
    try {
        // 1. Cek stok dengan FOR UPDATE untuk mengunci baris agar tidak bentrok
        $cek_query = mysqli_query($koneksi, "SELECT stok_akhir FROM master_barang WHERE id_barang='$id_barang' FOR UPDATE");
        $cek = mysqli_fetch_array($cek_query);
        
        if(!$cek || $qty > $cek['stok_akhir']){
            throw new Exception("Qty pemusnahan melebihi stok tersedia!");
        }

        // 2. Simpan ke tr_pemusnahan
        mysqli_query($koneksi, "INSERT INTO tr_pemusnahan (no_pemusnahan, tgl_pemusnahan, id_barang, qty_dimusnahkan, satuan, metode_pemusnahan, nilai_jual_scrap, alasan_pemusnahan, id_user) 
                      VALUES ('$no_pms_input', '$tgl', '$id_barang', '$qty', '$satuan', '$metode', '$nilai_jual', '$alasan', '$id_user')");
        
        // 3. Potong stok di Master
        mysqli_query($koneksi, "UPDATE master_barang SET stok_akhir = stok_akhir - $qty WHERE id_barang='$id_barang'");

        // 4. PENTING: Catat di Kartu Stok agar tidak jadi "Stok Gaib"
        $keterangan_log = "PEMUSNAHAN: " . $metode . " (" . $alasan . ")";
        mysqli_query($koneksi, "INSERT INTO tr_stok_log (tgl_transaksi, id_barang, tipe_transaksi, qty, satuan, keterangan, id_user) 
                      VALUES ('$tgl', '$id_barang', 'KELUAR', '$qty', '$satuan', '$keterangan_log', '$id_user')");

        mysqli_commit($koneksi);
        echo "<script>alert('Pemusnahan Berhasil Diproses!'); window.location='pemusnahan.php';</script>";
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $pesan = $e->getMessage();
        echo "<script>alert('Gagal: $pesan'); window.location='pemusnahan.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pemusnahan Barang - MCP</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        :root { --mcp-red: #d63031; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; font-size: 0.85rem; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05); border-radius: 10px; }
        .bg-red { background-color: var(--mcp-red) !important; color: white; }
        .btn-red { background-color: var(--mcp-red); color: white; }
        .btn-red:hover { background-color: #b32424; color: white; }
        .table thead { background-color: #f1f3f5; }
        input, select, textarea { text-transform: uppercase; }
        .stok-info { background: #fff5f5; border: 1px solid #feb2b2; padding: 10px; border-radius: 8px; }
    </style>
</head>
<body class="py-4">

<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="m-0 fw-bold text-dark text-uppercase">
                        <i class="fas fa-trash-alt me-2 text-danger"></i>Riwayat Pemusnahan Barang
                    </h5>
                </div>
                <div class="d-flex gap-2">
                    <a href="../../index.php" class="btn btn-sm btn-danger px-2"><i class="fas fa-rotate-left"></i> Kembali</a>
                    <button type="button" class="btn btn-sm btn-red px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalPms">
                        <i class="fas fa-plus-circle me-1"></i> INPUT PEMUSNAHAN (WRITE-OFF)
                    </button>
                    
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0" id="tabelPms">
                    <thead>
                        <tr class="text-center small fw-bold text-uppercase">
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Nama Barang</th>
                            <th>Qty</th>
                            <th>Metode</th>
                            <th>Nilai Scrap</th>
                            <th>Petugas</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php
                        $q = mysqli_query($koneksi, "SELECT p.*, m.nama_barang, u.nama_lengkap 
                             FROM tr_pemusnahan p 
                             JOIN master_barang m ON p.id_barang = m.id_barang 
                             JOIN users u ON p.id_user = u.id_user 
                             ORDER BY p.id_pemusnahan DESC");
                        while($h = mysqli_fetch_array($q)):
                        ?>
                        <tr>
                            <td class="text-center fw-bold text-danger"><?= $h['no_pemusnahan'] ?></td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($h['tgl_pemusnahan'])) ?></td>
                            <td class="fw-bold"><?= $h['nama_barang'] ?></td>
                            <td class="text-center"><?= $h['qty_dimusnahkan'] ?> <?= $h['satuan'] ?></td>
                            <td class="text-center"><span class="badge bg-secondary"><?= $h['metode_pemusnahan'] ?></span></td>
                            <td class="text-end">Rp <?= number_format($h['nilai_jual_scrap'], 0, ',', '.') ?></td>
                            <td class="text-center small"><?= $h['nama_lengkap'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPms" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-red">
                <h6 class="modal-title fw-bold"><i class="fas fa-fire me-2"></i>FORM PENGHAPUSAN / PEMUSNAHAN BARANG</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">NO. TRANSAKSI</label>
                            <input type="text" name="no_pemusnahan" class="form-control fw-bold text-danger bg-light" value="<?= $no_pms ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">TANGGAL PEMUSNAHAN</label>
                            <input type="date" name="tgl_pemusnahan" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold mb-1">PILIH BARANG GUDANG (REAL-TIME)</label>
                        <select name="id_barang" id="id_barang" class="form-select select2-barang" onchange="updateInfo()" required>
                            <option value="">-- KETIK NAMA BARANG --</option>
                            <?php
                            // Query menghitung saldo real dari tr_stok_log (Kartu Stok)
                            $sql_realtime = "SELECT 
                                                m.id_barang, 
                                                m.nama_barang, 
                                                m.satuan,
                                                (SELECT COALESCE(SUM(CASE WHEN tipe_transaksi = 'MASUK' THEN qty ELSE 0 END), 0) FROM tr_stok_log WHERE id_barang = m.id_barang) -
                                                (SELECT COALESCE(SUM(CASE WHEN tipe_transaksi = 'KELUAR' THEN qty ELSE 0 END), 0) FROM tr_stok_log WHERE id_barang = m.id_barang) as stok_log
                                            FROM master_barang m 
                                            HAVING stok_log > 0 
                                            ORDER BY m.nama_barang ASC";
                            
                            $brg = mysqli_query($koneksi, $sql_realtime);
                            while($b = mysqli_fetch_array($brg)){
                                echo "<option value='{$b['id_barang']}' 
                                            data-satuan='{$b['satuan']}' 
                                            data-stok='{$b['stok_log']}'>
                                        {$b['nama_barang']} (Sistem: {$b['stok_log']} {$b['satuan']})
                                    </option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="stok-info mb-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <label class="small fw-bold text-danger">QTY DIMUSNAHKAN</label>
                                <input type="number" name="qty_dimusnahkan" id="qty_input" class="form-control form-control-lg fw-bold" min="1" required>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">SATUAN</label>
                                <input type="text" name="satuan" id="satuan_input" class="form-control form-control-lg bg-white text-center" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">METODE PEMUSNAHAN</label>
                                <select name="metode_pemusnahan" class="form-select form-select-lg" required>
                                    <option value="DIHANCURKAN">DIHANCURKAN (SCRAP)</option>
                                    <option value="DIJUAL">DIJUAL (ROMBENG)</option>
                                    <option value="DIBUANG">DIBUANG (SAMPAH)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold mb-1">NILAI JUAL SCRAP (ISI 0 JIKA TIDAK DIJUAL)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light">Rp</span>
                            <input type="number" name="nilai_jual_scrap" class="form-control" value="0">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="small fw-bold mb-1">ALASAN PEMUSNAHAN / KERUSAKAN</label>
                        <textarea name="alasan_pemusnahan" class="form-control" rows="3" placeholder="CONTOH: BARANG RUSAK KARENA BENCANA / KADALUARSA" required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-danger" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" name="simpan_pemusnahan" class="btn btn-sm btn-red px-4 fw-bold">
                        <i class="fas fa-check-circle me-1"></i> KONFIRMASI PEMUSNAHAN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    function updateInfo() {
        const select = document.getElementById('id_barang');
        const selected = select.options[select.selectedIndex];
        const stokMax = selected.getAttribute('data-stok');
        const satuan = selected.getAttribute('data-satuan');
        
        document.getElementById('satuan_input').value = satuan;
        const qtyInp = document.getElementById('qty_input');
        qtyInp.max = stokMax;
        qtyInp.placeholder = "MAX: " + stokMax;

        qtyInp.addEventListener('input', function() {
    if (parseInt(this.value) > parseInt(this.max)) {
        alert('Qty tidak boleh melebihi stok!');
        this.value = this.max;
    }
    });
    }

    $(document).ready(function () {
        $('#tabelPms').DataTable({
            "pageLength": 10,
            "order": [[0, "desc"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
            }
        });
    });
    $(document).ready(function() {
    // Inisialisasi Select2
    $('.select2-barang').select2({
        theme: 'bootstrap-5',
        placeholder: '-- CARI NAMA BARANG --',
        allowClear: true,
        dropdownParent: $('#modalPms') // PENTING: Agar search bisa diketik di dalam modal
    });

    // Karena Select2 mengubah elemen select, kita perlu trigger updateInfo secara manual saat select2 berubah
    $('.select2-barang').on('select2:select', function (e) {
        updateInfo();
    });
});
function updateInfo() {
    const select = document.getElementById('id_barang');
    const selected = select.options[select.selectedIndex];
    
    if (selected.value !== "") {
        const stokMax = selected.getAttribute('data-stok');
        const satuan = selected.getAttribute('data-satuan');
        
        document.getElementById('satuan_input').value = satuan;
        const qtyInp = document.getElementById('qty_input');
        
        // Update atribut input
        qtyInp.max = stokMax;
        qtyInp.placeholder = "MAKSIMAL: " + stokMax;
        qtyInp.value = ""; // Reset qty saat ganti barang
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


</body>
</html>
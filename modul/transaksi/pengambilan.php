<?php
session_start();
include '../../config/koneksi.php';

if ($_SESSION['status'] != "login") {
    header("location:../../login.php");
    exit;
}

// 1. Generate No. Permintaan Otomatis (Tetap Sama)
$bulan_sekarang = date('Ym');
$query_no = mysqli_query($koneksi, "SELECT MAX(no_permintaan) as max_no FROM bon_permintaan WHERE no_permintaan LIKE 'PB-$bulan_sekarang-%'");
$data_no = mysqli_fetch_array($query_no);
$no_urut = (int) substr($data_no['max_no'] ?? '', -4);
$no_urut++;
$no_permintaan = "PB-" . $bulan_sekarang . "-" . sprintf("%04s", $no_urut);

// 2. Proses Simpan
if(isset($_POST['simpan'])){
    $no_req     = $_POST['no_permintaan'];
    $tgl        = $_POST['tgl_keluar'];
    $penerima   = mysqli_real_escape_string($koneksi, strtoupper($_POST['penerima']));
    $id_barang  = $_POST['id_barang'];
    $qty        = (float)$_POST['qty_keluar'];
    $keperluan  = mysqli_real_escape_string($koneksi, strtoupper($_POST['keperluan']));

    // --- PERBAIKAN LOGIKA CEK STOK (Berdasarkan LOG) ---
    $sql_cek = "SELECT 
                (SELECT SUM(qty) FROM tr_stok_log WHERE id_barang = '$id_barang' AND tipe_transaksi = 'MASUK') as t_masuk,
                (SELECT SUM(qty) FROM tr_stok_log WHERE id_barang = '$id_barang' AND tipe_transaksi = 'KELUAR') as t_keluar";
    $res_cek = mysqli_fetch_array(mysqli_query($koneksi, $sql_cek));
    $stok_sebenarnya = ($res_cek['t_masuk'] ?? 0) - ($res_cek['t_keluar'] ?? 0);
    
    if($qty > $stok_sebenarnya){
        echo "<script>alert('Gagal! Stok tidak mencukupi. Sisa stok akurat: ".$stok_sebenarnya."'); window.location='pengambilan.php';</script>";
    } else {
        // A. Insert ke tabel bon_permintaan
        $query = mysqli_query($koneksi, "INSERT INTO bon_permintaan (no_permintaan, id_barang, tgl_keluar, qty_keluar, penerima, keperluan) 
                  VALUES ('$no_req', '$id_barang', '$tgl', '$qty', '$penerima', '$keperluan')");
        
        $id_cetak = mysqli_insert_id($koneksi); 

        // B. Update stok di master_barang (Sebagai cadangan/backup angka cepat)
        mysqli_query($koneksi, "UPDATE master_barang SET stok_akhir = stok_akhir - $qty WHERE id_barang='$id_barang'");

        // C. Catat ke tr_stok_log (PENTING: Ini yang akan dibaca oleh data_barang.php)
        $keterangan_log = "PENGAMBILAN: $penerima ($keperluan)";
        $waktu_sekarang = date('H:i:s');
        
        mysqli_query($koneksi, "INSERT INTO tr_stok_log (id_barang, tgl_log, tipe_transaksi, qty, keterangan) 
                  VALUES ('$id_barang', '$tgl $waktu_sekarang', 'KELUAR', '$qty', '$keterangan_log')");

        if($query){
            echo "<script>
                    if(confirm('Berhasil Simpan & Potong Stok! Ingin Cetak Bukti Pengambilan?')){
                        window.open('cetak_permintaan.php?id=$id_cetak', '_blank');
                    }
                    window.location='pengambilan.php';
                  </script>";
        }
    }
}
?>      
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Permintaan Barang - MCP</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        :root { --mcp-blue: #0000FF; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; font-size: 0.85rem; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05); border-radius: 10px; }
        .bg-mcp { background-color: var(--mcp-blue) !important; color: white; }
        input, select, textarea { text-transform: uppercase; }
        .btn-mcp { background-color: var(--mcp-blue); color: white; }
        .stok-label { background: #e7f0ff; padding: 10px; border-radius: 8px; border-left: 4px solid blue; }
    </style>
</head>
<body class="py-4">

<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold text-dark"><i class="fas fa-clipboard-list me-2 text-primary"></i>RIWAYAT PENGAMBILAN</h5>
                <div class="d-flex gap-2">
                    <a href="../../index.php" class="btn btn-sm btn-danger px-3"><i class="fas fa-arrow-left"></i> KEMBALI</a>
                    <button type="button" class="btn btn-sm btn-mcp px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalAmbil">
                        <i class="fas fa-plus-circle me-1"></i> BUAT PERMINTAAN
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle" id="tabelHistori">
                    <thead class="bg-light">
                        <tr class="text-center small fw-bold">
                            <th>NO. PB</th>
                            <th>TANGGAL</th>
                            <th>NAMA BARANG</th>
                            <th>QTY</th>
                            <th>PENERIMA</th>
                            <th>KEPERLUAN</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $histori = mysqli_query($koneksi, "SELECT b.*, m.nama_barang, m.satuan FROM bon_permintaan b JOIN master_barang m ON b.id_barang=m.id_barang ORDER BY b.id_bon DESC");
                        while($h = mysqli_fetch_array($histori)):
                        ?>
                        <tr>
                            <td class="text-center fw-bold text-primary"><?= $h['no_permintaan'] ?></td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($h['tgl_keluar'])) ?></td>
                            <td class="fw-bold"><?= $h['nama_barang'] ?></td>
                            <td class="text-center text-danger fw-bold"><?= number_format($h['qty_keluar'], 0) ?> <?= $h['satuan'] ?></td>
                            <td><?= $h['penerima'] ?></td>
                            <td class="small"><?= $h['keperluan'] ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="cetak_permintaan.php?id=<?= $h['id_bon'] ?>" target="_blank" class="btn btn-sm btn-success"><i class="fas fa-print"></i></a>
                                    
                                    <button type="button" class="btn btn-sm btn-warning btn-edit" 
                                            data-id="<?= $h['id_bon'] ?>" data-barang="<?= $h['nama_barang'] ?>"
                                            data-qty="<?= $h['qty_keluar'] ?>" data-penerima="<?= $h['penerima'] ?>"
                                            data-keperluan="<?= $h['keperluan'] ?>"><i class="fas fa-edit"></i></button>

                                    <a href="proses_hapus_pengambilan.php?id=<?= $h['id_bon'] ?>" 
                                    class="btn btn-sm btn-danger" 
                                    onclick="return confirm('BATALKAN PENGAMBILAN?\n\nBarang: <?= $h['nama_barang'] ?>\nQty: <?= $h['qty_keluar'] ?>\n\nStok akan dikembalikan ke gudang dan data ini akan dihapus.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAmbil" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-mcp">
                <h6 class="modal-title fw-bold text-white">FORM PENGELUARAN BARANG</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">NO. PERMINTAAN</label>
                            <input type="text" name="no_permintaan" class="form-control fw-bold bg-light" value="<?= $no_permintaan ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">TANGGAL AMBIL</label>
                            <input type="date" name="tgl_keluar" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold mb-1">PENERIMA BARANG</label>
                        <input type="text" name="penerima" class="form-control" required placeholder="NAMA KARYAWAN / TEKNISI">
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold mb-1">PILIH BARANG</label>
                        <select name="id_barang" id="id_barang" class="form-select select2-barang border-primary" onchange="cekStok()" required>
                            <option value="">-- PILIH/KETIK BARANG --</option>
                            <?php
                            // Load barang yang punya saldo di LOG
                            $sql_load = "SELECT b.id_barang, b.nama_barang, b.satuan,
                                        (SELECT SUM(qty) FROM tr_stok_log WHERE id_barang = b.id_barang AND tipe_transaksi = 'MASUK') as masuk,
                                        (SELECT SUM(qty) FROM tr_stok_log WHERE id_barang = b.id_barang AND tipe_transaksi = 'KELUAR') as keluar
                                        FROM master_barang b ORDER BY b.nama_barang ASC";
                            $res_load = mysqli_query($koneksi, $sql_load);
                            while($b = mysqli_fetch_array($res_load)){
                                $sisa = ($b['masuk'] ?? 0) - ($b['keluar'] ?? 0);
                                if($sisa > 0) {
                                    echo "<option value='{$b['id_barang']}' data-stok='{$sisa}' data-satuan='{$b['satuan']}'>{$b['nama_barang']} (SISA: $sisa {$b['satuan']})</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="stok-label mb-3">
                        <div class="row align-items-center">
                            <div class="col-6 text-center">
                                <span class="small fw-bold text-muted">STOK TERSEDIA:</span><br>
                                <span id="txt_stok" class="fw-bold fs-4 text-primary">0</span> <span id="txt_satuan" class="fw-bold"></span>
                            </div>
                            <div class="col-6 border-start border-2">
                                <label class="small fw-bold text-danger">JUMLAH KELUAR:</label>
                                <input type="number" name="qty_keluar" id="qty_input" class="form-control form-control-lg fw-bold border-danger" min="1" step="any" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="small fw-bold mb-1">KEPERLUAN / LOKASI</label>
                        <textarea name="keperluan" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="simpan" class="btn btn-mcp w-100 fw-bold py-2">SIMPAN & POTONG STOK</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Inisialisasi DataTables
        $('#tabelHistori').DataTable({ 
            "order": [[0, "desc"]] 
        });

        // Inisialisasi Select2
        $('.select2-barang').select2({
            theme: 'bootstrap-5',
            placeholder: '-- CARI NAMA BARANG --',
            allowClear: true,
            dropdownParent: $('#modalAmbil') // Solusi agar kolom search bisa diketik di modal
        });

        // Trigger fungsi cekStok saat Select2 berubah
        $('.select2-barang').on('select2:select', function (e) {
            cekStok();
        });
    });

    function cekStok() {
        const select = document.getElementById('id_barang');
        const selected = select.options[select.selectedIndex];
        
        // Ambil data stok dan satuan dari atribut data-
        const stok = parseFloat(selected.getAttribute('data-stok')) || 0;
        const satuan = selected.getAttribute('data-satuan') || "";
        
        // Update tampilan label
        document.getElementById('txt_stok').innerText = stok;
        document.getElementById('txt_satuan').innerText = satuan;
        
        // Atur maksimal input qty
        const qtyInput = document.getElementById('qty_input');
        qtyInput.max = stok;
        qtyInput.value = ""; // Reset value agar user input ulang
    }

    // Validasi saat input manual
    document.getElementById('qty_input').addEventListener('input', function() {
        const tersedia = parseFloat(document.getElementById('txt_stok').innerText);
        if (parseFloat(this.value) > tersedia) {
            alert("STOK TIDAK MENCUKUPI!");
            this.value = tersedia;
        }
    });
    $(document).on('click', '.btn-edit', function() {
    var d = $(this).data();
    $('#edit_id').val(d.id);
    $('#edit_barang').val(d.barang);
    $('#edit_penerima').val(d.penerima);
    $('#edit_qty').val(d.qty);
    $('#edit_qty_lama').val(d.qty);
    $('#edit_keperluan').val(d.keperluan);
    $('#modalEditAmbil').modal('show');
});
</script>
<div class="modal fade" id="modalEditAmbil" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h6 class="modal-title fw-bold">KOREKSI PENGAMBILAN BARANG</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_edit_pengambilan.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_bon" id="edit_id">
                    <div class="mb-3">
                        <label class="small fw-bold">NAMA BARANG</label>
                        <input type="text" id="edit_barang" class="form-control bg-light" readonly>
                        <small class="text-muted">*Barang tidak dapat diubah, silakan hapus & buat baru jika salah barang.</small>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">PENERIMA</label>
                        <input type="text" name="penerima" id="edit_penerima" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-danger">JUMLAH KELUAR (QTY)</label>
                        <input type="number" name="qty_baru" id="edit_qty" class="form-control fw-bold border-danger" step="any" required>
                        <input type="hidden" name="qty_lama" id="edit_qty_lama">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">KEPERLUAN</label>
                        <textarea name="keperluan" id="edit_keperluan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">SIMPAN PERUBAHAN</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
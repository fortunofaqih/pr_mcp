<?php
include '../../config/koneksi.php';

$id = mysqli_real_escape_string($koneksi, $_GET['id']);

// 1. Ambil data header
$query_header = mysqli_query($koneksi, "SELECT * FROM tr_request WHERE id_request = '$id'");
$h = mysqli_fetch_array($query_header);

if (!$h) {
    echo "<div class='p-4 text-center text-danger'>Data tidak ditemukan.</div>";
    exit;
}
?>

<div class="p-3 bg-light border-bottom">
    <div class="row small fw-bold text-uppercase">
        <div class="col-md-4">
            <span class="text-muted d-block" style="font-size: 10px;">No. Request:</span>
            <span class="text-primary" style="font-size: 14px;"><?= $h['no_request'] ?></span>
        </div>
        <div class="col-md-4 text-center border-start border-end">
            <span class="text-muted d-block" style="font-size: 10px;">Pemesan:</span>
            <span><?= strtoupper($h['nama_pemesan']) ?></span>
        </div>
        <div class="col-md-4 text-end">
            <span class="text-muted d-block" style="font-size: 10px;">Tanggal:</span>
            <span><?= date('d/m/Y', strtotime($h['tgl_request'])) ?></span>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover mb-0" style="font-size: 0.8rem;">
        <thead class="table-dark text-uppercase" style="font-size: 0.7rem;">
            <tr>
                <th class="text-center" width="40">NO</th>
                <th>Nama Barang</th>
                <th>Kwalifikasi</th>
                <th class="text-center">Unit/Mobil</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Harga</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $grand_total = 0;
            
            // PERBAIKAN QUERY: Tambahkan JOIN ke master_barang
            $sql_detail = "SELECT d.*, m.plat_nomor, b.nama_barang as nama_barang_master
                           FROM tr_request_detail d
                           LEFT JOIN master_mobil m ON d.id_mobil = m.id_mobil
                           LEFT JOIN master_barang b ON d.id_barang = b.id_barang
                           WHERE d.id_request = '$id' 
                           ORDER BY d.id_detail ASC";
            
            $query_detail = mysqli_query($koneksi, $sql_detail);

            while($d = mysqli_fetch_array($query_detail)) {
                $subtotal = $d['jumlah'] * $d['harga_satuan_estimasi'];
                $grand_total += $subtotal;

                // LOGIKA: Jika nama_barang_manual kosong, ambil dari nama_barang_master
                $nama_tampil = !empty($d['nama_barang_manual']) ? $d['nama_barang_manual'] : $d['nama_barang_master'];
            ?>
            <tr>
                <td class="text-center text-muted"><?= $no++ ?></td>
                <td class="fw-bold text-dark"><?= strtoupper($nama_tampil) ?></td>
                <td><small><?= $d['kwalifikasi'] ?: '-' ?></small></td>
                <td class="text-center"><?= ($d['id_mobil'] != 0) ? $d['plat_nomor'] : '-' ?></td>
                <td class="text-center fw-bold"><?= (float)$d['jumlah'] ?> <?= $d['satuan'] ?></td>
                <td class="text-end"><?= number_format($d['harga_satuan_estimasi'], 0, ',', '.') ?></td>
                <td class="text-end fw-bold"><?= number_format($subtotal, 0, ',', '.') ?></td>
            </tr>
            <?php } ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="6" class="text-end fw-bold">ESTIMASI GRAND TOTAL :</td>
                <td class="text-end fw-bold text-primary" style="font-size: 1rem;">
                    Rp <?= number_format($grand_total, 0, ',', '.') ?>
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="p-2 bg-white text-end">
    <small class="text-muted italic">* Tampilan ini adalah ringkasan Purchase Request</small>
</div>
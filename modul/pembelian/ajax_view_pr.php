<?php
include '../../config/koneksi.php';
$id = $_GET['id'];

$q = mysqli_query($koneksi, "SELECT * FROM tr_request WHERE id_request = '$id'");
$h = mysqli_fetch_array($q);
?>
<div class="preview-pr-container shadow-sm">
    <div class="text-center mb-3">
        <h5 class="mb-0 fw-bold">PURCHASE REQUEST FORM</h5>
        <small>PT. MUTIARA CAHAYA PLASTINDO</small>
        <hr>
    </div>
    
    <div class="row mb-2 fw-bold small">
        <div class="col-4">NO: <?= $h['no_request'] ?></div>
        <div class="col-4 text-center">PEMESAN: <?= strtoupper($h['nama_pemesan']) ?></div>
        <div class="col-4 text-end">TGL: <?= date('d/m/Y', strtotime($h['tgl_request'])) ?></div>
    </div>

    <table class="table-preview">
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th width="35%">NAMA BARANG</th>
                <th width="15%">UNIT/MOBIL</th>
                <th width="10%">QTY</th>
                <th width="15%">ESTIMASI HARGA</th>
                <th width="10%">STATUS</th> <th width="10%">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no=1; $total=0;
            $det = mysqli_query($koneksi, "SELECT d.*, m.plat_nomor FROM tr_request_detail d LEFT JOIN master_mobil m ON d.id_mobil = m.id_mobil WHERE d.id_request = '$id'");
            
            while($d = mysqli_fetch_array($det)){
                // Tentukan gaya visual berdasarkan status_item
                $status_item = $d['status_item']; // Pastikan nama kolom sesuai di database Anda
                $row_style = "";
                $badge_status = "";
                $sub = $d['jumlah'] * $d['harga_satuan_estimasi'];

                if($status_item == 'REJECTED'){
                    $row_style = "style='background-color: #ffe9e9; color: #a94442; text-decoration: line-through;'";
                    $badge_status = "<span class='badge bg-danger' style='font-size: 0.6rem;'>DITOLAK</span>";
                } else if($status_item == 'APPROVED'){
                    $badge_status = "<span class='badge bg-success' style='font-size: 0.6rem;'>DISETUJUI</span>";
                    $total += $sub; // Total hanya menjumlahkan yang disetujui
                } else {
                    $badge_status = "<span class='badge bg-warning text-dark' style='font-size: 0.6rem;'>PENDING</span>";
                    $total += $sub;
                }

                echo "<tr $row_style>
                    <td class='text-center'>$no</td>
                    <td><b>".strtoupper($d['nama_barang_manual'])."</b><br><small>".$d['kwalifikasi']."</small></td>
                    <td class='text-center'>".($d['plat_nomor'] ?? '-')."</td>
                    <td class='text-center'>".$d['jumlah']." ".$d['satuan']."</td>
                    <td class='text-end'>".number_format($d['harga_satuan_estimasi'])."</td>
                    <td class='text-center'>$badge_status</td>
                    <td class='text-end'>".number_format($sub)."</td>
                </tr>";
                $no++;
            }
            ?>
        </tbody>
        <tfoot>
            <tr class="fw-bold" style="background: #f9f9f9;">
                <td colspan="6" class="text-end">TOTAL YANG DISETUJUI</td>
                <td class="text-end text-primary">Rp <?= number_format($total) ?></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="mt-3 small italic text-muted">
        * Keterangan PR: <?= $h['keterangan'] ?>
    </div>
</div>
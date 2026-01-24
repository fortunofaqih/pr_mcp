<?php
session_start();
include '../../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_bon     = $_POST['id_bon'];
    $penerima   = strtoupper($_POST['penerima']);
    $qty_baru   = (float)$_POST['qty_baru'];
    $qty_lama   = (float)$_POST['qty_lama'];
    $keperluan  = strtoupper($_POST['keperluan']);
    $now        = date('Y-m-d H:i:s');

    // 1. Cari info barang dari ID Bon
    $data_awal = mysqli_fetch_array(mysqli_query($koneksi, "SELECT id_barang, no_permintaan FROM bon_permintaan WHERE id_bon = '$id_bon'"));
    $id_barang = $data_awal['id_barang'];
    $no_pb     = $data_awal['no_permintaan'];

    mysqli_begin_transaction($koneksi);

    try {
        // 2. Kembalikan stok lama ke Master
        mysqli_query($koneksi, "UPDATE master_barang SET stok_akhir = stok_akhir + $qty_lama WHERE id_barang = '$id_barang'");

        // 3. Cek apakah stok cukup untuk QTY Baru
        $sql_cek = "SELECT stok_akhir FROM master_barang WHERE id_barang = '$id_barang'";
        $res_cek = mysqli_fetch_array(mysqli_query($koneksi, $sql_cek));
        
        if($qty_baru > $res_cek['stok_akhir']){
            throw new Exception("Gagal! Stok tidak cukup. Sisa stok tersedia: " . $res_cek['stok_akhir']);
        }

        // 4. Potong stok baru di Master
        mysqli_query($koneksi, "UPDATE master_barang SET stok_akhir = stok_akhir - $qty_baru WHERE id_barang = '$id_barang'");

        // 5. Update Tabel bon_permintaan
        mysqli_query($koneksi, "UPDATE bon_permintaan SET 
                                qty_keluar = '$qty_baru', 
                                penerima = '$penerima', 
                                keperluan = '$keperluan' 
                                WHERE id_bon = '$id_bon'");

        // 6. Update tr_stok_log (Hapus log lama, buat log baru agar history rapi)
        // Kita hapus log pengambilan lama berdasarkan keterangan yang mengandung No. Permintaan tersebut
        $ket_cari = "PENGAMBILAN%"; // Anda bisa menyesuaikan pattern keterangan log Anda
        mysqli_query($koneksi, "DELETE FROM tr_stok_log WHERE id_barang = '$id_barang' AND keterangan LIKE 'PENGAMBILAN: %' AND tgl_log LIKE '".date('Y-m-d')."%' LIMIT 1");
        
        $ket_baru = "EDIT PENGAMBILAN: $penerima ($keperluan)";
        mysqli_query($koneksi, "INSERT INTO tr_stok_log (id_barang, tgl_log, tipe_transaksi, qty, keterangan) 
                                VALUES ('$id_barang', '$now', 'KELUAR', '$qty_baru', '$ket_baru')");

        mysqli_commit($koneksi);
        echo "<script>alert('Berhasil Koreksi Pengambilan!'); window.location='pengambilan.php';</script>";

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        echo "<script>alert('".$e->getMessage()."'); window.location='pengambilan.php';</script>";
    }
}
<?php
session_start();
include '../../config/koneksi.php';

// Menangkap data dari form
$id           = $_POST['id_barang'];
$nama         = strtoupper(mysqli_real_escape_string($koneksi, $_POST['nama_barang']));
$merk           = strtoupper(mysqli_real_escape_string($koneksi, $_POST['merk']));

$lokasi       = strtoupper(mysqli_real_escape_string($koneksi, $_POST['lokasi_rak']));
$satuan       = $_POST['satuan']; // Sesuai dengan name="satuan" di form edit
$stok_akhir   = $_POST['stok_akhir'];
$status_aktif = $_POST['status_aktif'];
$kategori     = mysqli_real_escape_string($koneksi, $_POST['kategori']);
$user_login   = $_SESSION['nama']; 

// 1. Ambil stok lama untuk pengecekan log
$query_lama = mysqli_query($koneksi, "SELECT stok_akhir FROM master_barang WHERE id_barang='$id'");
$lama = mysqli_fetch_array($query_lama);
$stok_lama = $lama['stok_akhir'];

$sql = "UPDATE master_barang SET 
        nama_barang  = '$nama', 
        merk        = '$merk',
        kategori     = '$kategori', 
        lokasi_rak   = '$lokasi', 
        satuan       = '$satuan', 
        stok_akhir   = '$stok_akhir', 
        status_aktif = '$status_aktif'
        WHERE id_barang = '$id'";

if(mysqli_query($koneksi, $sql)){
    
    // 3. Catat ke log jika ada perubahan angka stok akhir
    if($stok_akhir != $stok_lama) {
        $selisih = $stok_akhir - $stok_lama;
        $tipe = ($selisih > 0) ? 'MASUK' : 'KELUAR';
        $qty_log = abs($selisih);
        
        $keterangan = "ADJUSTMENT STOK BY $user_login (STOK LAMA: $stok_lama)";
        
        // Sesuaikan dengan kolom tabel tr_stok_log: id_barang, tipe_transaksi, qty, keterangan
        $log = "INSERT INTO tr_stok_log (id_barang, tipe_transaksi, qty, keterangan) 
                VALUES ('$id', '$tipe', '$qty_log', '$keterangan')";
        mysqli_query($koneksi, $log);
    }

    header("location:data_barang.php?pesan=berhasil_update");
} else {
    echo "Error Database: " . mysqli_error($koneksi);
}
?>
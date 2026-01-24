<?php
session_start(); 
include '../../config/koneksi.php';

if ($_SESSION['status'] != "login") {
    header("location:../../login.php?pesan=belum_login");
    exit;
}

// 1. Tangkap Data dari Form
$nama       = strtoupper(mysqli_real_escape_string($koneksi, $_POST['nama_barang']));
$merk       = strtoupper(mysqli_real_escape_string($koneksi, $_POST['merk']));
$kategori   = mysqli_real_escape_string($koneksi, $_POST['kategori']);
$status     = $_POST['status_aktif'];
$lokasi_rak = strtoupper(mysqli_real_escape_string($koneksi, $_POST['lokasi_rak']));
$stok_input = $_POST['stok_awal']; 
$tgl_pilihan = $_POST['tgl_log']; 
$user_login = $_SESSION['nama']; 
$satuan     = $_POST['satuan'];

// --- TAMBAHAN: CEK APAKAH NAMA BARANG SUDAH ADA (AGAR TIDAK DOUBLE) ---
$cek_dulu = mysqli_query($koneksi, "SELECT * FROM master_barang WHERE nama_barang = '$nama'");
if(mysqli_num_rows($cek_dulu) > 0){
    // Jika sudah ada, kembalikan ke form dengan pesan khusus
    header("location:barang.php?pesan=ada");
    exit;
}

// 2. Insert ke Master Barang
$sql = "INSERT INTO master_barang (nama_barang, merk, kategori, satuan, stok_minimal, stok_akhir, lokasi_rak, status_aktif, created_by) 
        VALUES ('$nama', '$merk', '$kategori', '$satuan', 3, '$stok_input', '$lokasi_rak', '$status', '$user_login')";

if(mysqli_query($koneksi, $sql)){
    $id_baru = mysqli_insert_id($koneksi);
    
    // 3. Insert ke Log Stok (tr_stok_log)
    $log = "INSERT INTO tr_stok_log (id_barang, tgl_log, tipe_transaksi, qty, keterangan, user_input) 
            VALUES ('$id_baru', '$tgl_pilihan', 'MASUK', '$stok_input', 'SALDO AWAL BARANG BARU', '$user_login')";
    
    if(mysqli_query($koneksi, $log)){
        // Jika semua sukses, arahkan ke pesan 'berhasil'
        header("location:barang.php?pesan=berhasil");
    } else {
        // Jika log gagal
        header("location:barang.php?pesan=gagal");
    }
} else {
    // Jika master gagal
    header("location:barang.php?pesan=gagal");
}
?>
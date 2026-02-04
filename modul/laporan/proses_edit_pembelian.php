<?php
session_start();
include '../../config/koneksi.php';

// Proteksi akses langsung dan login
if ($_SESSION['status'] != "login") {
    header("location:../../login.php?pesan=belum_login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Ambil data dan bersihkan (Sanitize)
    $id_pembelian = mysqli_real_escape_string($koneksi, $_POST['id_pembelian']);
    $nama_barang  = mysqli_real_escape_string($koneksi, strtoupper($_POST['nama_barang']));
    $merk_beli    = mysqli_real_escape_string($koneksi, strtoupper($_POST['merk_beli']));
    $supplier     = mysqli_real_escape_string($koneksi, strtoupper($_POST['supplier']));
    $qty          = (float)$_POST['qty'];
    $harga        = (int)$_POST['harga'];
    $alokasi      = mysqli_real_escape_string($koneksi, $_POST['alokasi']);
    
    // DATA BARU: Driver, Plat Nomor, dan Keterangan
    $driver       = mysqli_real_escape_string($koneksi, strtoupper($_POST['driver']));
    $plat_nomor   = mysqli_real_escape_string($koneksi, strtoupper($_POST['plat_nomor']));
    $keterangan   = mysqli_real_escape_string($koneksi, strtoupper($_POST['keterangan']));

    // 2. Validasi Input Dasar
    if (empty($nama_barang) || $qty <= 0 || $harga < 0) {
        header("location:data_pembelian.php?pesan=input_tidak_valid");
        exit;
    }

    // 3. Validasi Keberadaan Barang di Master
    // Catatan: Jika Anda ingin lebih fleksibel (boleh isi barang di luar master), bagian ini bisa dihapus/dikomentari.
    $cek_master = mysqli_query($koneksi, "SELECT nama_barang FROM master_barang WHERE nama_barang = '$nama_barang'");
    if(mysqli_num_rows($cek_master) == 0) {
        header("location:data_pembelian.php?pesan=barang_tidak_terdaftar");
        exit;
    }

    // 4. Mulai Transaksi Database
    mysqli_begin_transaction($koneksi);

    try {
        // Query Update: Menambahkan driver, plat_nomor, dan keterangan
        $sql = "UPDATE pembelian SET 
                nama_barang_beli = '$nama_barang',
                merk_beli        = '$merk_beli',
                supplier         = '$supplier',
                qty              = '$qty',
                harga            = '$harga',
                alokasi_stok     = '$alokasi',
                driver           = '$driver',
                plat_nomor       = '$plat_nomor',
                keterangan       = '$keterangan'
                WHERE id_pembelian = '$id_pembelian'";
        
        if (!mysqli_query($koneksi, $sql)) {
            throw new Exception("Gagal eksekusi query update.");
        }

        // Jika semua OK, simpan permanen
        mysqli_commit($koneksi);
        header("location:data_pembelian.php?pesan=update_berhasil");

    } catch (Exception $e) {
        // Batalkan perubahan jika error
        mysqli_rollback($koneksi);
        header("location:data_pembelian.php?pesan=gagal_update&log=" . urlencode($e->getMessage()));
    }
} else {
    header("location:data_pembelian.php");
}
?>
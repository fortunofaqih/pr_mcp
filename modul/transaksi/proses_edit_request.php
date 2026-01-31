<?php
session_start();
include '../../config/koneksi.php';

if ($_POST) {
    $id_request   = $_POST['id_request'];
    $no_request   = $_POST['no_request'];
    $tgl_request  = $_POST['tgl_request'];
    $nama_pemesan = strtoupper(mysqli_real_escape_string($koneksi, $_POST['nama_pemesan']));
    $user_login   = $_SESSION['nama'];
    $now          = date('Y-m-d H:i:s');

    // 1. UPDATE HEADER
    $query_h = "UPDATE tr_request SET 
                tgl_request  = '$tgl_request', 
                nama_pemesan = '$nama_pemesan',
                updated_by   = '$user_login',
                updated_at   = '$now' 
                WHERE id_request = '$id_request'";

    if (mysqli_query($koneksi, $query_h)) {
        
        // 2. HAPUS SEMUA DETAIL LAMA
        mysqli_query($koneksi, "DELETE FROM tr_request_detail WHERE id_request = '$id_request'");

        // 3. AMBIL DATA DARI POST
        $nama_barang = $_POST['nama_barang'];
        $kategori    = $_POST['kategori_request'];
        $kwalifikasi = $_POST['kwalifikasi'];
        $id_mobil    = $_POST['id_mobil'];
        $jumlah      = $_POST['jumlah'];
        $satuan      = $_POST['satuan'];
        $harga       = $_POST['harga'];

        // 4. INSERT ULANG DETAIL BARU
        foreach ($nama_barang as $key => $val) {
            if(empty(trim($val))) continue;

            $nama  = strtoupper(mysqli_real_escape_string($koneksi, $val));
            $kat   = mysqli_real_escape_string($koneksi, $kategori[$key]);
            $kwal  = strtoupper(mysqli_real_escape_string($koneksi, $kwalifikasi[$key]));
            $mobil = mysqli_real_escape_string($koneksi, $id_mobil[$key]);
            $qty   = (float)$jumlah[$key];
            $sat   = strtoupper(mysqli_real_escape_string($koneksi, $satuan[$key]));
            $hrg   = (float)$harga[$key];
            
            // PERBAIKAN: Hitung manual untuk disimpan ke MySQL 5.6
            $subtotal = $qty * $hrg;

            // --- CEK APAKAH BARANG ADA DI MASTER ---
            $cek_barang = mysqli_query($koneksi, "SELECT id_barang FROM master_barang WHERE nama_barang = '$nama'");
            $data_b = mysqli_fetch_array($cek_barang);

            if ($data_b) {
                $id_barang = $data_b['id_barang'];
                $nama_manual = ""; 
            } else {
                $id_barang = 0;
                $nama_manual = $nama;
            }

            // PERBAIKAN: Pastikan subtotal_estimasi ikut di-INSERT karena sudah jadi kolom fisik/biasa
            $query_d = "INSERT INTO tr_request_detail 
                        (id_request, nama_barang_manual, id_barang, kategori_barang, kwalifikasi, id_mobil, jumlah, satuan, harga_satuan_estimasi, subtotal_estimasi) 
                        VALUES 
                        ('$id_request', '$nama_manual', '$id_barang', '$kat', '$kwal', '$mobil', '$qty', '$sat', '$hrg', '$subtotal')";
            
            mysqli_query($koneksi, $query_d);
        }

        header("location:pr.php?pesan=update_sukses");
        exit;
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>
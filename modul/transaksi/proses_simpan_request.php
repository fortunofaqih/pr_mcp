<?php
session_start();
include '../../config/koneksi.php';

if ($_SESSION['status'] != "login") {
    header("location:../../login.php?pesan=belum_login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $tgl_form      = $_POST['tgl_request'];
    $tgl_kode      = date('Ymd', strtotime($tgl_form));
    $user_login    = $_SESSION['nama'];
    $nama_pemesan  = strtoupper(mysqli_real_escape_string($koneksi, $_POST['nama_pemesan']));

    // --- LOCK TABLES ---
    mysqli_query($koneksi, "LOCK TABLES tr_request WRITE, tr_request_detail WRITE");

    // Generate No Request
    $query_no = mysqli_query($koneksi, "SELECT MAX(no_request) as max_code FROM tr_request WHERE no_request LIKE 'PR-$tgl_kode%'");
    $data_no  = mysqli_fetch_array($query_no);
    $last_no  = $data_no['max_code'] ?? '';
    $sort_no  = (int) substr($last_no, -3);
    $new_no   = "PR-" . $tgl_kode . "-" . str_pad(($sort_no + 1), 3, "0", STR_PAD_LEFT);

    // --- SIMPAN HEADER ---
    $query_header = "INSERT INTO tr_request (no_request, tgl_request, nama_pemesan, status_request, created_by) 
                     VALUES ('$new_no', '$tgl_form', '$nama_pemesan', 'PENDING', '$user_login')";

    if (mysqli_query($koneksi, $query_header)) {
        $id_header = mysqli_insert_id($koneksi);

        // Ambil data array dari POST dengan pengamanan default
        $nama_barang      = $_POST['nama_barang'];
        $kategori_request = $_POST['kategori_request']; 
        $kwalifikasi      = $_POST['kwalifikasi'];
        $id_mobil         = $_POST['id_mobil'];
        $tipe_request     = $_POST['tipe_request']; 
        $jumlah           = $_POST['jumlah'];
        $satuan           = $_POST['satuan'];
        $harga            = $_POST['harga'];

        // Looping Detail
        foreach ($nama_barang as $key => $val) {
            if(empty(trim($val))) continue; 

            $nama  = strtoupper(mysqli_real_escape_string($koneksi, $val));
            $kat   = strtoupper(mysqli_real_escape_string($koneksi, $kategori_request[$key] ?? ''));
            $kwal  = strtoupper(mysqli_real_escape_string($koneksi, $kwalifikasi[$key] ?? ''));
            $mobil = (int)($id_mobil[$key] ?? 0);
            $tipe  = strtoupper(mysqli_real_escape_string($koneksi, $tipe_request[$key] ?? 'STOK'));
            $qty   = (float)($jumlah[$key] ?? 0);
            $sat   = strtoupper(mysqli_real_escape_string($koneksi, $satuan[$key] ?? ''));
            $hrg   = (float)($harga[$key] ?? 0);
            
            // Perhitungan manual untuk disimpan secara fisik
            $subtotal = $qty * $hrg;

            // Simpan Detail dengan kolom subtotal_estimasi
            $query_detail = "INSERT INTO tr_request_detail 
                            (id_request, nama_barang_manual, id_barang, id_mobil, jumlah, satuan, harga_satuan_estimasi, subtotal_estimasi, kategori_barang, tipe_request, kwalifikasi) 
                            VALUES 
                            ('$id_header', '$nama', 0, '$mobil', '$qty', '$sat', '$hrg', '$subtotal', '$kat', '$tipe', '$kwal')";
            
            mysqli_query($koneksi, $query_detail);
        }

        mysqli_query($koneksi, "UNLOCK TABLES");

        if (isset($_SESSION['role']) && $_SESSION['role'] == 'gang_beli') {
            header("location:../pembelian/index.php?pesan=berhasil&no=$new_no");
        } else {
            header("location:pr.php?pesan=berhasil&no=$new_no");
        }
        exit;

    } else {
        mysqli_query($koneksi, "UNLOCK TABLES");
        header("location:pr.php?pesan=gagal");
        exit;
    }
}
?>
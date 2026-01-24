<?php
include '../../config/koneksi.php';

// Beritahu browser bahwa file ini mengembalikan JSON
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Ambil data pemesan dan mungkin No PR untuk verifikasi
    $q = mysqli_query($koneksi, "SELECT UPPER(nama_pemesan) as nama_pemesan, no_request FROM tr_request WHERE id_request = '$id'");
    
    if (mysqli_num_rows($q) > 0) {
        $d = mysqli_fetch_assoc($q);
        echo json_encode($d);
    } else {
        // Berikan default kosong jika tidak ditemukan
        echo json_encode([
            'nama_pemesan' => '',
            'no_request' => ''
        ]);
    }
} else {
    // Jika ID tidak dikirim
    echo json_encode(['error' => 'ID tidak ditemukan']);
}
?>
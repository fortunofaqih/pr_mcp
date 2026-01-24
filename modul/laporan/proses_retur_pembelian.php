<?php
session_start();
include '../../config/koneksi.php';

if (isset($_GET['id'])) {
    $id     = mysqli_real_escape_string($koneksi, $_GET['id']);
    $alasan = mysqli_real_escape_string($koneksi, $_GET['alasan']);
    $user   = $_SESSION['nama'];
    $now    = date('Y-m-d H:i:s');

    // 1. Ambil detail pembelian & ID Barang dari master (JOIN untuk mendapatkan id_barang)
    $query_beli = mysqli_query($koneksi, "SELECT p.*, m.id_barang 
                                         FROM pembelian p 
                                         LEFT JOIN master_barang m ON p.nama_barang_beli = m.nama_barang 
                                         WHERE p.id_pembelian = '$id'");
    $data = mysqli_fetch_array($query_beli);

    if ($data) {
        $id_barang   = $data['id_barang'];
        $nama_barang = mysqli_real_escape_string($koneksi, $data['nama_barang_beli']);
        $qty_retur   = $data['qty'];
        $alokasi     = $data['alokasi_stok'];
        $no_pr       = $data['no_request'];
        $supplier    = mysqli_real_escape_string($koneksi, $data['supplier']);

        mysqli_begin_transaction($koneksi);

        try {
            // 2. CATAT KE LOG RETUR (Audit Trail Administrasi)
            $sql_log_admin = "INSERT INTO log_retur 
                             (tgl_retur, no_request, nama_barang_retur, qty_retur, supplier, alokasi_sebelumnya, alasan_retur, eksekutor_retur) 
                             VALUES 
                             ('$now', '$no_pr', '$nama_barang', '$qty_retur', '$supplier', '$alokasi', '$alasan', '$user')";
            mysqli_query($koneksi, $sql_log_admin);

            // 3. JIKA MASUK STOK, UPDATE MASTER DAN CATAT MUTASI DI KARTU STOK
            if ($alokasi == 'MASUK STOK' && !empty($id_barang)) {
                
                // A. Update stok_akhir di master_barang
                $sql_update_master = "UPDATE master_barang SET stok_akhir = stok_akhir - $qty_retur WHERE id_barang = '$id_barang'";
                mysqli_query($koneksi, $sql_update_master);

                // B. Tambahkan baris di tr_stok_log (AGAR MUNCUL DI KARTU STOK)
                $ket_log = "RETUR KE TOKO ($supplier) - PR: $no_pr - ALASAN: $alasan";
                $sql_stok_log = "INSERT INTO tr_stok_log 
                                (id_barang, tgl_log, tipe_transaksi, qty, keterangan, user_input) 
                                VALUES 
                                ('$id_barang', '$now', 'KELUAR', '$qty_retur', '$ket_log', '$user')";
                
                if (!mysqli_query($koneksi, $sql_stok_log)) {
                    throw new Exception("Gagal mencatat mutasi ke kartu stok.");
                }
            }

            // 4. HAPUS DATA PEMBELIAN (Agar tidak double di laporan realisasi)
            $sql_delete = "DELETE FROM pembelian WHERE id_pembelian = '$id'";
            if (!mysqli_query($koneksi, $sql_delete)) {
                throw new Exception("Gagal menghapus data pembelian.");
            }

            mysqli_commit($koneksi);
            header("location:data_pembelian.php?pesan=retur_sukses");

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo "<script>alert('Error: " . $e->getMessage() . "'); window.location='data_pembelian.php';</script>";
        }
    } else {
        echo "<script>alert('Data tidak ditemukan!'); window.location='data_pembelian.php';</script>";
    }
}
?>
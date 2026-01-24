<?php
session_start();
include '../../config/koneksi.php';

// Proteksi Login
if ($_SESSION['status'] != "login") {
    header("location:../../login.php");
    exit;
}

if ($_POST) {
    // 1. TANGKAP DATA HEADER
    $id_req_raw   = !empty($_POST['id_request']) ? mysqli_real_escape_string($koneksi, $_POST['id_request']) : "";
    $id_request   = ($id_req_raw != "") ? "'$id_req_raw'" : "NULL"; 

    $tgl_beli     = mysqli_real_escape_string($koneksi, $_POST['tgl_beli']);
    $nama_pemesan = mysqli_real_escape_string($koneksi, strtoupper($_POST['nama_pemesan']));
    $driver       = mysqli_real_escape_string($koneksi, strtoupper($_POST['driver'] ?? ''));
    
    // --- PERBAIKAN LOGIKA PLAT NOMOR ---
    // Cek apakah ada input plat_nomor manual dari form
    $plat_nomor = mysqli_real_escape_string($koneksi, strtoupper($_POST['plat_nomor'] ?? ''));

    // Jika plat_nomor kosong DAN ini berasal dari Request (PR), ambil otomatis dari database
    if (empty($plat_nomor) && $id_req_raw != "") {
        $q_cari_mobil = mysqli_query($koneksi, "
            SELECT m.plat_nomor 
            FROM tr_request_detail rd
            JOIN master_mobil m ON rd.id_mobil = m.id_mobil
            WHERE rd.id_request = '$id_req_raw' AND rd.id_mobil != 0
            LIMIT 1
        ");
        $data_mobil = mysqli_fetch_assoc($q_cari_mobil);
        if ($data_mobil) {
            $plat_nomor = $data_mobil['plat_nomor'];
        }
    }
    
    $id_user_beli = $_SESSION['id_user'] ?? 0; 
    $nama_user    = $_SESSION['nama'] ?? 'SYSTEM';

    // 2. AMBIL NOMOR PR (Untuk referensi)
    $no_request_ref = "";
    if ($id_req_raw != "") {
        $q_ref = mysqli_query($koneksi, "SELECT no_request FROM tr_request WHERE id_request = '$id_req_raw'");
        $d_ref = mysqli_fetch_assoc($q_ref);
        $no_request_ref = $d_ref['no_request'] ?? "";
    }

    // 3. TANGKAP DATA DETAIL (ARRAY)
    $nama_barang_arr  = $_POST['nama_barang'] ?? [];
    $supplier_arr     = $_POST['supplier'] ?? [];
    $qty_arr          = $_POST['qty'] ?? [];
    $harga_satuan_arr = $_POST['harga_satuan'] ?? [];
    $kategori_arr     = $_POST['kategori_beli'] ?? [];
    $alokasi_arr      = $_POST['alokasi_stok'] ?? [];
    $ket_input_arr    = $_POST['keterangan'] ?? []; 

    $berhasil_simpan = 0;

    // MULAI TRANSAKSI DATABASE
    mysqli_begin_transaction($koneksi);

    try {
        // 4. LOOPING PROSES TIAP BARANG
        foreach ($nama_barang_arr as $key => $val) {
            if (empty(trim($val))) continue; 

            $nama_barang  = mysqli_real_escape_string($koneksi, strtoupper($val));
            $supplier     = mysqli_real_escape_string($koneksi, strtoupper($supplier_arr[$key] ?? ''));
            $qty          = (float)($qty_arr[$key] ?? 0);
            $harga_satuan = (float)($harga_satuan_arr[$key] ?? 0);
            $kategori     = mysqli_real_escape_string($koneksi, $kategori_arr[$key] ?? '');
            $alokasi      = mysqli_real_escape_string($koneksi, $alokasi_arr[$key] ?? 'LANGSUNG PAKAI');
            $catatan_item = isset($ket_input_arr[$key]) ? mysqli_real_escape_string($koneksi, strtoupper($ket_input_arr[$key])) : "";

            // A. SIMPAN KE TABEL PEMBELIAN
            $q_beli = "INSERT INTO pembelian 
                       (id_request, no_request, tgl_beli, supplier, nama_barang_beli, qty, harga, kategori_beli, alokasi_stok, nama_pemesan, driver, plat_nomor, keterangan, id_user_beli) 
                       VALUES 
                       ($id_request, '$no_request_ref', '$tgl_beli', '$supplier', '$nama_barang', '$qty', '$harga_satuan', '$kategori', '$alokasi', '$nama_pemesan', '$driver', '$plat_nomor', '$catatan_item', '$id_user_beli')";
            
            if (!mysqli_query($koneksi, $q_beli)) {
                throw new Exception("Gagal simpan barang: $nama_barang. Error: " . mysqli_error($koneksi));
            }
            
            $berhasil_simpan++;

            // B. LOGIKA STOK (Jika MASUK STOK)
            if ($alokasi == "MASUK STOK") {
                $cek_master = mysqli_query($koneksi, "SELECT id_barang FROM master_barang WHERE nama_barang = '$nama_barang' LIMIT 1");
                
                if (mysqli_num_rows($cek_master) > 0) {
                    $d_mb = mysqli_fetch_array($cek_master);
                    $id_barang_fix = $d_mb['id_barang'];
                    mysqli_query($koneksi, "UPDATE master_barang SET stok_akhir = stok_akhir + $qty WHERE id_barang = '$id_barang_fix'");
                } else {
                    $q_ins_m = "INSERT INTO master_barang (nama_barang, kategori, stok_akhir, status_aktif, created_by) 
                                VALUES ('$nama_barang', '$kategori', '$qty', 'AKTIF', '$nama_user')";
                    mysqli_query($koneksi, $q_ins_m);
                    $id_barang_fix = mysqli_insert_id($koneksi);
                }

                // C. CATAT LOG STOK
                $tgl_jam_log = $tgl_beli . " " . date('H:i:s');
                $keterangan_log = "BELI: $supplier" . ($no_request_ref ? " (PR: $no_request_ref)" : "");
                if(!empty($catatan_item)) $keterangan_log .= " - $catatan_item";

                $q_log = "INSERT INTO tr_stok_log (id_barang, tgl_log, tipe_transaksi, qty, keterangan) 
                          VALUES ('$id_barang_fix', '$tgl_jam_log', 'MASUK', '$qty', '$keterangan_log')";
                mysqli_query($koneksi, $q_log);
            }
        }

        // 5. UPDATE STATUS PR JADI SELESAI
        if ($berhasil_simpan > 0 && $id_req_raw != "") {
            // Pastikan nama tabel benar: tr_request (sesuai SQL dump Anda)
            mysqli_query($koneksi, "UPDATE tr_request SET status_request = 'SELESAI' WHERE id_request = '$id_req_raw'");
        }

        mysqli_commit($koneksi);

        echo "<script>
                alert('Berhasil menyimpan $berhasil_simpan item pembelian.'); 
                window.location='index.php';
              </script>";

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $error_msg = $e->getMessage();
        echo "<script>
                alert('Error: $error_msg'); 
                window.location='index.php';
              </script>";
    }
} else {
    header("location:index.php");
}
?>
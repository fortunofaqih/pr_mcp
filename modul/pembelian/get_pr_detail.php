<?php
include '../../config/koneksi.php';
$id = mysqli_real_escape_string($koneksi, $_GET['id']);

// TAMBAHKAN FILTER: status_item != 'REJECTED'
// Ini agar barang yang tidak dicentang pimpinan tidak muncul di form belanja
$q = mysqli_query($koneksi, "SELECT * FROM tr_request_detail 
                             WHERE id_request = '$id' 
                             AND status_item != 'REJECTED'"); 

if(mysqli_num_rows($q) == 0){
    echo '<tr><td colspan="8" class="text-center text-danger py-3">Tidak ada item yang disetujui untuk dibeli.</td></tr>';
    exit;
}

while($d = mysqli_fetch_array($q)){
    $kat_db = strtoupper($d['kategori_barang']); 
    // Sesuaikan tipe request (STOK atau LANGSUNG)
    $tipe_pr = strtoupper($d['tipe_request'] ?? ''); 
    $subtotal = $d['jumlah'] * $d['harga_satuan_estimasi'];

    echo '<tr class="baris-beli">
        <td>
            <input type="text" name="nama_barang[]" class="form-control form-control-sm bg-light fw-bold" value="'.strtoupper($d['nama_barang_manual']).'" readonly>
            <small class="text-muted">Spec: '.$d['kwalifikasi'].'</small>
        </td>
        
        <td>
            <input type="text" name="supplier[]" class="form-control form-control-sm" placeholder="NAMA TOKO" required>
        </td>

        <td>
            <input type="number" name="qty[]" class="form-control form-control-sm b-qty text-center" step="0.01" value="'.(float)$d['jumlah'].'" required>
            <input type="hidden" name="satuan[]" value="'.$d['satuan'].'">
        </td>

        <td>
            <input type="number" name="harga_satuan[]" class="form-control form-control-sm b-harga text-end" value="'.$d['harga_satuan_estimasi'].'" required>
        </td>

        <td>
            <select name="kategori_beli[]" class="form-select form-select-sm" required>
                <option value="">-- PILIH --</option>
                <optgroup label="BENGKEL">
                    <option value="BENGKEL MOBIL" '.($kat_db == "BENGKEL MOBIL" ? "selected" : "").'>MOBIL</option>
                    <option value="BENGKEL LISTRIK" '.($kat_db == "BENGKEL LISTRIK" ? "selected" : "").'>LISTRIK</option>
                    <option value="BENGKEL DINAMO" '.($kat_db == "BENGKEL DINAMO" ? "selected" : "").'>DINAMO</option>
                    <option value="BENGKEL BUBUT" '.($kat_db == "BENGKEL BUBUT" ? "selected" : "").'>BUBUT</option>
                </optgroup>
                <optgroup label="UMUM">
                    <option value="KANTOR" '.($kat_db == "KANTOR" ? "selected" : "").'>KANTOR</option>
                    <option value="BANGUNAN" '.($kat_db == "BANGUNAN" ? "selected" : "").'>BANGUNAN</option>
                    <option value="LAS" '.($kat_db == "LAS" ? "selected" : "").'>LAS</option>
                    <option value="MESIN" '.($kat_db == "MESIN" ? "selected" : "").'>MESIN</option>
                    <option value="LAIN-LAIN" '.($kat_db == "LAIN-LAIN" ? "selected" : "").'>LAIN-LAIN</option>
                </optgroup>
            </select>
        </td>

        <td>
            <select name="alokasi_stok[]" class="form-select form-select-sm" required>
                <option value="LANGSUNG PAKAI" '.($tipe_pr == "LANGSUNG" ? "selected" : "").'>LANGSUNG PAKAI</option>
                <option value="MASUK STOK" '.($tipe_pr == "STOK" ? "selected" : "").'>MASUK STOK</option>
            </select>
        </td>

        <td>
            <input type="text" class="form-control form-control-sm b-total bg-light fw-bold text-end" value="'.number_format($subtotal, 0, ',', '.').'" readonly>
        </td>

        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger remove-baris border-0"><i class="fas fa-times"></i></button>
        </td>
    </tr>';
}
?>
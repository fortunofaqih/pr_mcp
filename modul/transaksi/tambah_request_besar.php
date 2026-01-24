<?php
session_start();
include '../../config/koneksi.php';

if ($_SESSION['status'] != "login") {
    header("location:../../login.php?pesan=belum_login");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Request Baru - MCP System</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --mcp-blue: #0000FF; }
        body { background-color: #f4f7f6; font-size: 0.85rem; }
        .card-header { background: white; border-bottom: 2px solid #eee; }
        .table-input thead { background: var(--mcp-blue); color: white; font-size: 0.75rem; text-transform: uppercase; }
        .form-control-sm, .form-select-sm { border-radius: 4px; }
        input, select, textarea { text-transform: uppercase; }
        .bg-autonumber { background-color: #e9ecef; border-style: dashed; color: #00008B; font-weight: bold; }
        .table-input { min-width: 1300px; }
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 31px !important;
            font-size: 0.8rem !important;
            padding: 2px 5px !important;
        }
        .item-row:hover { background-color: #f8f9ff; }
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>

<body class="py-4">
<div class="container-fluid">
    <form action="proses_simpan_besar.php" method="POST">
        
        <div class="row justify-content-center">
            <div class="col-md-11">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header py-3">
                        <h5 class="fw-bold m-0 text-danger"><i class="fas fa-boxes-stacked me-2"></i> FORM REQUEST BARANG BESAR</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">NOMOR REQUEST</label>
                                <input type="text" class="form-control bg-autonumber" value="[ GENERATE OTOMATIS ]" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">TANGGAL REQUEST</label>
                                <input type="date" name="tgl_request" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted">NAMA PEMESAN / BAGIAN</label>
                                <input type="text" name="nama_pemesan" class="form-control" placeholder="Contoh: IBU IKA / GUDANG" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="small fw-bold text-primary">ALASAN / KEPERLUAN PEMBELIAN</label>
                                <textarea name="keterangan" class="form-control" rows="2" placeholder="JELASKAN MENGAPA BARANG INI DIBUTUHKAN (CONTOH: PERBAIKAN MESIN PRODUKSI DLL)..." required></textarea>
                            </div>
                        </div>

                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered table-input align-middle" id="tableItem">
                                <thead>
                                    <tr class="text-center">
                                        <th width="18%">Nama Barang</th>
                                        <th width="12%">Kategori</th>
                                        <th width="15%">Kwalifikasi (Merk/Spek)</th>
                                        <th width="12%">Unit/Mobil</th>
                                        <th width="8%">Tipe</th>
                                        <th width="6%">Qty</th>
                                        <th width="8%">Satuan</th>
                                        <th width="10%">Harga (Est)</th>
                                        <th width="11%">Total (Rp)</th>
                                        <th width="3%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="item-row">
                                        <td>
                                            <select name="nama_barang[]" class="form-select form-select-sm select-barang" required>
                                                <option value="">-- PILIH BARANG --</option>
                                                <?php
                                                $brg = mysqli_query($koneksi, "SELECT * FROM master_barang ORDER BY nama_barang ASC");
                                                while($b = mysqli_fetch_array($brg)){
                                                    // Menambahkan data-kategori untuk otomatisasi
                                                    echo "<option value='".$b['nama_barang']."' 
                                                            data-satuan='".$b['satuan']."' 
                                                            data-merk='".$b['merk']."' 
                                                            data-kategori='".strtoupper($b['kategori'])."'
                                                            data-harga='".$b['harga_beli']."'>".$b['nama_barang']."</option>";
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="kategori_request[]" class="form-select form-select-sm select-kategori" required>
                                                <option value="">- PILIH -</option>
                                                <optgroup label="BENGKEL">
                                                    <option value="BENGKEL MOBIL">BENGKEL MOBIL</option>
                                                    <option value="BENGKEL LISTRIK">BENGKEL LISTRIK</option>
                                                    <option value="BENGKEL DINAMO">BENGKEL DINAMO</option>
                                                    <option value="BENGKEL BUBUT">BENGKEL BUBUT</option>
                                                </optgroup>
                                                <optgroup label="UMUM">
                                                    <option value="KANTOR">KANTOR</option>
                                                    <option value="BANGUNAN">BANGUNAN</option>
                                                    <option value="LAIN-LAIN">LAIN-LAIN</option>
                                                </optgroup>
                                            </select>
                                        </td>
                                        <td><input type="text" name="kwalifikasi[]" class="form-control form-control-sm input-kwalifikasi" placeholder="Merk / Spek tambahan..."></td>
                                        <td>
                                            <select name="id_mobil[]" class="form-select form-select-sm select-mobil">
                                                <option value="0">NON MOBIL</option>
                                                <?php
                                                $mbl = mysqli_query($koneksi, "SELECT id_mobil, plat_nomor FROM master_mobil WHERE status_aktif='AKTIF' ORDER BY plat_nomor ASC");
                                                while($m = mysqli_fetch_array($mbl)){
                                                    echo "<option value='".$m['id_mobil']."'>".$m['plat_nomor']."</option>";
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="tipe_request[]" class="form-select form-select-sm select-tipe">
                                                <option value="STOK">STOK</option>
                                                <option value="LANGSUNG" selected>LANGSUNG</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="jumlah[]" class="form-control form-control-sm input-qty text-center" step="0.01" value="1" required></td>
                                        <td>
                                            <select name="satuan[]" class="form-select form-select-sm select-satuan" required>
                                                <option value="">- PILIH -</option>
                                                <option value="PCS">PCS</option>
                                                <option value="DUS">DUS</option>
                                                <option value="KG">KG</option>
                                                <option value="LITER">LITER</option>
                                                <option value="METER">METER</option>
                                                <option value="CM">CM</option>
                                                <option value="LONJOR">LONJOR</option>
                                                <option value="SET">SET</option>
                                                <option value="ROLL">ROLL</option>
                                                <option value="PAX">PAX</option>
                                                <option value="UNIT">UNIT</option>
                                                <option value="DRUM">DRUM</option>
                                                <option value="SAK">SAK</option>
                                                <option value="PAIL">PAIL</option>
                                                <option value="CAN">CAN</option>
                                                <option value="BOTOL">BOTOL</option>
                                                <option value="TUBE">TUBE</option>
                                                <option value="GALON">GALON</option>
                                                <option value="IKAT">IKAT</option>
                                                <option value="LEMBAR">LEMBAR</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="harga[]" class="form-control form-control-sm input-harga text-end" placeholder="0"></td>
                                        <td><input type="text" class="form-control form-control-sm input-subtotal text-end bg-light" value="0" readonly></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-row border-0"><i class="fas fa-times"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <button type="button" id="addRow" class="btn btn-sm btn-success fw-bold px-3">
                            <i class="fas fa-plus me-1"></i> Tambah Baris Barang
                        </button>

                        <div class="row mt-3 justify-content-end">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-primary text-white fw-bold">TOTAL ESTIMASI</span>
                                    <input type="text" id="grandTotal" class="form-control form-control-lg text-end fw-bold bg-white" value="Rp 0" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white py-3">
                        <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm">
                            <i class="fas fa-save me-1"></i> SIMPAN SEMUA PERMINTAAN
                        </button>
                        <a href="pr.php" class="btn fw-bold px-5 btn-danger"><i class="fas fa-rotate-left"></i> BATAL</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function(){
    
    function initSelect2() {
        $('.select-barang').select2({
            theme: 'bootstrap-5',
            width: '100%',
            tags: false, 
            placeholder: "-- PILIH BARANG --",
            allowClear: true
        });

        $('.select-kategori, .select-mobil, .select-tipe, .select-satuan').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    initSelect2();

    function hitungSubtotal(row) {
        var qty = parseFloat(row.find('.input-qty').val()) || 0;
        var harga = parseFloat(row.find('.input-harga').val()) || 0;
        var subtotal = qty * harga;
        row.find('.input-subtotal').val(subtotal.toLocaleString('id-ID'));
        hitungGrandTotal();
    }

    function hitungGrandTotal() {
        var grandTotal = 0;
        $('.input-subtotal').each(function() {
            var sub = parseFloat($(this).val().replace(/\./g, '').replace(/,/g, '.')) || 0;
            grandTotal += sub;
        });
        $('#grandTotal').val("Rp " + grandTotal.toLocaleString('id-ID'));
    }

    $(document).on('change', '.select-barang', function(){
        var row = $(this).closest('tr');
        var selected = $(this).find(':selected');
        
        var merk = selected.attr('data-merk') || "";
        var satuan = selected.attr('data-satuan') || "";
        var harga = selected.attr('data-harga') || 0;
        var kategori = selected.attr('data-kategori') || "";
        
        row.find('.input-kwalifikasi').val(merk); 
        
        // Auto select kategori (FOKUS UTAMA)
        if(kategori != "") {
            row.find('.select-kategori').val(kategori).trigger('change.select2');
        }

        // Auto select satuan jika ada di master barang
        if(satuan != "") {
            row.find('.select-satuan').val(satuan.toUpperCase()).trigger('change.select2');
        }

        row.find('.input-harga').val(harga);
        hitungSubtotal(row);
    });

    $(document).on('input', '.input-qty, .input-harga', function(){
        hitungSubtotal($(this).closest('tr'));
    });

    $(document).on('change', '.select-mobil', function(){
        var row = $(this).closest('tr');
        if($(this).val() != "0"){
            row.find('.select-kategori').val("BENGKEL MOBIL").trigger('change.select2');
            row.find('.select-tipe').val("LANGSUNG").trigger('change.select2');
        }
    });

    $("#addRow").click(function(){
        $('.select-barang, .select-kategori, .select-mobil, .select-tipe, .select-satuan').select2('destroy');
        
        var newRow = $('.item-row:last').clone(); 
        newRow.find('input').val('');
        newRow.find('.input-qty').val('1');
        newRow.find('.input-subtotal').val('0');
        newRow.find('select').val('');
        newRow.find('.select-mobil').val('0');
        newRow.find('.select-tipe').val('LANGSUNG'); 
        
        $("#tableItem tbody").append(newRow);
        initSelect2();
        hitungGrandTotal();
    });

    $(document).on('click', '.remove-row', function(){
        if($("#tableItem tbody tr").length > 1){
            $(this).closest('tr').remove();
            hitungGrandTotal();
        }
    });
});
</script>
<script>
$(document).ready(function() {
    // Notifikasi Respon
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('pesan') === 'berhasil') {
        Swal.fire('Tersimpan!', 'Request barang besar telah berhasil dibuat.', 'success');
    }

    // Konfirmasi Submit Khusus Barang Besar
    $('form').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const total = $('#grandTotal').val();

        Swal.fire({
            title: 'Konfirmasi Request Besar',
            html: `Sistem mendeteksi pengajuan barang besar/investasi.<br>Total Estimasi: <b>${total}</b>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // Merah untuk barang besar
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Ajukan Sekarang',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
</body>
</html>
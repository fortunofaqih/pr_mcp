<?php
session_start();
include '../../config/koneksi.php';

$id = mysqli_real_escape_string($koneksi, $_GET['id']);
$query_header = mysqli_query($koneksi, "SELECT * FROM tr_request WHERE id_request = '$id'");
$h = mysqli_fetch_array($query_header);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak PR - <?= $h['no_request'] ?></title>
     <link rel="icon" type="image/png" href="<?php echo $base_url; ?>assets/img/logo_mcp.png">
    <style>
        /* 1. Paksa Ukuran Kertas */
        @page { 
            size: A5 landscape; 
            margin: 0; /* Margin diatur di level body agar lebih stabil */
        }

        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            font-size: 11px; 
            margin: 0; 
            padding: 10mm; /* Jarak aman printer */
            background: #fff;
            /* 2. Paksa Warna Muncul saat Print */
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .header { 
            text-align: center; 
            margin-bottom: 10px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 5px; 
        }
        .header h2 { margin: 0; padding: 0; font-size: 18px; letter-spacing: 2px; color: #000; }
        .header h4 { margin: 2px 0; padding: 0; font-size: 12px; font-weight: normal; }

        .info-table { width: 100%; margin-bottom: 10px; font-weight: bold; font-size: 11px; }
        
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data th, table.data td { border: 1px solid #000; padding: 5px 6px; }
        
        /* 3. Perbaikan Header Tabel agar warna tidak hilang */
        table.data th { 
            background-color: #e9ecef !important; 
            text-transform: uppercase; 
            font-size: 10px; 
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        .footer-sign { 
            width: 100%; 
            margin-top: 15px; 
            border-collapse: collapse; 
        }
        .footer-sign td { text-align: center; width: 33%; font-size: 11px; vertical-align: bottom; }
        .space-sign { height: 40px; } 

        /* 4. Kontrol Tampilan Layar vs Cetak */
        @media print {
            .no-print { display: none !important; }
            body { padding: 10mm; }
        }
        
        /* Gaya tombol simulasi di layar */
        .btn-print-preview {
            background: #007bff;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body onload="window.print()"> 
    
    <div class="no-print" style="background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-bottom: 1px solid #ddd;">
        <button onclick="window.print()" class="btn-print-preview">🖨️ CETAK SEKARANG (A5)</button>
        <!--<a href="permintaan_barang.php" style="margin-left:10px; color:#666; font-size:12px;">Kembali</a>-->
        <div style="margin-top: 5px;"><small>Tips: Jika warna tabel tidak muncul, aktifkan <b>"Background Graphics"</b> di pengaturan print browser.</small></div>
    </div>

    <div class="header">
        <h2>PURCHASE REQUEST FORM</h2>
        <h4>PT. Mutiara Cahaya Plastindo</h4>
    </div>

    <table class="info-table">
        <tr>
            <td width="35%">NO: <span style="font-size: 13px;"><?= $h['no_request'] ?></span></td>
            <td width="30%" class="text-center">PEMESAN: <?= strtoupper($h['nama_pemesan']) ?></td>
            <td width="35%" class="text-right">TGL: <?= date('d/m/Y', strtotime($h['tgl_request'])) ?></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th width="40px">NO</th>
                <th>NAMA BARANG</th>
                <th width="120px">KWALIFIKASI</th>
                <th width="90px">UNIT/MOBIL</th>
                <th width="70px">QTY</th>
                <th width="90px">HARGA</th>
                <th width="110px">SUBTOTAL</th>
                <th width="30px">V</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $grand_total = 0;
            $sql_detail = "SELECT d.*, m.plat_nomor 
                           FROM tr_request_detail d
                           LEFT JOIN master_mobil m ON d.id_mobil = m.id_mobil
                           WHERE d.id_request = '$id' 
                           ORDER BY d.id_detail ASC";
            $query_detail = mysqli_query($koneksi, $sql_detail);

            while($d = mysqli_fetch_array($query_detail)) {
                $subtotal = $d['jumlah'] * $d['harga_satuan_estimasi'];
                $grand_total += $subtotal;
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><span class="text-bold"><?= strtoupper($d['nama_barang_manual']) ?></span></td>
                <td><small><?= $d['kwalifikasi'] ?></small></td>
                <td class="text-center"><?= ($d['id_mobil'] != 0) ? $d['plat_nomor'] : '-' ?></td>
                <td class="text-center text-bold"><?= (float)$d['jumlah'] ?> <?= $d['satuan'] ?></td>
                <td class="text-right"><?= number_format($d['harga_satuan_estimasi'], 0, ',', '.') ?></td>
                <td class="text-right text-bold"><?= number_format($subtotal, 0, ',', '.') ?></td>
                <td class="text-center">
                    <div style="width:12px; height:12px; border:1px solid #000; margin:auto;"></div>
                </td>
            </tr>
            <?php } ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right text-bold">ESTIMASI GRAND TOTAL</td>
                <td class="text-right text-bold" style="background: #f2f2f2 !important; font-size: 13px;">
                    Rp <?= number_format($grand_total, 0, ',', '.') ?>
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <table class="footer-sign">
        <tr>
            <td>
                Pemesan,<br>
                <div class="space-sign"></div>
                <b>( ________________ )</b>
            </td>
            <td>
                Pembeli,<br>
                <div class="space-sign"></div>
                <b>( ________________ )</b>
            </td>
            <td>
                Mengetahui,<br>
                <div class="space-sign"></div>
                <b>( ________________ )</b>
            </td>
        </tr>
    </table>

</body>
</html>
# Custom JIBAS - Penambahan WhatsApp Gateway

Modul ada di folder whatsappgateway

Modul ini menambahkan mekanisme notifikasi WhatsApp ke JIBAS tanpa mengubah kode inti. Mekanisme bekerja dengan menyalin SMS yang sudah dihasilkan ke tabel `jbssms.outboxhistory`, memindahkannya ke antrian WhatsApp (`jbswa.wa_queue`), kemudian mengirimnya melalui Wapisender API.

## Arsitektur
- **queue_sync.php**: membaca entri baru `jbssms.outboxhistory` dan memasukkannya ke `jbswa.wa_queue`.
- **wa_dispatch.php**: mengambil antrian pending dan memanggil Wapisender `message/text` untuk tiap tujuan.
- **jbswa.wa_queue**: menyimpan status pengiriman WhatsApp (pending, sukses, retry, gagal).
- **jbswa.wa_sync_marker**: penanda ID `outboxhistory` terakhir yang sudah diproses.

## Instalasi
1. Jalankan skrip SQL `install/create_schema.sql` menggunakan akun MySQL JIBAS. 
Atau bisa jalankan dengan cara
```
cd C:\xampp\mysql\bin
mysql -uroot < C:\xampp\htdocs\jibas\whatsappgateway\install\create_schema.sql
```
2. Salin `include/wapi.config.php` lalu isi `WAPI_API_KEY` dan `WAPI_DEVICE_KEY` sesuai kredensial Wapisender.
3. Pastikan ekstensi PHP `curl` aktif (bawaan XAMPP).

## Penjadwalan
- Tambahkan penjadwalan (Task Scheduler / cron) untuk menjalankan:
  - `php whatsappgateway/script/queue_sync.php`
  - `php whatsappgateway/script/wa_dispatch.php`
- Jalankan keduanya setiap 1 menit atau sesuai kebutuhan.

## Status Antrian
- `status = 0`: pending (belum pernah dikirim).
- `status = 1`: sukses terkirim ke Wapisender.
- `status = 2`: gagal sementara, akan dicoba ulang sampai batas `WAPI_MAX_RETRY`.
- `status = 3`: gagal permanen (butuh tindakan manual).

## Kustomisasi
- Penamaan pengirim dan batas retry dapat diatur di `include/wapi.config.php`.
- Jika ingin memfilter jenis SMS tertentu, ubah logika pada `script/queue_sync.php`.

## Webhook (opsional)
Wapisender mendukung webhook penerimaan pesan. Tambahkan handler baru bila dibutuhkan tanpa menyentuh kode inti JIBAS.

## Versi JIBAS
versi 32.0 - 05 Februari 2025
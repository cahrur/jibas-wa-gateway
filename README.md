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
- Untuk jalankan manual
```
& 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\jibas\whatsappgateway\script\queue_sync.php'
& 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\jibas\whatsappgateway\script\wa_dispatch.php'
```
- Untuk jalankan di windows
1. Buka Task Scheduler → Create Task
2. Tab General: beri nama misal JIBAS Queue Sync, centang “Run whether user is logged on or not”.
3. Tab Triggers: klik New…, “Begin the task” = On a schedule, lalu pada “Advanced settings” centang “Repeat task every” = 1 minute, durasi = Indefinitely. ( jika 1 menit tidak ada ketik manual)
4. Tab Actions: klik New…, “Start a program”.
- Program/script: C:\xampp\php\php.exe
- Add arguments: C:\xampp\htdocs\jibas\whatsappgateway\script\queue_sync.php
5. Settings: centang “Run task as soon as possible after a scheduled start is missed”.
6. Ulangi langkah yang sama untuk membuat tugas kedua bernama JIBAS WA Dispatch dengan argumen C:\xampp\htdocs\jibas\whatsappgateway\script\wa_dispatch.php. Sesudah itu kedua skrip akan jalan otomatis setiap menit.


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

## Dukung Pengembangan
Jika modul ini bermanfaat, dukung saya melalui Saweria: https://saweria.co/cahrur

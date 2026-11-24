<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * View WhatsApp Bot & Fonnte Gateway Integration
 * SMP Negeri Satu Atap 2 Cipatujah (SADUCI CBT)
 *
 * @var string $judul
 * @var string $subjudul
 * @var object $setting
 * @var object $wa_setting
 * @var string $webhook_url
 * @var object $tp_active
 * @var object $smt_active
 * @var array $jadwals
 * @var int $total_unattended
 * @var array $unattended_reports
 * @var array $logs
 * @var object $user
 * @var object|null $profile
 * @var object|null $guru
 */
$is_connected = !empty($wa_setting->api_token);
$has_group = !empty($wa_setting->target_group);
?>

<div class="content-wrapper bg-light">
    <!-- Header Content -->
    <section class="content-header pb-2">
        <div class="container-fluid">
            <div class="row align-items-center mb-2">
                <div class="col-sm-6">
                    <h1 class="font-weight-bold text-dark m-0" style="font-size: 1.6rem;">
                        <i class="fab fa-whatsapp text-success mr-2"></i><?= $judul ?>
                    </h1>
                    <small class="text-muted"><?= $subjudul ?></small>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>"><i class="fas fa-home mr-1"></i>Beranda</a></li>
                        <li class="breadcrumb-item">CBT</li>
                        <li class="breadcrumb-item active">WhatsApp Bot</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="content">
        <div class="container-fluid">

            <!-- ===================================================================
                 WIDGET 1: STATUS SUMMARY CARDS
                 =================================================================== -->
            <div class="row mb-3">
                <!-- Status Koneksi Fonnte -->
                <div class="col-6 col-md-3 mb-2">
                    <div class="card bg-white border my-shadow mb-0 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-xs font-weight-bold text-muted text-uppercase">Status Gateway</span>
                            <span class="badge <?= $is_connected ? 'badge-success' : 'badge-warning' ?>">
                                <?= $is_connected ? 'Aktif' : 'Belum Setup' ?>
                            </span>
                        </div>
                        <h5 class="font-weight-bold <?= $is_connected ? 'text-success' : 'text-warning' ?> m-0 mb-1">
                            <i class="<?= $is_connected ? 'fas fa-check-circle' : 'fas fa-exclamation-circle' ?> mr-1"></i>
                            <?= $is_connected ? 'Fonnte Siap' : 'Perlu Token' ?>
                        </h5>
                        <small class="text-muted text-xs">Layanan WhatsApp API Fonnte</small>
                    </div>
                </div>

                <!-- Target Grup WhatsApp -->
                <div class="col-6 col-md-3 mb-2">
                    <div class="card bg-white border my-shadow mb-0 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-xs font-weight-bold text-muted text-uppercase">Target Grup WA</span>
                            <i class="fas fa-users text-primary"></i>
                        </div>
                        <h6 class="font-weight-bold text-dark m-0 mb-1 text-truncate" title="<?= $wa_setting->target_group ?>">
                            <?= $has_group ? $wa_setting->target_group : 'Belum Diatur' ?>
                        </h6>
                        <small class="text-muted text-xs">Tujuan broadcast laporan guru/panitia</small>
                    </div>
                </div>

                <!-- Siswa Belum Ujian Hari Ini -->
                <div class="col-6 col-md-3 mb-2">
                    <div class="card bg-white border border-danger my-shadow mb-0 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-xs font-weight-bold text-danger text-uppercase">Belum Ujian Hari Ini</span>
                            <span class="pulse-danger"></span>
                        </div>
                        <h4 class="font-weight-bold text-danger m-0 mb-1"><?= $total_unattended ?> <small class="text-sm font-weight-normal text-muted">Siswa</small></h4>
                        <small class="text-muted text-xs">Dari jadwal aktif hari ini</small>
                    </div>
                </div>

                <!-- Log Terkirim -->
                <div class="col-6 col-md-3 mb-2">
                    <div class="card bg-white border my-shadow mb-0 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-xs font-weight-bold text-muted text-uppercase">Total Log Terkirim</span>
                            <i class="fas fa-paper-plane text-info"></i>
                        </div>
                        <h4 class="font-weight-bold text-dark m-0 mb-1"><?= count($logs) ?> <small class="text-sm font-weight-normal text-muted">Aktivitas</small></h4>
                        <small class="text-muted text-xs">Riwayat pesan & webhook</small>
                    </div>
                </div>
            </div>

            <!-- ===================================================================
                 TABS UTAMA
                 =================================================================== -->
            <div class="card card-primary card-outline card-outline-tabs my-shadow mb-5">
                <div class="card-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs" id="wa-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active font-weight-bold" id="tab-broadcast-tab" data-toggle="pill" href="#tab-broadcast" role="tab">
                                <i class="fas fa-bullhorn mr-1 text-primary"></i>Kirim Laporan ke Grup WA
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="tab-setting-tab" data-toggle="pill" href="#tab-setting" role="tab">
                                <i class="fas fa-cogs mr-1 text-success"></i>Pengaturan Fonnte & Webhook
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="tab-guide-tab" data-toggle="pill" href="#tab-guide" role="tab">
                                <i class="fas fa-robot mr-1 text-info"></i>Daftar Perintah & Tombol Bot
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="tab-logs-tab" data-toggle="pill" href="#tab-logs" role="tab">
                                <i class="fas fa-history mr-1 text-secondary"></i>Riwayat Log
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="wa-tabContent">

                        <!-- =======================================================
                             TAB 1: BROADCAST LAPORAN KE GRUP WA
                             ======================================================= -->
                        <div class="tab-pane fade show active" id="tab-broadcast" role="tabpanel">
                            <div class="row">
                                <!-- Card A: Siswa Belum Ujian Hari Ini -->
                                <div class="col-12 col-lg-6 mb-4">
                                    <div class="card border rounded h-100 bg-white">
                                        <div class="card-header bg-light border-bottom py-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="font-weight-bold m-0 text-dark">
                                                    <i class="fas fa-user-times text-danger mr-2"></i>Laporan Siswa Belum Ujian Hari Ini
                                                </h6>
                                                <span class="badge badge-danger"><?= $total_unattended ?> Siswa</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-sm text-muted mb-3">
                                                Secara otomatis mendata siswa yang terdaftar di kelas jadwal hari ini namun belum login atau belum mulai ujian. Nama-nama siswa akan dikelompokkan per kelas dan langsung dikirim ke grup WhatsApp pengawas/guru.
                                            </p>

                                            <?php if (!empty($unattended_reports)): ?>
                                                <div class="border rounded p-3 mb-3 bg-light text-sm" style="max-height: 180px; overflow-y: auto;">
                                                    <?php foreach ($unattended_reports as $rep): ?>
                                                        <div class="font-weight-bold text-dark mb-1">
                                                            <i class="far fa-file-alt text-primary mr-1"></i><?= $rep['jadwal']->nama_mapel ?? $rep['jadwal']->bank_kode ?>: 
                                                            <span class="text-danger font-weight-bold"><?= $rep['total_tidak_hadir'] ?> belum ujian</span> 
                                                            <span class="text-muted">(dari <?= $rep['total_terdaftar'] ?> siswa)</span>
                                                        </div>
                                                        <?php foreach ($rep['unattended_by_class'] as $kls => $s_arr): ?>
                                                            <div class="ml-3 text-xs text-muted mb-1">
                                                                • <b>Kelas <?= $kls ?>:</b> <?= count($s_arr) ?> siswa
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-success text-sm py-2 px-3 mb-3">
                                                    <i class="fas fa-check-circle mr-1"></i>Tidak ada siswa yang tertinggal ujian hari ini.
                                                </div>
                                            <?php endif; ?>

                                            <button id="btn-broadcast-unattended" class="btn btn-danger btn-block font-weight-bold py-2" <?= !$is_connected ? 'disabled' : '' ?>>
                                                <i class="fab fa-whatsapp mr-2"></i>Kirim Laporan Siswa Belum Ujian ke Grup WA
                                            </button>
                                            <?php if (!$is_connected): ?>
                                                <small class="text-danger d-block text-center mt-1">⚠️ Atur Token Fonnte terlebih dahulu di tab Pengaturan.</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card B: Rekap Nilai Mapel & File Excel -->
                                <div class="col-12 col-lg-6 mb-4">
                                    <div class="card border rounded h-100 bg-white">
                                        <div class="card-header bg-light border-bottom py-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="font-weight-bold m-0 text-dark">
                                                    <i class="fas fa-file-excel text-success mr-2"></i>Kirim Rekap Nilai Mapel ke Grup WA
                                                </h6>
                                                <span class="badge badge-success">Format Excel (.xlsx)</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-sm text-muted mb-3">
                                                Pilih mata pelajaran yang sudah direkap nilainya. Sistem akan membuat dokumen Excel resmi, menghitung rata-rata, nilai tertinggi/terendah, dan mengirimkan file beserta ringkasannya langsung ke grup WhatsApp.
                                            </p>

                                            <div class="form-group mb-3">
                                                <label class="text-xs font-weight-bold text-uppercase text-muted">Pilih Jadwal Mata Pelajaran</label>
                                                <select id="select-jadwal-rekap" class="form-control select2" style="width: 100%;">
                                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                                    <?php foreach ($jadwals as $j): ?>
                                                        <option value="<?= $j->id_jadwal ?>">
                                                            <?= $j->nama_mapel ?> (<?= $j->bank_kode ?>) - Tgl: <?= date('d/m/Y', strtotime($j->tgl_mulai)) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="p-3 bg-light border rounded mb-3 text-xs text-muted">
                                                <i class="fas fa-info-circle text-info mr-1"></i>
                                                Pesan WhatsApp akan dilengkapi dokumen Excel lampiran yang dapat langsung diunduh oleh guru di grup, disertai rangkuman statistik dan siswa berprestasi.
                                            </div>

                                            <button id="btn-broadcast-rekap" class="btn btn-success btn-block font-weight-bold py-2" <?= !$is_connected ? 'disabled' : '' ?>>
                                                <i class="fas fa-paper-plane mr-2"></i>Kirim Rekap Nilai & File Excel ke Grup WA
                                            </button>
                                            <?php if (!$is_connected): ?>
                                                <small class="text-danger d-block text-center mt-1">⚠️ Atur Token Fonnte terlebih dahulu di tab Pengaturan.</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card C: Broadcast Jadwal Ujian & Nama Pengawas (Hari Ini & Besok) -->
                                <div class="col-12 mb-2">
                                    <div class="card border rounded bg-white">
                                        <div class="card-header bg-light border-bottom py-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="font-weight-bold m-0 text-dark">
                                                    <i class="fas fa-calendar-check text-info mr-2"></i>Pengingat Jadwal Ujian &amp; Nama Pengawas (Ruang &amp; Sesi)
                                                </h6>
                                                <span class="badge <?= !empty($wa_setting->auto_schedule_enabled) ? 'badge-info' : 'badge-secondary' ?>">
                                                    <?= !empty($wa_setting->auto_schedule_enabled)
                                                        ? 'Otomatis: Sore ' . html_escape($wa_setting->schedule_afternoon_time ?? '15:00') . ' & Pagi ' . html_escape($wa_setting->schedule_morning_time ?? '05:00') . ' WIB'
                                                        : 'Pengingat Otomatis OFF' ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-sm text-muted mb-3">
                                                Mengirim daftar mata pelajaran, kelas, jam/sesi, ruang, dan <b>nama guru pengawas</b> langsung ke grup WA. Secara otomatis sistem mengirim pengingat <b>jadwal besok jam <?= html_escape($wa_setting->schedule_afternoon_time ?? '15:00') ?> WIB</b> dan <b>jadwal hari ini + Kode Exambro jam <?= html_escape($wa_setting->schedule_morning_time ?? '05:00') ?> WIB</b> (jika ada jadwal ujian).
                                            </p>
                                            <div class="row">
                                                <div class="col-12 col-md-6 mb-2 mb-md-0">
                                                    <button type="button" id="btn-broadcast-schedule-today" class="btn btn-info btn-block font-weight-bold py-2" <?= !$is_connected ? 'disabled' : '' ?>>
                                                        <i class="fas fa-sun mr-2"></i>Kirim Jadwal &amp; Pengawas HARI INI ke Grup WA
                                                    </button>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <button type="button" id="btn-broadcast-schedule-tomorrow" class="btn btn-primary btn-block font-weight-bold py-2" <?= !$is_connected ? 'disabled' : '' ?>>
                                                        <i class="fas fa-calendar-day mr-2"></i>Kirim Jadwal &amp; Pengawas BESOK ke Grup WA
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- =======================================================
                             TAB 2: PENGATURAN FONNTE & WEBHOOK
                             ======================================================= -->
                        <div class="tab-pane fade" id="tab-setting" role="tabpanel">
                            <div class="row">
                                <div class="col-12 col-lg-7 mb-4">
                                    <?= form_open('', ['id' => 'form-wa-setting']) ?>
                                        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-key text-warning mr-1"></i>Token API Fonnte <span class="text-danger">*</span>
                                            </label>
                                            <input type="password" name="api_token" id="api_token" class="form-control" 
                                                   value="<?= html_escape($wa_setting->api_token) ?>" placeholder="Contoh: aBcDeF1234567890xyz...">
                                            <small class="text-muted">
                                                Dapatkan token API gratis/berlangganan di dashboard <a href="https://fonnte.com" target="_blank" class="font-weight-bold text-primary">fonnte.com</a>.
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-users text-primary mr-1"></i>ID Grup WhatsApp (Tujuan Broadcast Laporan)
                                            </label>
                                            <div class="input-group">
                                                <input type="text" name="target_group" id="target_group" class="form-control font-weight-bold" 
                                                       value="<?= html_escape($wa_setting->target_group) ?>" placeholder="Contoh: 120363023456789012@g.us">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-primary" id="btn-fetch-groups" title="Tarik daftar grup dari WhatsApp Fonnte">
                                                        <i class="fas fa-search mr-1"></i>Pilih dari Daftar Grup
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                Klik tombol <b>Pilih dari Daftar Grup</b> untuk otomatis memuat grup WA.
                                                <br><span class="text-info"><i class="fas fa-shield-alt mr-1"></i><b>Mode Fokus Grup Aktif:</b> Bot hanya membalas perintah berawalan <code>!</code> / <code>/</code> / <code>#</code> (misal <code>!absen</code>, <code>!rekap</code>, <code>!server</code>, <code>!token</code>, <code>!menu</code>) dari ID Grup di atas (bisa lebih dari 1 grup, pisahkan dengan koma). Chat pribadi &amp; grup lain otomatis diabaikan.</span>
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-user-shield text-info mr-1"></i>Nomor WhatsApp Admin (Tes Koneksi)
                                            </label>
                                            <input type="text" name="target_admin" id="target_admin" class="form-control" 
                                                   value="<?= html_escape($wa_setting->target_admin) ?>" placeholder="Contoh: 08123456789 atau 628123456789">
                                            <small class="text-muted">Nomor kontak admin untuk pengujian pesan.</small>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="status" name="status" value="1" <?= $wa_setting->status == 1 ? 'checked' : '' ?>>
                                                <label class="custom-control-label font-weight-bold" for="status">Aktifkan Layanan WhatsApp Bot &amp; Gateway</label>
                                            </div>
                                        </div>

                                        <div class="p-3 rounded border bg-light mb-3">
                                            <div class="custom-control custom-switch mb-2">
                                                <input type="checkbox" class="custom-control-input" id="auto_schedule_enabled" name="auto_schedule_enabled" value="1" <?= !isset($wa_setting->auto_schedule_enabled) || $wa_setting->auto_schedule_enabled == 1 ? 'checked' : '' ?>>
                                                <label class="custom-control-label font-weight-bold text-dark" for="auto_schedule_enabled">
                                                    <i class="fas fa-clock text-info mr-1"></i>Notifikasi Otomatis Harian Jadwal Ujian &amp; Pengawas
                                                </label>
                                            </div>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label class="text-xs font-weight-bold text-muted mb-1">Jam Sore (Info Jadwal BESOK)</label>
                                                    <input type="time" name="schedule_afternoon_time" class="form-control form-control-sm" value="<?= html_escape($wa_setting->schedule_afternoon_time ?? '15:00') ?>">
                                                </div>
                                                <div class="col-6">
                                                    <label class="text-xs font-weight-bold text-muted mb-1">Jam Pagi (Info Jadwal HARI INI)</label>
                                                    <input type="time" name="schedule_morning_time" class="form-control form-control-sm" value="<?= html_escape($wa_setting->schedule_morning_time ?? '05:00') ?>">
                                                </div>
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                Hanya dikirim jika ada jadwal ujian aktif pada tanggal tersebut (tidak spam saat hari libur).
                                            </small>
                                        </div>

                                        <div class="d-flex flex-wrap align-items-center mt-4">
                                            <button type="submit" class="btn btn-primary font-weight-bold px-4 mr-2 mb-2" id="btn-save-setting">
                                                <i class="fas fa-save mr-1"></i>Simpan Pengaturan
                                            </button>
                                            <button type="button" class="btn btn-outline-success font-weight-bold mr-2 mb-2" id="btn-test-send-group">
                                                <i class="fas fa-users mr-1"></i>Tes Kirim ke Grup WA
                                            </button>
                                            <button type="button" class="btn btn-outline-info font-weight-bold mb-2" id="btn-test-send">
                                                <i class="fab fa-whatsapp mr-1"></i>Tes Kirim ke Admin
                                            </button>
                                        </div>
                                    <?= form_close() ?>
                                </div>

                                <!-- Box Webhook URL & Panduan -->
                                <div class="col-12 col-lg-5">
                                    <div class="card bg-white border rounded">
                                        <div class="card-header bg-light border-bottom py-2">
                                            <h6 class="font-weight-bold m-0 text-dark">
                                                <i class="fas fa-plug text-primary mr-1"></i>Webhook Fonnte (Bot WhatsApp Otomatis)
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-xs text-muted mb-2">
                                                Pasang URL Webhook berikut di dashboard Fonnte agar bot dapat merespons kata kunci seperti <code>!absen</code> dan <code>!rekap</code> secara otomatis saat diketik di grup WA:
                                            </p>

                                            <div class="input-group mb-3">
                                                <input type="text" id="webhook_url" class="form-control font-weight-bold text-xs bg-light" readonly value="<?= $webhook_url ?>">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-primary btn-sm" id="btn-copy-webhook" title="Salin URL">
                                                        <i class="far fa-copy mr-1"></i>Salin
                                                    </button>
                                                </div>
                                            </div>

                                            <h6 class="font-weight-bold text-xs text-uppercase text-muted mt-3 mb-2">Langkah Mudah Memasang:</h6>
                                            <ol class="text-xs text-muted pl-3 mb-0" style="line-height: 1.6;">
                                                <li>Buka dashboard <b>fonnte.com</b> dan pilih menu <b>Device</b>.</li>
                                                <li>Klik ikon pengaturan/pensil pada device WhatsApp Anda.</li>
                                                <li>Pada kolom <b>Webhook URL</b>, tempelkan URL di atas.</li>
                                                <li>Simpan. Bot WhatsApp SADUCI CBT kini siap membalas otomatis di grup!</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- =======================================================
                             TAB 3: PANDUAN PERINTAH BOT & TOMBOL
                             ======================================================= -->
                        <div class="tab-pane fade" id="tab-guide" role="tabpanel">
                            <div class="row">
                                <div class="col-12 col-lg-7 mb-4">
                                    <h6 class="font-weight-bold text-dark mb-3">
                                        <i class="fas fa-keyboard text-primary mr-2"></i>Kata Kunci / Tombol yang Dikenali oleh Bot WhatsApp
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover text-sm">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th width="140">Kata Kunci / Tombol</th>
                                                    <th>Fungsi Respons Bot</th>
                                                    <th width="120">Tujuan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><span class="badge badge-success px-2 py-1">!live</span></td>
                                                    <td>Ringkasan live progres ujian hari ini (Mengerjakan, Selesai, Pakai Exambro vs Browser lain, Belum Pin Home).</td>
                                                    <td><span class="badge badge-primary">Grup Target</span></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge badge-info px-2 py-1">!jadwal</span> / <span class="badge badge-info px-2 py-1">!besok</span></td>
                                                    <td>Menampilkan <b>jadwal ujian, kelas, jam/sesi, ruang, dan nama guru pengawas</b> untuk hari ini atau besok.</td>
                                                    <td><span class="badge badge-primary">Grup Target</span></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge badge-dark px-2 py-1">!kode</span></td>
                                                    <td>Menampilkan <b>Kode Keluar Exambro</b> aktif hari ini, PIN darurat, dan status Wajib Exambro/Snapshot.</td>
                                                    <td><span class="badge badge-primary">Grup Target</span></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge badge-danger px-2 py-1">!pelanggaran</span></td>
                                                    <td>Daftar siswa yang terdeteksi pindah layar atau login bukan dari Saduci Exambro beserta nama HP-nya.</td>
                                                    <td><span class="badge badge-primary">Grup Target</span></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge badge-warning px-2 py-1">!reset &lt;NIS&gt;</span> <br><small class="text-muted">atau !reset semua</small></td>
                                                    <td><b>Reset login 1 siswa</b> (via NIS/Nama) atau semua siswa aktif langsung dari grup WA (tercatat di Audit Trail).</td>
                                                    <td><span class="badge badge-primary">Grup Target</span></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge badge-danger px-2 py-1">!selesai &lt;NIS&gt;</span></td>
                                                    <td><b>Paksa selesai</b> ujian siswa (jika HP siswa mati/rusak) langsung dari grup WA.</td>
                                                    <td><span class="badge badge-primary">Grup Target</span></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge badge-primary px-2 py-1">!foto &lt;NIS&gt;</span></td>
                                                    <td>Mengirim <b>bukti foto kamera depan terbaru</b> siswa langsung ke grup WA.</td>
                                                    <td><span class="badge badge-primary">Grup Target</span></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge badge-success px-2 py-1">!siap</span></td>
                                                    <td>Cek kesiapan ujian cepat (Pre-Flight Check): jadwal hari ini, bank soal kosong, ruang/sesi, &amp; sisa disk VPS.</td>
                                                    <td><span class="badge badge-primary">Grup Target</span></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge badge-danger px-2 py-1">!absen</span></td>
                                                    <td>Menampilkan jumlah dan <b>nama-nama siswa yang belum mengikuti ujian hari ini</b> per kelas.</td>
                                                    <td><span class="badge badge-primary">Grup Target</span></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge badge-success px-2 py-1">!rekap</span></td>
                                                    <td>Mengirimkan <b>rekapitulasi nilai ujian per mapel beserta file Excel (.xlsx)</b>.</td>
                                                    <td><span class="badge badge-primary">Grup Target</span></td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge badge-info px-2 py-1">!server</span> / <span class="badge badge-warning px-2 py-1">!token</span> / <span class="badge badge-secondary px-2 py-1">!menu</span></td>
                                                    <td>Cek beban CPU/RAM/Disk VPS, token ujian aktif, atau tampilkan daftar lengkap perintah bot.</td>
                                                    <td><span class="badge badge-primary">Grup Target</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Mockup Tampilan WhatsApp -->
                                <div class="col-12 col-lg-5">
                                    <div class="card border rounded bg-white">
                                        <div class="card-header bg-success text-white py-2">
                                            <h6 class="font-weight-bold m-0 text-sm">
                                                <i class="fab fa-whatsapp mr-1"></i>Simulasi Tampilan Pesan WhatsApp
                                            </h6>
                                        </div>
                                        <div class="card-body p-3 bg-light" style="font-family: monospace; font-size: 11.5px; line-height: 1.5;">
                                            <div class="p-2 bg-white rounded shadow-sm border mb-2">
                                                📢 <b>LAPORAN SISWA BELUM UJIAN</b><br>
                                                🏫 SMPN Satu Atap 2 Cipatujah<br>
                                                📝 Mapel: Matematika (PTS-MTK-7)<br>
                                                👥 Total Terdaftar: 90 Siswa<br>
                                                ✅ Sudah Ujian: 82 Siswa<br>
                                                ❌ <b>Belum Ujian: 8 Siswa</b><br><br>
                                                📌 <b>Kelas VII-A (3 Siswa):</b><br>
                                                1. Ahmad Fauzi (NIS: 2425001)<br>
                                                2. Budi Santoso (NIS: 2425012)<br>
                                                3. Citra Lestari (NIS: 2425023)<br>
                                                ...<br><br>
                                                💡 Ketik perintah: <b>!absen</b>, <b>!rekap</b>, atau <b>!server</b>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- =======================================================
                             TAB 4: LOG AKTIVITAS WHATSAPP
                             ======================================================= -->
                        <div class="tab-pane fade" id="tab-logs" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped text-sm mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="140">Waktu</th>
                                            <th width="80">Arah</th>
                                            <th width="140">Tujuan / Pengirim</th>
                                            <th>Isi Pesan / Aksi</th>
                                            <th width="100" class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($logs)): ?>
                                            <?php foreach ($logs as $lg): ?>
                                                <tr>
                                                    <td class="text-muted text-xs"><?= date('d/m/Y H:i:s', strtotime($lg->time)) ?></td>
                                                    <td>
                                                        <span class="badge <?= $lg->direction === 'in' ? 'badge-info' : 'badge-primary' ?>">
                                                            <?= $lg->direction === 'in' ? '⬅️ Masuk' : '➡️ Keluar' ?>
                                                        </span>
                                                    </td>
                                                    <td class="font-weight-bold text-xs"><?= html_escape($lg->target ?: $lg->sender) ?></td>
                                                    <td class="text-truncate" style="max-width: 320px;" title="<?= html_escape($lg->message) ?>">
                                                        <?= html_escape($lg->message) ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge <?= $lg->status === 'SUCCESS' ? 'badge-success' : 'badge-danger' ?>">
                                                            <?= $lg->status ?>
                                                        </span>
                                                        <?php if ($lg->status !== 'SUCCESS' && !empty($lg->response)): ?>
                                                            <?php 
                                                                $resp_decoded = json_decode($lg->response, true);
                                                                $reason = is_array($resp_decoded) ? ($resp_decoded['reason'] ?? ($resp_decoded['message'] ?? $lg->response)) : $lg->response;
                                                            ?>
                                                            <small class="text-danger d-block text-xs mt-1" style="max-width: 160px; margin: 0 auto; line-height: 1.2;" title="<?= html_escape($reason) ?>">
                                                                <?= html_escape(mb_strimwidth($reason, 0, 45, '...')) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat pengiriman pesan WhatsApp.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- Modal Pilih Grup WhatsApp -->
<div class="modal fade" id="modal-pilih-grup" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold"><i class="fab fa-whatsapp mr-2"></i>Daftar Grup WhatsApp (Fonnte)</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="loading-groups" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2 mb-0">Sedang menarik daftar grup dari Fonnte WhatsApp...</p>
                </div>
                <div id="content-groups">
                    <p class="text-xs text-muted mb-3">
                        Berikut adalah daftar grup WhatsApp yang diikuti oleh nomor device WhatsApp Anda. Klik tombol <b>Pilih</b> pada grup yang ingin dijadikan target pengiriman laporan ujian CBT.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered text-sm" id="table-grup-wa">
                            <thead class="bg-light">
                                <tr>
                                    <th width="40" class="text-center">No</th>
                                    <th>Nama Grup WhatsApp</th>
                                    <th>ID Grup WhatsApp</th>
                                    <th width="100" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-groups">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ===================================================================
     JAVASCRIPT LOGIC
     =================================================================== -->
<script>
$(document).ready(function () {
    if (typeof ajaxcsrf === 'function') {
        ajaxcsrf();
    }
    $('.select2').select2({ theme: 'bootstrap4' });

    // Tarik Daftar Grup WA dari Fonnte
    $('#btn-fetch-groups').on('click', function () {
        const token = $('#api_token').val();
        if (!token) {
            Swal.fire('Token Kosong', 'Silakan isi Token API Fonnte terlebih dahulu sebelum menarik daftar grup.', 'warning');
            return;
        }

        $('#modal-pilih-grup').modal('show');
        $('#loading-groups').removeClass('d-none');
        $('#content-groups').addClass('d-none');

        $.ajax({
            url: base_url + 'cbtwa/get_groups',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                $('#loading-groups').addClass('d-none');
                $('#content-groups').removeClass('d-none');

                let rows = '';
                const groups = res.data || res;
                if (Array.isArray(groups) && groups.length > 0) {
                    groups.forEach(function (g, idx) {
                        const gId = g.id || g.jid || '';
                        const gName = g.name || g.subject || 'Grup Tanpa Nama';
                        rows += `
                            <tr>
                                <td class="text-center align-middle">${idx + 1}</td>
                                <td class="font-weight-bold align-middle text-dark"><i class="fas fa-users text-primary mr-1"></i>${gName}</td>
                                <td class="align-middle text-xs"><code>${gId}</code></td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-success btn-select-group" data-id="${gId}" data-name="${gName}">
                                        <i class="fas fa-check mr-1"></i>Pilih
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    rows = `<tr><td colspan="4" class="text-center py-4 text-muted">Tidak ditemukan grup WhatsApp atau device belum tergabung dalam grup manapun. Pastikan nomor WhatsApp bot sudah dimasukkan ke dalam grup sekolah.</td></tr>`;
                }
                $('#tbody-groups').html(rows);
            },
            error: function () {
                $('#loading-groups').addClass('d-none');
                $('#content-groups').removeClass('d-none');
                $('#tbody-groups').html(`<tr><td colspan="4" class="text-center py-4 text-danger">Gagal menghubungi API Fonnte. Pastikan token benar dan nomor WhatsApp terhubung.</td></tr>`);
            }
        });
    });

    $(document).on('click', '.btn-select-group', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        $('#target_group').val(id);
        $('#modal-pilih-grup').modal('hide');
        toastr.success('Grup "' + name + '" dipilih!');
    });

    // Salin Webhook URL
    $('#btn-copy-webhook').on('click', function () {
        const url = $('#webhook_url').val();
        navigator.clipboard.writeText(url).then(function() {
            toastr.success('URL Webhook berhasil disalin ke clipboard!');
        });
    });

    // Simpan Pengaturan
    $('#form-wa-setting').on('submit', function (e) {
        e.preventDefault();
        const btn = $('#btn-save-setting');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i>Menyimpan...');

        $.ajax({
            url: base_url + 'cbtwa/save_setting',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Pengaturan');
                if (res.status) {
                    Swal.fire('Berhasil!', res.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Pengaturan');
                let errText = 'Terjadi kesalahan pada server';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errText = xhr.responseJSON.message;
                } else if (xhr.status === 403) {
                    errText = 'Token sesi/keamanan (CSRF) kedaluwarsa. Silakan refresh halaman dan coba lagi.';
                }
                Swal.fire('Error (' + xhr.status + ')', errText, 'error');
            }
        });
    });

    // Kirim Pesan Tes ke Grup WA
    $('#btn-test-send-group').on('click', function () {
        const groupTarget = ($('#target_group').val() || '').trim();
        if (!groupTarget) {
            Swal.fire('Grup Belum Dipilih', 'Pilih atau masukkan ID Grup WhatsApp terlebih dahulu lalu simpan pengaturan.', 'warning');
            return;
        }
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i>Mengirim ke Grup...');

        $.ajax({
            url: base_url + 'cbtwa/test_send',
            type: 'POST',
            data: { target: groupTarget },
            dataType: 'json',
            success: function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-users mr-1"></i>Tes Kirim ke Grup WA');
                if (res.status) {
                    Swal.fire('Terkirim ke Grup!', 'Pesan pengujian berhasil dikirim ke grup: ' + groupTarget, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal Kirim ke Grup!', res.reason || res.message || 'Periksa token Fonnte, status device, atau pastikan nomor bot ada di dalam grup.', 'error');
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="fas fa-users mr-1"></i>Tes Kirim ke Grup WA');
                Swal.fire('Error!', 'Gagal menghubungi server', 'error');
            }
        });
    });

    // Kirim Pesan Tes ke Nomor Admin
    $('#btn-test-send').on('click', function () {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i>Mengirim...');

        $.ajax({
            url: base_url + 'cbtwa/test_send',
            type: 'POST',
            data: { target: $('#target_admin').val() || $('#target_group').val() },
            dataType: 'json',
            success: function (res) {
                btn.prop('disabled', false).html('<i class="fab fa-whatsapp mr-1"></i>Tes Kirim ke Admin');
                if (res.status) {
                    Swal.fire('Terkirim!', 'Pesan pengujian berhasil dikirim via Fonnte!', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal!', res.reason || res.message || 'Gagal mengirim pesan tes. Periksa kembali token API Fonnte.', 'error');
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="fab fa-whatsapp mr-1"></i>Tes Kirim ke Admin');
                Swal.fire('Error!', 'Gagal menghubungi server', 'error');
            }
        });
    });

    // Broadcast Laporan Siswa Belum Ujian Hari Ini
    $('#btn-broadcast-unattended').on('click', function () {
        const btn = $(this);
        Swal.fire({
            title: 'Kirim Laporan Siswa Belum Ujian?',
            text: 'Data rekap siswa yang belum ujian hari ini akan langsung dikirimkan ke grup WhatsApp!',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Kirim ke Grup WA',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-2"></i>Mengirim Laporan...');
                $.ajax({
                    url: base_url + 'cbtwa/broadcast_unattended',
                    type: 'POST',
                    dataType: 'json',
                    success: function (res) {
                        btn.prop('disabled', false).html('<i class="fab fa-whatsapp mr-2"></i>Kirim Laporan Siswa Belum Ujian ke Grup WA');
                        if (res.status) {
                            Swal.fire('Berhasil Terkirim!', 'Laporan siswa belum ujian hari ini telah diposting ke grup WhatsApp!', 'success');
                        } else {
                            Swal.fire('Gagal!', res.message || 'Gagal mengirim laporan', 'error');
                        }
                    },
                    error: function () {
                        btn.prop('disabled', false).html('<i class="fab fa-whatsapp mr-2"></i>Kirim Laporan Siswa Belum Ujian ke Grup WA');
                        Swal.fire('Error!', 'Terjadi kesalahan sistem saat mengirim laporan', 'error');
                    }
                });
            }
        });
    });

    // Broadcast Rekap Nilai Mapel & Excel
    $('#btn-broadcast-rekap').on('click', function () {
        const idJadwal = $('#select-jadwal-rekap').val();
        if (!idJadwal) {
            Swal.fire('Pilih Mata Pelajaran', 'Silakan pilih jadwal mata pelajaran yang ingin direkap terlebih dahulu.', 'warning');
            return;
        }

        const btn = $(this);
        Swal.fire({
            title: 'Kirim Rekap Nilai ke Grup WA?',
            text: 'Sistem akan membuat file Excel rekapitulasi nilai dan mengirimkannya langsung ke grup WhatsApp.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Buat Excel & Kirim',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-2"></i>Membuat Dokumen Excel & Mengirim...');
                $.ajax({
                    url: base_url + 'cbtwa/broadcast_rekap',
                    type: 'POST',
                    data: { id_jadwal: idJadwal },
                    dataType: 'json',
                    success: function (res) {
                        btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-2"></i>Kirim Rekap Nilai & File Excel ke Grup WA');
                        if (res.status) {
                            Swal.fire({
                                title: 'Rekap Nilai Terkirim!',
                                html: `Rekap nilai dan file Excel berhasil dikirim ke grup WhatsApp!<br><br><a href="${res.excel_url}" target="_blank" class="btn btn-sm btn-outline-success"><i class="fas fa-download mr-1"></i>Unduh File Excel Rekap</a>`,
                                icon: 'success'
                            });
                        } else {
                            Swal.fire('Gagal!', res.message || 'Gagal mengirim rekap nilai', 'error');
                        }
                    },
                    error: function () {
                        btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-2"></i>Kirim Rekap Nilai & File Excel ke Grup WA');
                        Swal.fire('Error!', 'Terjadi kesalahan sistem saat memproses rekap nilai', 'error');
                    }
                });
            }
        });
    });

    // Broadcast Jadwal & Pengawas (Hari Ini / Besok)
    function triggerScheduleBroadcast(mode, btnSelector, originalHtml) {
        const label = mode === 'tomorrow' ? 'BESOK' : 'HARI INI';
        const btn = $(btnSelector);
        Swal.fire({
            title: 'Kirim Jadwal & Pengawas ' + label + '?',
            text: 'Daftar mapel, ruang, sesi, dan nama guru pengawas ' + label + ' akan dikirim ke grup WA.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Kirim ke Grup WA',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-2"></i>Mengirim...');
                $.ajax({
                    url: base_url + 'cbtwa/broadcast_schedule',
                    type: 'POST',
                    data: { mode: mode },
                    dataType: 'json',
                    success: function (res) {
                        btn.prop('disabled', false).html(originalHtml);
                        if (res && res.status) {
                            Swal.fire('Terkirim!', 'Jadwal ujian & nama pengawas ' + label + ' berhasil dikirim ke grup WA.', 'success');
                        } else {
                            Swal.fire('Gagal!', res?.reason || res?.message || 'Gagal mengirim jadwal.', 'error');
                        }
                    },
                    error: function () {
                        btn.prop('disabled', false).html(originalHtml);
                        Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                    }
                });
            }
        });
    }

    $('#btn-broadcast-schedule-today').on('click', function () {
        triggerScheduleBroadcast('today', '#btn-broadcast-schedule-today', '<i class="fas fa-sun mr-2"></i>Kirim Jadwal &amp; Pengawas HARI INI ke Grup WA');
    });

    $('#btn-broadcast-schedule-tomorrow').on('click', function () {
        triggerScheduleBroadcast('tomorrow', '#btn-broadcast-schedule-tomorrow', '<i class="fas fa-calendar-day mr-2"></i>Kirim Jadwal &amp; Pengawas BESOK ke Grup WA');
    });
});
</script>

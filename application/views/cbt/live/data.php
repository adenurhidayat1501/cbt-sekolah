<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * View Live Monitor Ujian Siswa & Monitor Sistem
 * SMP Negeri Satu Atap 2 Cipatujah (SADUCI CBT)
 */
?>

<div class="content-wrapper bg-light">
    <!-- Header Content -->
    <section class="content-header pb-2">
        <div class="container-fluid">
            <div class="row align-items-center mb-2">
                <div class="col-sm-6">
                    <h1 class="font-weight-bold text-dark m-0" style="font-size: 1.6rem;">
                        <i class="fas fa-satellite-dish text-danger mr-2"></i><?= $judul ?>
                    </h1>
                    <small class="text-muted">Pantau aktivitas ujian siswa, progres soal, pengawas, dan resource server secara real-time.</small>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>"><i class="fas fa-home mr-1"></i>Beranda</a></li>
                        <li class="breadcrumb-item">CBT</li>
                        <li class="breadcrumb-item active"><?= $judul ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="content">
        <div class="container-fluid">

            <!-- ===================================================================
                 WIDGET 1: MONITOR RESOURCE SISTEM & SERVER HEALTH
                 =================================================================== -->
            <div class="card card-outline card-primary my-shadow mb-4">
                <div class="card-header border-0 py-2">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <h6 class="card-title font-weight-bold m-0 text-primary">
                            <i class="fas fa-microchip mr-2"></i>Status & Kesehatan Server Real-Time
                        </h6>
                        <div class="card-tools d-flex align-items-center">
                            <span class="badge badge-success px-2 py-1 mr-2" id="sys-conn-badge">
                                <span class="pulse-online mr-1"></span> Live Sync
                            </span>
                            <small class="text-muted mr-3" id="sys-server-time"><i class="far fa-clock mr-1"></i>--:--:--</small>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-1 pb-3">
                    <div class="row">
                        <!-- CPU Load -->
                        <div class="col-6 col-md-3 mb-2">
                            <div class="sys-metric-card p-3 rounded border bg-white h-100">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted font-weight-bold text-xs text-uppercase">CPU Load</span>
                                    <span class="badge badge-info" id="sys-cpu-cores">-- Core</span>
                                </div>
                                <div class="d-flex align-items-baseline mb-2">
                                    <h4 class="font-weight-bold m-0 mr-2 text-dark" id="sys-cpu-val">0%</h4>
                                    <small class="text-muted" id="sys-cpu-avg">avg: 0.00</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-info" id="sys-cpu-bar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- RAM Usage -->
                        <div class="col-6 col-md-3 mb-2">
                            <div class="sys-metric-card p-3 rounded border bg-white h-100">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted font-weight-bold text-xs text-uppercase">RAM Memory</span>
                                    <span class="badge badge-primary" id="sys-ram-percent">0%</span>
                                </div>
                                <div class="d-flex align-items-baseline mb-2">
                                    <h4 class="font-weight-bold m-0 mr-2 text-dark" id="sys-ram-used">0.0 GB</h4>
                                    <small class="text-muted" id="sys-ram-total">/ 0.0 GB</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" id="sys-ram-bar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- MariaDB Threads & Live Students -->
                        <div class="col-6 col-md-3 mb-2">
                            <div class="sys-metric-card p-3 rounded border bg-white h-100">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted font-weight-bold text-xs text-uppercase">Siswa Live Online</span>
                                    <span class="badge badge-success" id="sys-db-threads">DB: --</span>
                                </div>
                                <div class="d-flex align-items-baseline mb-2">
                                    <h4 class="font-weight-bold m-0 mr-2 text-success" id="sys-live-students">0</h4>
                                    <small class="text-muted">sedang ujian</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Storage & Uptime -->
                        <div class="col-6 col-md-3 mb-2">
                            <div class="sys-metric-card p-3 rounded border bg-white h-100">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted font-weight-bold text-xs text-uppercase">Penyimpanan / Disk</span>
                                    <span class="badge badge-warning" id="sys-disk-percent">0%</span>
                                </div>
                                <div class="d-flex align-items-baseline mb-2">
                                    <h4 class="font-weight-bold m-0 mr-2 text-dark" id="sys-disk-free">0 GB</h4>
                                    <small class="text-muted" id="sys-disk-total">free of 0 GB</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-warning" id="sys-disk-bar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================================================================
                 WIDGET 2: FILTER JADWAL HARI INI & PILIH KELAS
                 =================================================================== -->
            <div class="card card-default my-shadow mb-4">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <!-- Dropdown Jadwal -->
                        <div class="col-12 col-md-5 mb-2 mb-md-0">
                            <label class="mb-1 font-weight-bold text-xs text-uppercase text-muted">
                                <i class="far fa-calendar-alt mr-1 text-primary"></i>Pilih Jadwal Ujian
                            </label>
                            <select id="select-jadwal" class="form-control select2" style="width: 100%;">
                                <option value="">-- Pilih Jadwal Ujian --</option>
                                <?php if (!empty($today_jadwals)): ?>
                                    <optgroup label="🌟 JADWAL HARI INI (<?= date('d/m/Y') ?>)">
                                        <?php foreach ($today_jadwals as $tj): ?>
                                            <option value="<?= $tj->id_jadwal ?>" data-bank="<?= $tj->bank_kode ?>" data-mapel="<?= $tj->nama_mapel ?>" selected>
                                                [HARI INI] <?= $tj->nama_mapel ?> (<?= $tj->bank_kode ?>) - <?= $tj->kode_jenis ?? 'Ujian' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>

                                <?php if (!empty($other_jadwals)): ?>
                                    <optgroup label="📅 SEMUA JADWAL AKTIF LAINNYA">
                                        <?php foreach ($other_jadwals as $oj): ?>
                                            <option value="<?= $oj->id_jadwal ?>" data-bank="<?= $oj->bank_kode ?>" data-mapel="<?= $oj->nama_mapel ?>">
                                                <?= $oj->nama_mapel ?> (<?= $oj->bank_kode ?>) - Tgl: <?= date('d/m', strtotime($oj->tgl_mulai)) ?> s/d <?= date('d/m', strtotime($oj->tgl_selesai)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Dropdown Kelas -->
                        <div class="col-12 col-md-4 mb-2 mb-md-0">
                            <label class="mb-1 font-weight-bold text-xs text-uppercase text-muted">
                                <i class="fas fa-school mr-1 text-primary"></i>Pilih Kelas
                            </label>
                            <select id="select-kelas" class="form-control select2" style="width: 100%;">
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($kelases as $kls_id => $kls_nama): ?>
                                    <option value="<?= $kls_id ?>"><?= $kls_nama ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Auto-Refresh & Tombol Muat -->
                        <div class="col-12 col-md-3 text-md-right mt-2 mt-md-0">
                            <label class="mb-1 font-weight-bold text-xs text-uppercase text-muted d-block">
                                <i class="fas fa-sync mr-1 text-primary"></i>Auto-Refresh
                            </label>
                            <div class="input-group">
                                <select id="select-interval" class="form-control form-control-sm">
                                    <option value="5000" selected>⚡ Tiap 5 Detik</option>
                                    <option value="10000">Tiap 10 Detik</option>
                                    <option value="30000">Tiap 30 Detik</option>
                                    <option value="0">Manual (Mati)</option>
                                </select>
                                <div class="input-group-append">
                                    <button id="btn-manual-refresh" class="btn btn-sm btn-primary" title="Refresh Sekarang">
                                        <i class="fas fa-redo-alt" id="icon-refresh"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================================================================
                 WIDGET 3: INFO JADWAL & DATA PENGAWAS RUANG/SESI
                 =================================================================== -->
            <div id="section-info-jadwal" class="card card-default my-shadow mb-4 d-none">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <!-- Info Ringkas Jadwal -->
                        <div class="col-12 col-lg-7 border-right-lg mb-3 mb-lg-0">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge badge-primary px-2 py-1 mr-2" id="info-mapel-badge">Mata Pelajaran</span>
                                <h5 class="font-weight-bold m-0 text-dark" id="info-nama-mapel">-</h5>
                            </div>
                            <div class="row text-sm text-muted">
                                <div class="col-sm-4 mb-1">
                                    <i class="fas fa-barcode mr-1"></i>Bank: <b class="text-dark" id="info-bank-kode">-</b>
                                </div>
                                <div class="col-sm-4 mb-1">
                                    <i class="far fa-clock mr-1"></i>Durasi: <b class="text-dark" id="info-durasi">-</b>
                                </div>
                                <div class="col-sm-4 mb-1">
                                    <i class="fas fa-key mr-1 text-warning"></i>Token: 
                                    <span class="badge badge-warning font-weight-bold px-2 py-1" id="info-token">-</span>
                                    <button class="btn btn-xs btn-outline-secondary ml-1" id="btn-copy-token" title="Salin Token">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Data Pengawas -->
                        <div class="col-12 col-lg-5">
                            <h6 class="font-weight-bold text-xs text-uppercase text-muted mb-2">
                                <i class="fas fa-user-shield mr-1 text-info"></i>Data Pengawas Ujian
                            </h6>
                            <div id="container-pengawas">
                                <span class="text-muted text-sm"><i class="fa fa-spinner fa-spin mr-1"></i>Memuat pengawas...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================================================================
                 WIDGET 4: STATISTIK RINGKAS KELAS (SUMMARY CARDS)
                 =================================================================== -->
            <div id="section-summary-cards" class="row mb-3 d-none">
                <div class="col-6 col-md-2 mb-2">
                    <div class="card bg-white border my-shadow mb-0 text-center py-2 px-1">
                        <span class="text-xs font-weight-bold text-muted text-uppercase">Total Siswa</span>
                        <h4 class="font-weight-bold text-dark m-0" id="stat-total">0</h4>
                    </div>
                </div>
                <div class="col-6 col-md-2 mb-2">
                    <div class="card bg-white border border-success my-shadow mb-0 text-center py-2 px-1">
                        <span class="text-xs font-weight-bold text-success text-uppercase">
                            <span class="pulse-online mr-1"></span>Mengerjakan
                        </span>
                        <h4 class="font-weight-bold text-success m-0" id="stat-mengerjakan">0</h4>
                    </div>
                </div>
                <div class="col-6 col-md-2 mb-2">
                    <div class="card bg-white border border-primary my-shadow mb-0 text-center py-2 px-1">
                        <span class="text-xs font-weight-bold text-primary text-uppercase">Selesai</span>
                        <h4 class="font-weight-bold text-primary m-0" id="stat-selesai">0</h4>
                    </div>
                </div>
                <div class="col-6 col-md-2 mb-2">
                    <div class="card bg-white border my-shadow mb-0 text-center py-2 px-1">
                        <span class="text-xs font-weight-bold text-muted text-uppercase">Belum Mulai</span>
                        <h4 class="font-weight-bold text-secondary m-0" id="stat-belum">0</h4>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-2">
                    <div class="card bg-white border border-danger my-shadow mb-0 text-center py-2 px-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-left">
                                <span class="text-xs font-weight-bold text-danger text-uppercase">
                                    <span class="pulse-danger mr-1"></span>Perlu Reset Login
                                </span>
                                <h4 class="font-weight-bold text-danger m-0" id="stat-reset">0</h4>
                            </div>
                            <button id="btn-quick-reset-all" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-unlock-alt mr-1"></i>Reset Semua Terkunci
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================================================================
                 WIDGET 5: TABEL LIVE MONITOR SISWA
                 =================================================================== -->
            <div class="card card-default my-shadow mb-5">
                <div class="card-header py-3 bg-white border-bottom">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-5 mb-2 mb-md-0">
                            <h5 class="card-title font-weight-bold m-0 text-dark">
                                <i class="fas fa-users mr-2 text-primary"></i>Daftar Siswa & Aktivitas Real-Time
                            </h5>
                        </div>
                        <div class="col-12 col-md-7 text-md-right d-flex flex-wrap justify-content-md-end align-items-center">
                            <!-- Quick Filter Search -->
                            <div class="input-group input-group-sm mr-2 mb-1" style="max-width: 220px;">
                                <input type="text" id="filter-search-siswa" class="form-control" placeholder="Cari nama / nomor...">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                            </div>

                            <!-- Bulk Action Buttons -->
                            <button id="btn-bulk-reset" class="btn btn-sm btn-warning mr-2 mb-1" disabled>
                                <i class="fas fa-unlock mr-1"></i>Reset Login (<span class="selected-count">0</span>)
                            </button>
                            <button id="btn-bulk-finish" class="btn btn-sm btn-danger mb-1" disabled>
                                <i class="fas fa-check-double mr-1"></i>Paksa Selesai (<span class="selected-count">0</span>)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <!-- Loading Overlay Indicator -->
                    <div id="table-loading" class="text-center py-5 d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Memuat data...</span>
                        </div>
                        <p class="text-muted mt-2 mb-0">Sedang mengambil status siswa terbaru...</p>
                    </div>

                    <!-- Placeholder Belum Memilih Kelas -->
                    <div id="table-placeholder" class="text-center py-5 text-muted">
                        <i class="fas fa-chalkboard-teacher fa-3x mb-3 text-black-50"></i>
                        <p class="mb-1 font-weight-bold">Silakan pilih Jadwal Ujian dan Kelas di atas untuk memulai monitoring.</p>
                        <small>Data live siswa, progres soal, dan sisa waktu akan otomatis dimuat dan diperbarui secara berkala.</small>
                    </div>

                    <!-- Responsive Table -->
                    <div class="table-responsive" id="table-wrapper">
                        <table class="table table-hover table-striped mb-0 text-sm align-middle" id="table-live-siswa">
                            <thead class="bg-light text-dark">
                                <tr>
                                    <th width="35" class="text-center align-middle">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="check-all-siswa">
                                            <label class="custom-control-label" for="check-all-siswa"></label>
                                        </div>
                                    </th>
                                    <th width="45" class="text-center align-middle">No.</th>
                                    <th width="110" class="text-center align-middle">No. Peserta</th>
                                    <th class="align-middle">Nama Siswa</th>
                                    <th width="110" class="text-center align-middle">Ruang / Sesi</th>
                                    <th width="130" class="text-center align-middle">Status Ujian</th>
                                    <th width="160" class="align-middle">Progres Soal</th>
                                    <th width="120" class="text-center align-middle">Mulai / Sisa</th>
                                    <th width="130" class="text-center align-middle">IP / Device</th>
                                    <th width="150" class="text-center align-middle">Aksi Cepat</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-siswa">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- ===================================================================
     STYLES KHUSUS LIVE MONITOR
     =================================================================== -->
<style>
.sys-metric-card {
    transition: all 0.2s ease;
    border-radius: 8px !important;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.sys-metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.pulse-online {
    display: inline-block;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: #28a745;
    box-shadow: 0 0 0 rgba(40, 167, 69, 0.7);
    animation: pulse-green 1.8s infinite;
}
@keyframes pulse-green {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 7px rgba(40, 167, 69, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
}
.pulse-danger {
    display: inline-block;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: #dc3545;
    box-shadow: 0 0 0 rgba(220, 53, 69, 0.7);
    animation: pulse-red 1.4s infinite;
}
@keyframes pulse-red {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 7px rgba(220, 53, 69, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}
.progress-bar-animated-custom {
    transition: width 0.4s ease;
}
@media (min-width: 992px) {
    .border-right-lg {
        border-right: 1px solid #dee2e6;
    }
}
</style>

<!-- ===================================================================
     JAVASCRIPT LIVE MONITOR
     =================================================================== -->
<script>
$(document).ready(function () {
    let liveTimer = null;
    let systemTimer = null;
    let isFetchingLive = false;
    let currentRawData = null;

    // Inisialisasi Select2
    $('#select-jadwal').select2({ theme: 'bootstrap4' });
    $('#select-kelas').select2({ theme: 'bootstrap4' });

    // Salin Token
    $('#btn-copy-token').on('click', function () {
        const token = $('#info-token').text();
        if (token && token !== '-') {
            navigator.clipboard.writeText(token).then(function() {
                toastr.success('Token ' + token + ' disalin ke clipboard');
            });
        }
    });

    // 1. Monitor System Stats Real-time (Setiap 4 detik)
    function fetchSystemStats() {
        $.ajax({
            url: base_url + 'cbtlive/get_system_stats',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res && res.status) {
                    $('#sys-server-time').html('<i class="far fa-clock mr-1"></i>' + res.server_time);
                    
                    // CPU
                    $('#sys-cpu-val').text(res.cpu.percent + '%');
                    $('#sys-cpu-avg').text('avg: ' + res.cpu.load[0]);
                    $('#sys-cpu-cores').text(res.cpu.cores + ' Core');
                    $('#sys-cpu-bar').css('width', res.cpu.percent + '%');
                    if (res.cpu.percent > 85) {
                        $('#sys-cpu-bar').removeClass('bg-info bg-warning').addClass('bg-danger');
                    } else if (res.cpu.percent > 65) {
                        $('#sys-cpu-bar').removeClass('bg-info bg-danger').addClass('bg-warning');
                    } else {
                        $('#sys-cpu-bar').removeClass('bg-warning bg-danger').addClass('bg-info');
                    }

                    // RAM
                    $('#sys-ram-used').text(res.ram.used + ' GB');
                    $('#sys-ram-total').text('/ ' + res.ram.total + ' GB');
                    $('#sys-ram-percent').text(res.ram.percent + '%');
                    $('#sys-ram-bar').css('width', res.ram.percent + '%');

                    // Database & Siswa Online
                    $('#sys-live-students').text(res.live_students);
                    $('#sys-db-threads').text('DB Threads: ' + res.database.threads);

                    // Disk
                    $('#sys-disk-free').text(res.disk.free_gb + ' GB');
                    $('#sys-disk-total').text('free of ' + res.disk.total_gb + ' GB');
                    $('#sys-disk-percent').text(res.disk.percent + '%');
                    $('#sys-disk-bar').css('width', res.disk.percent + '%');
                }
            }
        });
    }

    // Jalankan awal dan interval system stats
    fetchSystemStats();
    systemTimer = setInterval(fetchSystemStats, 4000);

    // 2. Event Perubahan Jadwal -> Update Dropdown Kelas
    $('#select-jadwal').on('change', function () {
        const idJadwal = $(this).val();
        if (!idJadwal) {
            resetView();
            return;
        }

        // Ambil kelas yang terhubung dengan jadwal ini
        $.ajax({
            url: base_url + 'cbtlive/get_kelas_by_jadwal?id_jadwal=' + idJadwal,
            type: 'GET',
            dataType: 'json',
            success: function (classes) {
                let options = '<option value="">-- Pilih Kelas --</option>';
                if (classes && classes.length > 0) {
                    classes.forEach(function (c) {
                        options += `<option value="${c.id_kelas}">${c.nama_kelas}</option>`;
                    });
                    $('#select-kelas').html(options);
                    // Jika ada kelas, otomatis pilih yang pertama
                    if (classes.length === 1) {
                        $('#select-kelas').val(classes[0].id_kelas).trigger('change');
                    }
                } else {
                    $('#select-kelas').html(options);
                }
            }
        });
    });

    // Otomatis trigger jika jadwal hari ini sudah terpilih secara default
    if ($('#select-jadwal').val()) {
        $('#select-jadwal').trigger('change');
    }

    // 3. Event Perubahan Kelas -> Muat Data Ujian
    $('#select-kelas').on('change', function () {
        const idKelas = $(this).val();
        const idJadwal = $('#select-jadwal').val();

        if (idJadwal && idKelas) {
            startLiveMonitoring();
        } else {
            resetView();
        }
    });

    // 4. Pengaturan Interval Auto-Refresh
    $('#select-interval').on('change', function () {
        restartTimer();
    });

    $('#btn-manual-refresh').on('click', function () {
        $('#icon-refresh').addClass('fa-spin');
        fetchLiveData(function () {
            $('#icon-refresh').removeClass('fa-spin');
        });
    });

    function restartTimer() {
        if (liveTimer) clearInterval(liveTimer);
        const interval = parseInt($('#select-interval').val());
        if (interval > 0 && $('#select-jadwal').val() && $('#select-kelas').val()) {
            liveTimer = setInterval(function () {
                fetchLiveData();
            }, interval);
        }
    }

    function startLiveMonitoring() {
        $('#table-placeholder').addClass('d-none');
        $('#table-loading').removeClass('d-none');
        fetchLiveData(function () {
            $('#table-loading').addClass('d-none');
        });
        restartTimer();
    }

    function resetView() {
        if (liveTimer) clearInterval(liveTimer);
        $('#section-info-jadwal').addClass('d-none');
        $('#section-summary-cards').addClass('d-none');
        $('#tbody-siswa').html('');
        $('#table-placeholder').removeClass('d-none');
        $('#table-loading').addClass('d-none');
    }

    // 5. Fetch Live Data Ujian Siswa
    function fetchLiveData(callback) {
        const idJadwal = $('#select-jadwal').val();
        const idKelas = $('#select-kelas').val();

        if (!idJadwal || !idKelas || isFetchingLive) {
            if (callback) callback();
            return;
        }

        isFetchingLive = true;
        $.ajax({
            url: base_url + 'cbtlive/get_live_data?id_jadwal=' + idJadwal + '&id_kelas=' + idKelas,
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                isFetchingLive = false;
                if (res && res.status) {
                    currentRawData = res;
                    renderInfoJadwal(res.info_jadwal);
                    renderPengawas(res.pengawas);
                    renderSummary(res.summary);
                    renderTableSiswa(res.students);
                }
                if (callback) callback();
            },
            error: function () {
                isFetchingLive = false;
                if (callback) callback();
            }
        });
    }

    // Render Info Jadwal
    function renderInfoJadwal(info) {
        $('#info-nama-mapel').text(info.nama_mapel);
        $('#info-bank-kode').text(info.bank_kode);
        $('#info-durasi').text(info.durasi);
        $('#info-token').text(info.token || 'TIDAK PAKAI');
        $('#section-info-jadwal').removeClass('d-none');
    }

    // Render Pengawas
    function renderPengawas(pengawas) {
        let html = '';
        if (pengawas && pengawas.length > 0) {
            pengawas.forEach(function (p) {
                if (p.gurus && p.gurus.length > 0) {
                    p.gurus.forEach(function (g) {
                        html += `
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge badge-light border mr-2"><i class="fas fa-door-open mr-1"></i>${p.ruang} / ${p.sesi}</span>
                                <span class="font-weight-bold text-dark text-sm mr-2">${g.nama}</span>
                                <small class="text-muted">(NIP: ${g.nip})</small>
                            </div>
                        `;
                    });
                } else {
                    html += `
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge badge-light border mr-2">${p.ruang} / ${p.sesi}</span>
                            <span class="text-muted text-sm font-italic">Belum ada pengawas ditugaskan</span>
                        </div>
                    `;
                }
            });
        } else {
            html = `
                <div class="alert alert-light border border-warning text-warning py-1 px-2 mb-0 text-sm">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Pengawas belum diatur untuk jadwal ini.
                    <a href="${base_url}cbtpengawas" class="text-primary font-weight-bold ml-1">Atur Pengawas</a>
                </div>
            `;
        }
        $('#container-pengawas').html(html);
    }

    // Render Summary Counters
    function renderSummary(sum) {
        $('#stat-total').text(sum.total);
        $('#stat-mengerjakan').text(sum.mengerjakan);
        $('#stat-selesai').text(sum.selesai);
        $('#stat-belum').text(sum.belum_mulai);
        $('#stat-reset').text(sum.perlu_reset);
        $('#section-summary-cards').removeClass('d-none');
    }

    // Render Tabel Siswa
    function renderTableSiswa(students) {
        const filterKeyword = $('#filter-search-siswa').val().toLowerCase().trim();
        let rows = '';

        if (!students || students.length === 0) {
            rows = `<tr><td colspan="10" class="text-center py-4 text-muted">Tidak ada siswa yang terdaftar di kelas ini.</td></tr>`;
            $('#tbody-siswa').html(rows);
            return;
        }

        let filteredCount = 0;
        students.forEach(function (s, idx) {
            // Filter pencarian client-side
            if (filterKeyword !== '') {
                const matchName = s.nama.toLowerCase().includes(filterKeyword);
                const matchNomor = s.nomor_peserta.toLowerCase().includes(filterKeyword);
                if (!matchName && !matchNomor) return;
            }
            filteredCount++;

            // Status Badge & Icon
            let statusBadge = '';
            if (s.status_code === 1) {
                statusBadge = `<span class="badge badge-success px-2 py-1"><span class="pulse-online mr-1"></span>Mengerjakan</span>`;
            } else if (s.status_code === 2) {
                statusBadge = `<span class="badge badge-primary px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Selesai</span>`;
            } else if (s.status_code === 3) {
                statusBadge = `<span class="badge badge-danger px-2 py-1"><span class="pulse-danger mr-1"></span>Perlu Reset</span>`;
            } else {
                statusBadge = `<span class="badge badge-secondary px-2 py-1">Belum Mulai</span>`;
            }

            // Progress Bar Soal
            const prog = s.progress;
            let progressHtml = `
                <div class="d-flex justify-content-between text-xs mb-1">
                    <span>${prog.terjawab} dari ${prog.total} Soal</span>
                    <span class="font-weight-bold">${prog.persen}%</span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar ${prog.persen === 100 ? 'bg-success' : 'bg-primary'}" style="width: ${prog.persen}%"></div>
                </div>
            `;

            // Tombol Aksi
            let actionButtons = `
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-warning btn-act-reset" data-id="${s.id_siswa}" data-nama="${s.nama}" title="Reset Login Siswa">
                        <i class="fas fa-unlock"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-act-finish" data-id="${s.id_siswa}" data-nama="${s.nama}" ${s.status_code === 1 ? '' : 'disabled'} title="Paksa Selesai">
                        <i class="fas fa-check-double"></i>
                    </button>
                    <a href="${base_url}cbtstatus/detail?siswa=${s.id_siswa}&jadwal=${$('#select-jadwal').val()}" target="_blank" class="btn btn-outline-info" title="Lihat Lembar Jawaban">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            `;

            rows += `
                <tr data-id="${s.id_siswa}" class="${s.status_code === 3 ? 'table-danger' : ''}">
                    <td class="text-center align-middle">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input check-item-siswa" id="chk-${s.id_siswa}" value="${s.id_siswa}">
                            <label class="custom-control-label" for="chk-${s.id_siswa}"></label>
                        </div>
                    </td>
                    <td class="text-center align-middle text-muted">${idx + 1}</td>
                    <td class="text-center align-middle font-weight-bold">${s.nomor_peserta}</td>
                    <td class="align-middle">
                        <div class="font-weight-bold text-dark">${s.nama}</div>
                        <small class="text-muted">NIS: ${s.nis}</small>
                        ${s.tab_switch_count > 0 ? `<span class="badge badge-warning text-xs ml-1" title="Terdeteksi pindah tab"><i class="fas fa-exclamation-circle mr-1"></i>${s.tab_switch_count}x Pindah Tab</span>` : ''}
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge badge-light border">${s.kode_ruang} / ${s.kode_sesi}</span>
                    </td>
                    <td class="text-center align-middle">${statusBadge}</td>
                    <td class="align-middle">${progressHtml}</td>
                    <td class="text-center align-middle">
                        <div class="text-xs font-weight-bold text-dark">Mulai: ${s.waktu_mulai}</div>
                        <div class="text-xs text-muted">Sisa: <b class="text-primary">${s.sisa_waktu}</b></div>
                    </td>
                    <td class="text-center align-middle text-xs">
                        <div class="text-truncate" style="max-width: 120px;" title="${s.user_agent}">${s.user_agent}</div>
                        <small class="text-muted">${s.ip_address}</small>
                    </td>
                    <td class="text-center align-middle">${actionButtons}</td>
                </tr>
            `;
        });

        if (filteredCount === 0) {
            rows = `<tr><td colspan="10" class="text-center py-4 text-muted">Tidak ada siswa yang cocok dengan pencarian "<b>${filterKeyword}</b>".</td></tr>`;
        }

        $('#tbody-siswa').html(rows);
        updateCheckboxState();
    }

    // Filter pencarian live pada tabel
    $('#filter-search-siswa').on('input', function () {
        if (currentRawData && currentRawData.students) {
            renderTableSiswa(currentRawData.students);
        }
    });

    // Checkbox All & Item
    $('#check-all-siswa').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('.check-item-siswa').prop('checked', isChecked);
        updateCheckboxState();
    });

    $(document).on('change', '.check-item-siswa', function () {
        updateCheckboxState();
    });

    function updateCheckboxState() {
        const total = $('.check-item-siswa').length;
        const checked = $('.check-item-siswa:checked').length;
        $('.selected-count').text(checked);

        if (checked > 0) {
            $('#btn-bulk-reset').prop('disabled', false);
            $('#btn-bulk-finish').prop('disabled', false);
        } else {
            $('#btn-bulk-reset').prop('disabled', true);
            $('#btn-bulk-finish').prop('disabled', true);
        }

        if (total > 0 && total === checked) {
            $('#check-all-siswa').prop('checked', true);
        } else {
            $('#check-all-siswa').prop('checked', false);
        }
    }

    // ===================================================================
    // AKSI 1-CLICK: RESET LOGIN SISWA
    // ===================================================================
    $(document).on('click', '.btn-act-reset', function () {
        const idSiswa = $(this).data('id');
        const namaSiswa = $(this).data('nama');
        const idJadwal = $('#select-jadwal').val();

        Swal.fire({
            title: 'Reset Login Siswa?',
            text: 'Apakah Anda yakin ingin me-reset status login ' + namaSiswa + '? Siswa akan diizinkan login kembali pada perangkat.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Reset Login',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: base_url + 'cbtlive/reset_login',
                    type: 'POST',
                    data: { id_siswa: idSiswa, id_jadwal: idJadwal },
                    dataType: 'json',
                    success: function (res) {
                        if (res && res.status) {
                            toastr.success(res.message);
                            fetchLiveData();
                        } else {
                            toastr.error(res.message || 'Gagal me-reset login');
                        }
                    }
                });
            }
        });
    });

    // ===================================================================
    // AKSI 1-CLICK: PAKSA SELESAI SISWA
    // ===================================================================
    $(document).on('click', '.btn-act-finish', function () {
        const idSiswa = $(this).data('id');
        const namaSiswa = $(this).data('nama');
        const idJadwal = $('#select-jadwal').val();

        Swal.fire({
            title: 'Paksa Selesai Ujian?',
            text: 'Ujian siswa ' + namaSiswa + ' akan dihentikan dan jawaban saat ini akan disimpan sebagai nilai akhir!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Paksa Selesai',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: base_url + 'cbtlive/force_finish',
                    type: 'POST',
                    data: { id_siswa: idSiswa, id_jadwal: idJadwal },
                    dataType: 'json',
                    success: function (res) {
                        if (res && res.status) {
                            toastr.success(res.message);
                            fetchLiveData();
                        } else {
                            toastr.error(res.message || 'Gagal menyelesaikan ujian');
                        }
                    }
                });
            }
        });
    });

    // ===================================================================
    // BULK ACTION: RESET LOGIN TERPILIH
    // ===================================================================
    $('#btn-bulk-reset').on('click', function () {
        const selected = [];
        $('.check-item-siswa:checked').each(function () {
            selected.push($(this).val());
        });
        const idJadwal = $('#select-jadwal').val();

        if (selected.length === 0) return;

        Swal.fire({
            title: 'Reset Login ' + selected.length + ' Siswa?',
            text: 'Semua siswa yang dipilih akan diizinkan login kembali pada perangkatnya.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Reset Terpilih',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: base_url + 'cbtlive/bulk_action',
                    type: 'POST',
                    data: { action: 'reset', ids: selected, id_jadwal: idJadwal },
                    dataType: 'json',
                    success: function (res) {
                        if (res && res.status) {
                            toastr.success(res.message);
                            fetchLiveData();
                        } else {
                            toastr.error(res.message || 'Gagal memproses bulk reset');
                        }
                    }
                });
            }
        });
    });

    // ===================================================================
    // BULK ACTION: PAKSA SELESAI TERPILIH
    // ===================================================================
    $('#btn-bulk-finish').on('click', function () {
        const selected = [];
        $('.check-item-siswa:checked').each(function () {
            selected.push($(this).val());
        });
        const idJadwal = $('#select-jadwal').val();

        if (selected.length === 0) return;

        Swal.fire({
            title: 'Paksa Selesai ' + selected.length + ' Siswa?',
            text: 'Semua siswa terpilih akan diselesaikan ujiannya dan nilai mereka akan langsung diproses.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Selesaikan Terpilih',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: base_url + 'cbtlive/bulk_action',
                    type: 'POST',
                    data: { action: 'finish', ids: selected, id_jadwal: idJadwal },
                    dataType: 'json',
                    success: function (res) {
                        if (res && res.status) {
                            toastr.success(res.message);
                            fetchLiveData();
                        } else {
                            toastr.error(res.message || 'Gagal memproses paksa selesai');
                        }
                    }
                });
            }
        });
    });

    // ===================================================================
    // QUICK RESET SEMUA SISWA TERKUNCI (STATUS = 3)
    // ===================================================================
    $('#btn-quick-reset-all').on('click', function () {
        if (!currentRawData || !currentRawData.students) return;
        const lockedIds = [];
        currentRawData.students.forEach(function (s) {
            if (s.status_code === 3) {
                lockedIds.push(s.id_siswa);
            }
        });

        if (lockedIds.length === 0) {
            toastr.info('Tidak ada siswa yang sedang berstatus terkunci / perlu reset saat ini.');
            return;
        }

        const idJadwal = $('#select-jadwal').val();
        Swal.fire({
            title: 'Reset ' + lockedIds.length + ' Siswa Terkunci?',
            text: 'Semua siswa yang terkunci atau minta izin reset login akan langsung dibuka kuncinya.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Buka Kunci Semua',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: base_url + 'cbtlive/bulk_action',
                    type: 'POST',
                    data: { action: 'reset', ids: lockedIds, id_jadwal: idJadwal },
                    dataType: 'json',
                    success: function (res) {
                        if (res && res.status) {
                            toastr.success(res.message);
                            fetchLiveData();
                        }
                    }
                });
            }
        });
    });
});
</script>

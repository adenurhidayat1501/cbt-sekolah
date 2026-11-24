<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>

<div class="content-wrapper bg-light">
    <section class="content-header pb-2">
        <div class="container-fluid">
            <div class="row align-items-center mb-2">
                <div class="col-sm-7">
                    <h1 class="font-weight-bold text-dark m-0" style="font-size: 1.55rem;">
                        <i class="fas fa-camera text-info mr-2"></i><?= $judul ?>
                    </h1>
                    <small class="text-muted">Arsip bukti foto kamera depan siswa dari Saduci Exambro — terkelompok per mapel &amp; siswa.</small>
                </div>
                <div class="col-sm-5">
                    <ol class="breadcrumb float-sm-right bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>"><i class="fas fa-home mr-1"></i>Beranda</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('cbtlive') ?>">Live Monitor</a></li>
                        <li class="breadcrumb-item active">Bukti Foto</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <!-- Ringkasan Statistik Arsip Foto -->
            <div class="row mb-3">
                <div class="col-6 col-md-3 mb-2">
                    <div class="card bg-white border my-shadow mb-0 p-3 h-100">
                        <span class="text-xs font-weight-bold text-muted text-uppercase">Mapel / Siswa Berfoto</span>
                        <h4 class="font-weight-bold text-dark m-0 mt-1">
                            <span id="stat-mapel">0</span> <small class="text-muted text-xs">Mapel</small>
                            <span class="text-muted mx-1">/</span>
                            <span id="stat-students" class="text-primary">0</span> <small class="text-muted text-xs">Siswa</small>
                        </h4>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="card bg-white border border-info my-shadow mb-0 p-3 h-100">
                        <span class="text-xs font-weight-bold text-info text-uppercase">Total Bukti Foto</span>
                        <h4 class="font-weight-bold text-info m-0 mt-1" id="stat-photos">0</h4>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="card bg-white border border-danger my-shadow mb-0 p-3 h-100">
                        <span class="text-xs font-weight-bold text-danger text-uppercase">Foto Bukti Pelanggaran</span>
                        <h4 class="font-weight-bold text-danger m-0 mt-1" id="stat-violations">0</h4>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="card bg-white border border-success my-shadow mb-0 p-3 h-100">
                        <span class="text-xs font-weight-bold text-success text-uppercase">Penyimpanan Terpakai</span>
                        <h4 class="font-weight-bold text-success m-0 mt-1">
                            <span id="stat-size-mb">0</span> <small class="text-xs">MB</small>
                        </h4>
                        <small class="text-muted text-xs">Sisa Disk VPS: <b id="stat-disk-free">-</b> GB</small>
                    </div>
                </div>
            </div>

            <!-- Panel Filter Lengkap -->
            <div class="card card-outline card-info my-shadow mb-3">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-12 col-md-3 mb-2">
                            <label class="text-xs font-weight-bold text-uppercase text-muted mb-1">
                                <i class="fas fa-book text-primary mr-1"></i>Filter Mapel / Jadwal
                            </label>
                            <select id="filter-jadwal" class="form-control form-control-sm select2" style="width:100%;">
                                <option value="">-- Semua Mapel --</option>
                                <?php foreach ($jadwals as $j): ?>
                                    <option value="<?= $j->id_jadwal ?>">
                                        <?= htmlspecialchars($j->nama_mapel ?: $j->bank_kode) ?> (<?= htmlspecialchars($j->bank_kode) ?>) - <?= date('d/m/Y', strtotime($j->tgl_mulai)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-2 mb-2">
                            <label class="text-xs font-weight-bold text-uppercase text-muted mb-1">
                                <i class="fas fa-school text-info mr-1"></i>Filter Kelas
                            </label>
                            <select id="filter-kelas" class="form-control form-control-sm select2" style="width:100%;">
                                <option value="">-- Semua Kelas --</option>
                                <?php foreach ($kelases as $kid => $knama): ?>
                                    <option value="<?= $kid ?>"><?= htmlspecialchars($knama) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-2 mb-2">
                            <label class="text-xs font-weight-bold text-uppercase text-muted mb-1">
                                <i class="fas fa-filter text-warning mr-1"></i>Tipe Bukti Foto
                            </label>
                            <select id="filter-reason" class="form-control form-control-sm">
                                <option value="all">Semua Tipe</option>
                                <option value="violation">🚨 Pelanggaran Saja</option>
                                <option value="exam_start">🟢 Mulai Ujian</option>
                                <option value="periodic">📸 Berkala</option>
                                <option value="proctor_request">👁️ Diminta Pengawas</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-3 mb-2">
                            <label class="text-xs font-weight-bold text-uppercase text-muted mb-1">
                                <i class="fas fa-search text-secondary mr-1"></i>Cari Siswa / NIS / Mapel
                            </label>
                            <input type="text" id="filter-search" class="form-control form-control-sm" placeholder="Ketik nama siswa atau NIS...">
                        </div>

                        <div class="col-12 col-md-2 mb-2 d-flex flex-column justify-content-end">
                            <button type="button" id="btn-refresh-foto" class="btn btn-sm btn-primary btn-block">
                                <i class="fas fa-sync-alt mr-1"></i>Muat Ulang
                            </button>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 pt-2 border-top">
                        <div class="btn-group btn-group-sm mb-1 mb-md-0" role="group">
                            <button type="button" class="btn btn-info btn-view-mode active" data-mode="students">
                                <i class="fas fa-folder-open mr-1"></i>Kelompok per Siswa
                            </button>
                            <button type="button" class="btn btn-outline-info btn-view-mode" data-mode="grid">
                                <i class="fas fa-th mr-1"></i>Semua Foto (Grid)
                            </button>
                        </div>

                        <div class="d-flex flex-wrap align-items-center">
                            <a href="#" id="btn-zip-mapel" class="btn btn-sm btn-success mr-2 mb-1 mb-md-0 disabled" target="_blank">
                                <i class="fas fa-file-archive mr-1"></i>Unduh ZIP Mapel Terpilih
                            </a>
                            <?php if (!empty($is_admin)): ?>
                            <button type="button" id="btn-delete-mapel" class="btn btn-sm btn-outline-danger mb-1 mb-md-0" disabled>
                                <i class="fas fa-trash-alt mr-1"></i>Hapus Arsip Mapel
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Status Snapshot -->
            <?php if (empty($exambro_setting->snapshot_enabled)): ?>
            <div class="alert alert-warning py-2 px-3 mb-3 text-sm d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <b>Fitur Auto-Snapshot Kamera Depan saat ini OFF.</b> Nyalakan di menu Live Monitor Ujian agar HP siswa mulai memotret otomatis saat ujian.
                </span>
                <a href="<?= base_url('cbtlive') ?>" class="btn btn-xs btn-dark ml-2">Buka Pengaturan di /cbtlive</a>
            </div>
            <?php endif; ?>

            <!-- Kontainer Galeri -->
            <div id="foto-loading" class="text-center py-5 d-none">
                <div class="spinner-border text-info" role="status"></div>
                <p class="text-muted mt-2 mb-0">Memuat arsip bukti foto ujian...</p>
            </div>

            <div id="foto-empty" class="card my-shadow p-5 text-center text-muted d-none">
                <i class="fas fa-camera-retro fa-3x mb-3 text-black-50"></i>
                <h5 class="font-weight-bold">Belum Ada Bukti Foto yang Cocok</h5>
                <p class="mb-0 text-sm">Coba ubah filter di atas, atau pastikan <b>Auto-Snapshot Kamera Depan</b> sudah diaktifkan pada menu Live Monitor Ujian.</p>
            </div>

            <!-- Mode 1: Kelompok per Siswa -->
            <div id="view-students" class="row"></div>

            <!-- Mode 2: Semua Foto Grid -->
            <div id="view-grid" class="row d-none"></div>

        </div>
    </section>
</div>

<script>
$(document).ready(function () {
    if (typeof ajaxcsrf === 'function') {
        ajaxcsrf();
    }

    $('#filter-jadwal, #filter-kelas').select2({ theme: 'bootstrap4' });

    let currentMode = 'students';
    let lastData = null;
    let searchTimer = null;

    const reasonBadge = {
        violation: '<span class="badge badge-danger">🚨 Pelanggaran</span>',
        exam_start: '<span class="badge badge-success">Mulai Ujian</span>',
        proctor_request: '<span class="badge badge-info">Diminta Pengawas</span>',
        periodic: '<span class="badge badge-secondary">Berkala</span>'
    };

    function syncMapelButtons() {
        const idJadwal = $('#filter-jadwal').val();
        if (idJadwal) {
            $('#btn-zip-mapel')
                .removeClass('disabled')
                .attr('href', base_url + 'exambro/download_snapshots_zip?id_jadwal=' + encodeURIComponent(idJadwal));
            $('#btn-delete-mapel').prop('disabled', false);
        } else {
            $('#btn-zip-mapel').addClass('disabled').attr('href', '#');
            $('#btn-delete-mapel').prop('disabled', true);
        }
    }

    function loadSnapshots() {
        syncMapelButtons();
        $('#foto-loading').removeClass('d-none');
        $('#foto-empty').addClass('d-none');
        $('#view-students, #view-grid').empty();

        $.getJSON(base_url + 'cbtfoto/get_data', {
            id_jadwal: $('#filter-jadwal').val(),
            id_kelas: $('#filter-kelas').val(),
            reason: $('#filter-reason').val(),
            search: $('#filter-search').val()
        }, function (res) {
            $('#foto-loading').addClass('d-none');
            if (!res || !res.status) return;
            lastData = res;

            $('#stat-mapel').text(res.summary.total_mapel);
            $('#stat-students').text(res.summary.total_students);
            $('#stat-photos').text(res.summary.total_photos);
            $('#stat-violations').text(res.summary.total_violations);
            $('#stat-size-mb').text(res.summary.total_size_mb);
            $('#stat-disk-free').text(res.summary.disk_free_gb);

            renderActiveView();
        }).fail(function () {
            $('#foto-loading').addClass('d-none');
            toastr.error('Gagal memuat data bukti foto dari server.');
        });
    }

    function renderActiveView() {
        if (!lastData) return;

        if (currentMode === 'students') {
            $('#view-grid').addClass('d-none');
            $('#view-students').removeClass('d-none').empty();

            if (!lastData.students || lastData.students.length === 0) {
                $('#foto-empty').removeClass('d-none');
                return;
            }
            $('#foto-empty').addClass('d-none');

            let html = '';
            lastData.students.forEach(function (st) {
                const violBadge = st.violation_count > 0
                    ? `<span class="badge badge-danger ml-1">🚨 ${st.violation_count} Pelanggaran</span>`
                    : '';
                html += `
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3">
                    <div class="card my-shadow h-100 border ${st.violation_count > 0 ? 'border-danger' : ''}">
                        <div style="position:relative;height:165px;background:#0f172a;overflow:hidden;cursor:pointer;"
                             class="btn-open-student-gallery"
                             data-jadwal="${st.id_jadwal}" data-siswa="${st.id_siswa}"
                             data-nama="${st.nama_siswa}" data-mapel="${st.nama_mapel}">
                            <img src="${st.latest_url}" style="width:100%;height:100%;object-fit:cover;" loading="lazy" alt="${st.nama_siswa}">
                            <span class="badge badge-dark" style="position:absolute;top:8px;left:8px;opacity:0.9;">
                                <i class="fas fa-images mr-1"></i>${st.photo_count} Foto (${st.size_kb} KB)
                            </span>
                            ${st.violation_count > 0 ? `<span class="badge badge-danger" style="position:absolute;top:8px;right:8px;">🚨 ${st.violation_count}</span>` : ''}
                        </div>
                        <div class="card-body p-2 d-flex flex-column">
                            <div class="font-weight-bold text-dark text-truncate" title="${st.nama_siswa}">${st.nama_siswa}</div>
                            <div class="text-xs text-muted mb-1">NIS: ${st.nis} • Kelas: <b>${st.nama_kelas}</b></div>
                            <div class="mb-2">
                                <span class="badge badge-primary text-xs">${st.nama_mapel}</span>
                                ${violBadge}
                            </div>
                            <small class="text-muted text-xs mb-2"><i class="far fa-clock mr-1"></i>Terakhir: ${st.latest_time}</small>
                            <div class="mt-auto d-flex justify-content-between">
                                <button type="button" class="btn btn-xs btn-info flex-fill mr-1 btn-open-student-gallery"
                                        data-jadwal="${st.id_jadwal}" data-siswa="${st.id_siswa}"
                                        data-nama="${st.nama_siswa}" data-mapel="${st.nama_mapel}">
                                    <i class="fas fa-eye mr-1"></i>Buka Galeri
                                </button>
                                <a href="${base_url}exambro/download_snapshots_zip?id_jadwal=${st.id_jadwal}&id_siswa=${st.id_siswa}"
                                   target="_blank" class="btn btn-xs btn-outline-success" title="Unduh ZIP bukti siswa ini">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>`;
            });
            $('#view-students').html(html);
        } else {
            $('#view-students').addClass('d-none');
            $('#view-grid').removeClass('d-none').empty();

            if (!lastData.photos || lastData.photos.length === 0) {
                $('#foto-empty').removeClass('d-none');
                return;
            }
            $('#foto-empty').addClass('d-none');

            let html = '';
            lastData.photos.forEach(function (p) {
                html += `
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3">
                    <div class="card my-shadow h-100 border ${p.reason === 'violation' ? 'border-danger' : ''}">
                        <a href="${p.url}" target="_blank" title="Klik untuk perbesar foto">
                            <img src="${p.url}" style="width:100%;height:125px;object-fit:cover;display:block;" loading="lazy">
                        </a>
                        <div class="card-body p-2 text-xs">
                            <div class="font-weight-bold text-truncate" title="${p.nama_siswa}">${p.nama_siswa}</div>
                            <div class="text-muted text-truncate">${p.nama_kelas} • ${p.nama_mapel}</div>
                            <div class="mt-1">${reasonBadge[p.reason] || reasonBadge.periodic}</div>
                            <div class="text-muted mt-1">${p.datetime}</div>
                        </div>
                    </div>
                </div>`;
            });
            $('#view-grid').html(html);
        }
    }

    // Ganti mode tampilan
    $('.btn-view-mode').on('click', function () {
        $('.btn-view-mode').removeClass('active btn-info').addClass('btn-outline-info');
        $(this).removeClass('btn-outline-info').addClass('active btn-info');
        currentMode = $(this).data('mode');
        renderActiveView();
    });

    // Event Filter
    $('#filter-jadwal, #filter-kelas, #filter-reason').on('change', loadSnapshots);
    $('#btn-refresh-foto').on('click', loadSnapshots);
    $('#filter-search').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadSnapshots, 350);
    });

    // Buka Modal Galeri Lengkap 1 Siswa
    $(document).on('click', '.btn-open-student-gallery', function () {
        const idJadwal = $(this).data('jadwal');
        const idSiswa = $(this).data('siswa');
        const nama = $(this).data('nama');
        const mapel = $(this).data('mapel');

        Swal.fire({
            title: 'Memuat Bukti Foto...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.getJSON(base_url + 'exambro/get_student_gallery', { id_jadwal: idJadwal, id_siswa: idSiswa }, function (res) {
            if (!res || !res.status || !res.photos || res.photos.length === 0) {
                Swal.fire('Info', 'Tidak ada foto ditemukan.', 'info');
                return;
            }
            let gridHtml = '<div class="text-left mb-2 d-flex justify-content-between align-items-center">'
                + '<small class="text-muted">Mapel: <b>' + mapel + '</b> • Total <b>' + res.total + ' foto</b></small>'
                + '<a href="' + base_url + 'exambro/download_snapshots_zip?id_jadwal=' + idJadwal + '&id_siswa=' + idSiswa + '" target="_blank" class="btn btn-xs btn-success"><i class="fas fa-file-archive mr-1"></i>Unduh ZIP Siswa</a>'
                + '</div>'
                + '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;max-height:440px;overflow-y:auto;padding:4px;">';

            res.photos.forEach(function (p) {
                gridHtml += '<div style="border:1px solid #dee2e6;border-radius:8px;overflow:hidden;background:#f8f9fa;text-align:center;">'
                    + '<a href="' + p.url + '" target="_blank" title="Klik untuk perbesar">'
                    + '<img src="' + p.url + '" style="width:100%;height:115px;object-fit:cover;display:block;" loading="lazy" />'
                    + '</a>'
                    + '<div style="padding:5px;font-size:11px;">'
                    + (reasonBadge[p.reason] || reasonBadge.periodic)
                    + '<div class="text-muted mt-1">' + p.datetime + ' (' + p.size_kb + ' KB)</div>'
                    + '</div></div>';
            });
            gridHtml += '</div>';

            Swal.fire({
                title: '📸 ' + nama,
                html: gridHtml,
                width: 760,
                confirmButtonText: 'Tutup'
            });
        });
    });

    // Hapus Arsip Mapel (Admin Only)
    $('#btn-delete-mapel').on('click', function () {
        const idJadwal = $('#filter-jadwal').val();
        const mapelText = $('#filter-jadwal option:selected').text().trim();
        if (!idJadwal) return;

        Swal.fire({
            title: 'Hapus Semua Bukti Foto Mapel Ini?',
            html: 'Seluruh foto pada <b>' + mapelText + '</b> akan dihapus permanen dari server.<br><small class="text-danger">Pastikan Anda sudah mengunduh arsip ZIP-nya terlebih dahulu!</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Ya, Hapus Permanen',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: base_url + 'cbtfoto/delete_mapel_archive',
                    type: 'POST',
                    data: { id_jadwal: idJadwal },
                    dataType: 'json',
                    success: function (res) {
                        if (res && res.status) {
                            toastr.success(res.message);
                            loadSnapshots();
                        } else {
                            toastr.error(res?.message || 'Gagal menghapus arsip.');
                        }
                    }
                });
            }
        });
    });

    // Muat awal
    loadSnapshots();
});
</script>

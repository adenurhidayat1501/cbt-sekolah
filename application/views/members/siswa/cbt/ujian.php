<?php
/**
 * View Ujian Siswa - SADUCI CBT (SMP Negeri Satu Atap 2 Cipatujah)
 *
 * @var object $siswa
 * @var object $jadwal
 * @var object $elapsed
 * @var object $user
 * @var object $setting
 */
defined('BASEPATH') or exit('No direct script access allowed');
?>
<div class="content-wrapper" style="margin-top: -1px;">
    <div class="sticky">
    </div>
    <section class="content overlap pt-3">
        <div class="container-fluid mb-3">
            <div class="row align-items-center">
                <div class="col-sm-6 col-12 mb-2 mb-sm-0">
                    <div class="exam-brand-badge">
                        <img src="<?= base_url() ?>assets/img/saduci_logo.png" width="48" height="48" alt="SADUCI CBT">
                        <div>
                            <h4 class="text-white mb-0 font-weight-bold" style="letter-spacing: -0.02em;">SADUCI CBT</h4>
                            <small class="text-white-50 text-uppercase font-weight-bold" style="letter-spacing: 0.08em; font-size: 11px;">SMPN SATU ATAP 2 CIPATUJAH</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-12 text-sm-right">
                    <div class="exam-student-badge text-left">
                        <img src="<?= base_url() ?>assets/app/img/ic_graduate.png" width="38" height="38" class="avatar" alt="Siswa">
                        <div style="line-height: 1.25;">
                            <div class="text-white font-weight-bold" style="font-size: 14px;"><?= $siswa->nama ?></div>
                            <div class="text-white-50" style="font-size: 12px;"><?= $siswa->nis ?> &bull; <?= $siswa->nama_kelas ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container"
             style="-moz-user-select: none; -webkit-user-select: none; -ms-user-select:none; user-select:none;-o-user-select:none;"
             unselectable="on">
            <div class="row">
                <div class="col-12">
                    <div class="card my-shadow">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between p-3">
                            <div class="d-flex align-items-center mb-2 mb-sm-0">
                                <span class="text-muted font-weight-bold mr-2 text-uppercase" style="font-size: 13px; letter-spacing: 0.05em;">Soal No.</span>
                                <div id="nomor-soal" class="btn no-hover"></div>
                            </div>
                            <div class="d-flex align-items-center">
                                <button class="btn btn-outline-danger btn-oval-sm no-hover mr-2">
                                    <i class="far fa-clock mr-1"></i>
                                    <span class='mr-2 d-none d-md-inline-block'><b>Sisa Waktu</b></span>
                                    <span id="timer" class="text-bold">00:00:00</span>
                                </button>
                                <button data-toggle="modal" data-target="#daftarModal"
                                        class="btn btn-primary btn-oval-sm">
                                    <i class="fa fa-th mr-1"></i>
                                    <span class="d-none d-md-inline-block"><b>Daftar Soal</b></span>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center mb-2 mb-sm-0">
                                    <div class="btn-group btn-group-sm mr-2" role="group" aria-label="Font scale">
                                        <button type="button" class="btn btn-outline-primary" id="btn-font-sm" title="Ukuran Font Kecil">A-</button>
                                        <button type="button" class="btn btn-outline-primary active" id="btn-font-md" title="Ukuran Font Standar">A</button>
                                        <button type="button" class="btn btn-outline-primary" id="btn-font-lg" title="Ukuran Font Besar">A+</button>
                                    </div>
                                    <button class="btn btn-outline-secondary btn-sm rounded-circle" id="btn-toggle-dark" title="Mode Gelap / Terang" style="width: 32px; height: 32px; padding: 0;">
                                        <i class="fas fa-moon"></i>
                                    </button>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span id="network-pill" class="network-status-pill network-online">
                                        <i class="fas fa-wifi mr-1"></i> <span id="network-text">Online</span>
                                    </span>
                                </div>
                            </div>
                            <div class="exam-container-relative p-3 mb-3 exam-question-box" style="border: 1px solid var(--cbt-border); border-radius: var(--cbt-radius-lg); background: #ffffff;">
                                <div id="watermark-layer" class="exam-watermark-overlay"></div>
                                <div class="konten-soal-jawab font-size-md" style="position: relative; z-index: 2;">
                                    <div class="row p-2 mb-4 ml-1">
                                        <div id="konten-soal" class="table-responsive"></div>
                                    </div>
                                    <?= form_open('jawab', array('id' => 'jawab')) ?>
                                    <input type="hidden" name="siswa" value="<?= $siswa->id_siswa ?>">
                                    <input type="hidden" name="jadwal" value="<?= $jadwal->id_jadwal ?>">
                                    <input type="hidden" name="bank" value="<?= $jadwal->id_bank ?>">
                                    <div class="row p-3">
                                        <div id="konten-jawaban" class="col-12">
                                        </div>
                                    </div>
                                    <?= form_close() ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <button class="btn btn-oval-sm" id="prev" onclick="prevSoal()">
                                        <i class="fa fa-arrow-left mr-1"></i>
                                        <span class="ml-1 d-none d-md-inline-block"><b>Soal Sebelumnya</b></span>
                                    </button>
                                </div>
                                <div class="d-flex align-items-center">
                                    <button class="btn btn-oval-sm btn-danger btn-disabled d-none mr-2" id="timer-selesai" disabled></button>
                                    <button class="btn btn-primary btn-oval-sm" id="next" onclick="nextSoal()">
                                        <span id="text-next" class="mr-1 d-none d-md-inline-block"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="overlay" id="loading">
                            <div class="spinner-grow text-primary"></div>
                            <div class="pl-3 font-weight-bold">MEMUAT SOAL...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="daftarModal" tabindex="-1" role="dialog" aria-labelledby="daftarLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="daftarLabel">Daftar Nomor Soal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container-fluid" id="konten-modal">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">TUTUP</button>
            </div>
        </div>
    </div>
</div>

<?= form_open('', array('id' => 'up')) ?>
<input type="hidden" name="siswa" value="<?= $siswa->id_siswa ?>">
<input type="hidden" name="jadwal" value="<?= $jadwal->id_jadwal ?>">
<input type="hidden" name="bank" value="<?= $jadwal->id_bank ?>">
<?= form_close() ?>

<script src="<?= base_url() ?>/assets/app/js/redirect.js"></script>
<script src="<?= base_url() ?>/assets/app/js/linker-list.js"></script>
<script src="<?= base_url() ?>/assets/plugins/element-queries/ElementQueries.js"></script>
<script src="<?= base_url() ?>/assets/plugins/element-queries/ResizeSensor.js"></script>

<script>
    var elem = document.documentElement;
    history.pushState(null, null, '<?php echo $_SERVER["REQUEST_URI"]; ?>');
    window.addEventListener('popstate', function (event) {
        loadSoalNomor(1);
    });
    const infoJadwal = JSON.parse(JSON.stringify(<?= json_encode($jadwal) ?>));
    let nomorSoal = 0;
    let idSoal, idSoalSiswa, jenisSoal, modelSoal, typeSoal;
    let jawabanSiswa, jawabanBaru = null, jsonJawaban;
    let nav = 0;
    let soalTerjawab = 0, soalTotal = 0;
    let timerOut;
    let timerSelesai;
    //const durasi = JSON.parse(JSON.stringify(<?= json_encode($elapsed) ?>));
    let elapsed = '0';
    let h, m, s;
    var message = "Jangan menggunakan klik kanan!";

    let tick = 0;
    const _second = 1000,
        _minute = _second * 60,
        _hour = _minute * 60,
        _day = _hour * 24;
    const durasiUjian = Number(infoJadwal.durasi_ujian);
    let dif;
    let fieldLinks;
    let zoomClicked = 3;
    var arrSize = [];

    $(document).ready(function () {
        $(document).keydown(function (event) {
            //console.log('press', event.keyCode);
            var charCode = event.charCode || event.keyCode || event.which;
            if (charCode == 27 || charCode == 91 || charCode == 92) {
                return false;
            }
        });

        document.onmousedown = rtclickcheck;
        swal.fire({
            title: 'Peraturan Ujian',
            html: 'Kerjakan soal dengan serius,<br>jangan nyontek!',
            // showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Iya',
            allowOutsideClick: false
        }).then((result) => {
            if (result.value) {
                openFullscreen();
            }
        });

        $('#jawab').on('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            var siswa = $('#up').find('input[name="siswa"]').val();
            var bank = $('#up').find('input[name="bank"]').val();

            let formData = new FormData($('#jawab')[0]);
            formData.append('siswa', siswa);
            formData.append('bank', bank);
            formData.append('data', JSON.stringify(jsonJawaban));

            // Backup answer to browser LocalStorage immediately (Dual Storage Resilience)
            saveAnswerLocally(idSoal, jsonJawaban);

            $.ajax({
                url: base_url + 'siswa/savejawaban',
                method: 'POST',
                processData: false,
                contentType: false,
                data: formData,
                success: function (response) {
                    soalTerjawab = response.soal_terjawab;
                    removeAnswerFromOfflineQueue(idSoal);
                    setOnlineStatus(true);
                    loadSoalNomor(nav);
                },
                error: function (xhr, error, status) {
                    // Queue for auto-sync without halting or locking the student screen
                    queueAnswerOffline(idSoal, jsonJawaban, siswa, bank);
                    setOnlineStatus(false);
                    loadSoalNomor(nav);
                }
            });
        });

        // Font scale buttons (A-, A, A+)
        $('#btn-font-sm').click(function () {
            $('.konten-soal-jawab').removeClass('font-size-md font-size-lg').addClass('font-size-sm');
            $('.btn-group button').removeClass('active');
            $(this).addClass('active');
            localStorage.setItem('saduci_font_size', 'sm');
        });
        $('#btn-font-md').click(function () {
            $('.konten-soal-jawab').removeClass('font-size-sm font-size-lg').addClass('font-size-md');
            $('.btn-group button').removeClass('active');
            $(this).addClass('active');
            localStorage.setItem('saduci_font_size', 'md');
        });
        $('#btn-font-lg').click(function () {
            $('.konten-soal-jawab').removeClass('font-size-sm font-size-md').addClass('font-size-lg');
            $('.btn-group button').removeClass('active');
            $(this).addClass('active');
            localStorage.setItem('saduci_font_size', 'lg');
        });

        // Restore font size preference if saved
        var savedFontSize = localStorage.getItem('saduci_font_size');
        if (savedFontSize === 'sm') $('#btn-font-sm').click();
        else if (savedFontSize === 'lg') $('#btn-font-lg').click();

        // Dark mode toggle
        $('#btn-toggle-dark').click(function () {
            $('body').toggleClass('cbt-dark-mode');
            var isDark = $('body').hasClass('cbt-dark-mode');
            $(this).html(isDark ? '<i class="fas fa-sun text-warning"></i>' : '<i class="fas fa-moon"></i>');
            localStorage.setItem('saduci_dark_mode', isDark ? '1' : '0');
        });
        if (localStorage.getItem('saduci_dark_mode') === '1') {
            $('body').addClass('cbt-dark-mode');
            $('#btn-toggle-dark').html('<i class="fas fa-sun text-warning"></i>');
        }

        loadSoalNomor(1);
    });

    function loadSoalNomor(nomor) {
        if (nomor == nomorSoal) {
            return;
        }
        var dataPost = $('#up').serialize() + '&nomor=' + nomor + '&timer=' + $('#timer').text() + '&elapsed=' + elapsed;
        //console.log('res', dataPost);
        if (soalTotal === 0 || nomor <= parseInt(soalTotal)) {
            $.ajax({
                type: 'POST',
                url: base_url + 'siswa/loadnomorsoal',
                data: dataPost,
                success: function (data) {
                    console.log('load soal', data);
                    $('#loading').addClass('d-none');
                    setKonten(data);
                }, error: function (xhr, error, status) {
                    showDangerToast('ERROR!');
                    console.log(xhr.responseText);
                }
            });
        } else {
            selesai();
        }
    }

    function loadSoal(datas) {
        $('#daftarModal').modal('hide').data('bs.modal', null);
        $('#daftarModal').on('hidden', function () {
            $(this).data('modal', null);
        });

        nav = $(datas).data('nomorsoal');
        var jwb1 = jawabanSiswa;
        var jwb2 = jawabanBaru;
        if ($.isArray(jwb1) || jwb1 instanceof jQuery) {
            jwb1 = JSON.stringify(jwb1)
        }
        if (jwb2 != null && ($.isArray(jwb2) || jwb2 instanceof jQuery)) {
            jwb2 = JSON.stringify(jwb2)
        }

        if (jawabanBaru != null && jwb1 !== jwb2) {
            $('#jawab').submit();
        } else {
            loadSoalNomor(nav);
        }
    }

    function setKonten(data) {
        //console.log('max_jawaban', data.max_jawaban);
        idSoal = data.soal_id;
        idSoalSiswa = data.soal_siswa_id;
        nomorSoal = parseInt(data.soal_nomor);
        jenisSoal = data.soal_jenis;
        soalTerjawab = data.soal_terjawab;
        soalTotal = data.soal_total;

        jsonJawaban = {};
        jawabanBaru = null;
        jawabanSiswa = data.soal_jawaban_siswa != null ? data.soal_jawaban_siswa : '';
        
        // Recover from LocalStorage cache if server answer is currently empty
        var localCached = getLocalAnswer(idSoal);
        if ((!jawabanSiswa || ($.isArray(jawabanSiswa) && jawabanSiswa.length === 0) || jawabanSiswa === '') && localCached) {
            if (localCached.jawaban_siswa) {
                jawabanSiswa = localCached.jawaban_siswa;
            }
        }
        if ($.isArray(jawabanSiswa)) jawabanSiswa.sort();

        if (nomorSoal === 1) {
            $('#prev').attr('disabled', 'disabled');
        } else {
            $('#prev').removeAttr('disabled');
        }

        $('#nomor-soal').html(nomorSoal);
        $('#konten-soal').html(data.soal_soal);
        var jenis = data.soal_jenis;
        var html = '';
        if (jenis == "1") {
            $.each(data.soal_opsi, function (key, opsis) {
                if (opsis.valAlias != "") {
                    var isSelected = (opsis.checked && opsis.checked.trim() !== '') ? ' selected-card' : '';
                    html += '<label class="container-jawaban font-weight-normal' + isSelected + '">' + opsis.opsi +
                        '<input type="radio"' +
                        ' name="jawaban"' +
                        ' value="' + opsis.value.toUpperCase() + '"' +
                        ' data-jawabansiswa="' + opsis.value.toUpperCase() + '"' +
                        ' data-jawabanalias="' + opsis.valAlias.toUpperCase() + '"' +
                        ' onclick="submitJawaban(this)" ' + opsis.checked + '>' +
                        '<span class="checkmark shadow text-center align-middle">' + opsis.valAlias.toUpperCase() + '</span>' +
                        '</label>';
                }
            });
            $('#konten-jawaban').html(html);
        } else if (jenis == "2") {
            $.each(data.soal_opsi, function (key, opsis) {
                html += '<div class="custom-control custom-checkbox checkbox-xl">' +
                    '<input type="checkbox" class="check2 custom-control-input"' +
                    'id="check'+key+'"' +
                    ' name="jawaban"' +
                    ' value="' + opsis.value.toUpperCase() + '"' +
                    ' data-max="' + data.max_jawaban[0] + '"' +
                    ' data-jawabansiswa="' + opsis.value.toUpperCase() + '"' +
                    ' onclick="submitJawaban(this)" ' + opsis.checked + '>' +
                    '<label class="custom-control-label font-weight-normal" for="check'+key+'">'
                    + opsis.opsi +'</label>' +
                    '</div>'
            });
            $('#konten-jawaban').html(html);
        } else if (jenis == "3") {
            modelSoal = data.soal_opsi.model;
            typeSoal = data.soal_opsi.type;
            let konten = $('#konten-jawaban')
            konten.html('');

            const dataJawab = data.soal_opsi
            const copy = $.extend(true, {}, dataJawab);
            //console.log('test', copy)

            let arrData = [copy.tabel[0]]
            if (Array.isArray(copy.tbody)) {
                for (let i = 0; i < copy.tbody.length; i++) {
                    let val = copy.tbody[i]
                    for (let j = 0; j < val.length; j++) {
                        if (j === 0) val[j] = copy.tabel[i+1][0]
                    }
                    arrData.push(val)
                }
            } else {
                for (let i = 0; i < copy.tabel.length; i++) {
                    let val = copy.tbody[i]
                    if (val) {
                        for (let j = 0; j < val.length; j++) {
                            if (j === 0) val[j] = copy.tabel[i][0]
                        }
                        arrData.push(val)
                    }
                }
            }

            let keys = 0
            let dataMax = {}
            $.each(data.max_jawaban, function (key, val) {
                dataMax[keys] = val
                keys ++
            })

            let objJawaban = {
                jawaban: arrData,
                max: dataMax,
                model: modelSoal,
                type: typeSoal,
            }
            //console.log('obj', objJawaban)
            konten.linkerList({
                enableEditor: false,
                data: objJawaban,
                viewMode: '2',
                id: nomorSoal,
                callback: function (id, data, hasLinks, isOffset) {
                    if (isOffset !== '0') {
                        $.toast({
                            heading: 'Warning',
                            text: 'Maksimal <b>' + isOffset + ' jawaban',
                            showHideTransition: 'slide',
                            icon: 'error',
                            loaderBg: '#f2a654',
                            position: 'bottom-center'
                        })
                    } else {
                        submitJawaban(data)
                    }
                }
            });
        } else if (jenis == "4") {
            html += '<div class="pr-4">' +
                '<span class="">JAWABAN:</span><br>' +
                '<div class="row"><div class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4">'+
                '<input id="jawaban-essai" class="pl-1 form-control" type="text"' +
                ' name="jawaban" value="' + jawabanSiswa + '"' +
                ' placeholder="Tulis jawaban disini"/><br>' +
                '</div></div>' +
                '</div>';
            $('#konten-jawaban').html(html);
        } else {
            html += '<div class="pr-4">' +
                '<label>JAWABAN:</label><br>' +
                '<textarea id="jawaban-essai" class="w-100 pl-1" type="text"' +
                ' name="jawaban" rows="4"' +
                ' placeholder="Tulis jawaban disini">' + jawabanSiswa + '</textarea><br>' +
                '</div>';
            $('#konten-jawaban').html(html);
        }

        $('#konten-modal').html(data.soal_modal);

        var $imgs = $('.konten-soal-jawab').find('img');
        $.each($imgs, function () {
            var curSrc = $(this).attr('src');
            if (!curSrc.includes("uploads")) return;
            var newSrc = '';
            if (curSrc.indexOf("http") === -1 && curSrc.indexOf("data:image") === -1) {
                newSrc = base_url + curSrc;
                $(this).attr('src', newSrc);
            } else if (curSrc.indexOf(base_url) === -1) {
                var pathUpload = 'uploads';
                var forReplace = curSrc.split(pathUpload);
                newSrc = base_url + pathUpload + forReplace[1];
                $(this).attr('src', newSrc);
				$(this).removeAttr('alt');
            }
            $(this).on('load', function () {
                if ($(this).height() > 50) {
                    $(this).addClass('img-fluid');
                }
            });
        });

        $('video').css({'width': '100%', 'max-height': '100%'});

        $('.check').change(function (e) {
            var row = $(e.target).closest('tr');
            var isChecked = $(row).find("input:checked");
            var max = $(e.target).data('max');
            if (isChecked.length > max) {
                $(e.target).prop('checked', !$(this).prop('checked'));
                $.toast({
                    heading: 'Warning',
                    text: 'Maksimal <b>' + max + ' jawaban',
                    showHideTransition: 'slide',
                    icon: 'error',
                    loaderBg: '#f2a654',
                    position: 'bottom-center'
                })
            } else {
                submitJawaban(null);
            }
        });

        $("#jawaban-essai").on('change keyup paste', function () {
            submitJawaban(null);
        });
        if (!data.durasi) {
            window.location.href = base_url + 'siswa/cbt';
        } else {
            setElapsed(data.durasi);

            if (timerSelesai) {
                clearTimeout(timerSelesai);
                timerSelesai = null;
            }

            var next = $('#next');
            next.removeAttr('disabled');
            var txtnext = $('#text-next');
            $('#ic-btn').remove();

            if (soalTotal === nomorSoal && data.durasi.mulai != null && data.durasi.mulai != '0') {
                next.removeClass('btn-primary');
                next.addClass('btn-success');
                next.append('<i id="ic-btn" class="fa fa-check-circle"></i>');
                txtnext.html('<b>Selesai</b>');
                setTimerSelesai(next, data.durasi);
            } else {
                $('#timer-selesai').addClass('d-none');
                next.removeClass('d-none');
                next.removeClass('btn-success');
                next.addClass('btn-primary');
                next.append('<i id="ic-btn" class="fa fa-arrow-circle-right"></i>');
                txtnext.html('<b>Soal Berikutnya</b>');
            }
        }

        arrSize = [];
    }

    function setElapsed(durasi) {
        elapsed = durasi.lama_ujian == null || durasi.lama_ujian == '0' ? "00:00:00" : durasi.lama_ujian;
        createTimerCountdown(durasiUjian, elapsed.split(':'), function (isOver, remaining, onGoing) {
            $('#timer').html(remaining);
            elapsed = onGoing;
            if (isOver) {
                $('#prev').attr('disabled', 'disabled');
                $('#next').attr('disabled', 'disabled');

                var siswa = $('#up').find('input[name="siswa"]').val();
                var bank = $('#up').find('input[name="bank"]').val();
                var jadwal = $('#up').find('input[name="jadwal"]').val();

                $.ajax({
                    url: base_url + 'siswa/savejawaban',
                    method: 'POST',
                    data: $('#jawab').serialize() + '&jadwal=' + jadwal + '&siswa=' + siswa + '&bank=' + bank +
                        '&waktu=' + $('#timer').text() + '&elapsed=' + elapsed + '&data=' + JSON.stringify(jsonJawaban),
                    success: function (response) {
                        $('.konten-soal-jawab').html('');
                        dialogWaktu();
                    },
                    error: function (xhr, error, status) {
                        console.log(xhr.responseText);
                    }
                });
            }
        })
    }

    function nextSoal() {
        $('#next').attr('disabled', 'disabled');
        $('#loading').removeClass('d-none');
        nav = (nomorSoal + 1);
        var jwb1 = jawabanSiswa;
        var jwb2 = jawabanBaru;
        if ($.isArray(jwb1) || jwb1 instanceof jQuery) {
            jwb1 = JSON.stringify(jwb1)
        }
        if (jwb2 != null && ($.isArray(jwb2) || jwb2 instanceof jQuery)) {
            jwb2 = JSON.stringify(jwb2)
        }

        if (jawabanBaru != null && jwb1 !== jwb2) {
            $('#jawab').submit();
        } else {
            loadSoalNomor(nav);
        }
    }

    function setTimerSelesai(next, durasi) {
        const perdetik = 1000;
        const permenit = 60 * perdetik;
        const perjam = 60 * permenit;
        const t_dur = Number(infoJadwal.jarak) * (1000 * 60);

        const elapsed = (durasi.lama_ujian == null || durasi.lama_ujian == '0' ? "00:00:00" : durasi.lama_ujian).split(':');
        let t_jam = Number(elapsed[0]);
        let t_mnt = Number(elapsed[1]);
        let t_dtk = Number(elapsed[2]);

        const btnTimer = $('#timer-selesai');
        setTimer();

        function setTimer() {
            if (timerSelesai) {
                clearTimeout(timerSelesai);
                timerSelesai = null;
            }

            const elapsedMicro = (t_jam * perjam) + (t_mnt * permenit) + (t_dtk * perdetik);
            const t_remaining = t_dur - elapsedMicro;
            if (t_remaining <= 0) {
                next.removeClass('d-none');
                btnTimer.addClass('d-none');
            } else {
                // elapsed
                t_dtk++;
                if (t_dtk > 59) {
                    t_dtk = 0;
                    t_mnt++;
                }
                if (t_mnt > 59) {
                    t_mnt = 0;
                    t_jam++;
                }

                // remaining
                const r_jam = Math.floor(t_remaining / perjam);
                const r_mnt = Math.floor((t_remaining % perjam) / permenit);
                const r_dtk = Math.floor((t_remaining % permenit) / perdetik);
                next.addClass('d-none');
                btnTimer.removeClass('d-none');
                btnTimer.html(zeroPad(r_jam) + ':' + zeroPad(r_mnt) + ':' + zeroPad(r_dtk));

                timerSelesai = setTimeout(setTimer, 1000);
            }
        }
    }

    function prevSoal() {
        $('#prev').attr('disabled', 'disabled');
        $('#loading').removeClass('d-none');
        nav = (nomorSoal - 1);
        var jwb1 = jawabanSiswa;
        var jwb2 = jawabanBaru;
        if ($.isArray(jwb1) || jwb1 instanceof jQuery) {
            jwb1 = JSON.stringify(jwb1)
        }
        if (jwb2 != null && ($.isArray(jwb2) || jwb2 instanceof jQuery)) {
            jwb2 = JSON.stringify(jwb2)
        }

        if (jawabanBaru != null && jwb1 !== jwb2) {
            $('#jawab').submit();
        } else {
            loadSoalNomor(nav);
        }
    }

    function updateModal(jwb) {
        var badges = $('#konten-modal').find(`#badge${nomorSoal}`);
        var btn = $('#konten-modal').find(`#btn${nomorSoal}`);
        btn.removeClass("btn-outline-secondary");
        btn.addClass("btn-primary");
        if (jenisSoal == 1) {
            if (badges.length) {
                $(`#badge${nomorSoal}`).text(jwb)
            } else {
                var badge = '<div id="badge' + nomorSoal + '" class="badge badge-pill badge-success border border-dark text-yellow"' +
                    ' style="font-size:12pt; width: 30px; height: 30px; margin-top: -60px; margin-left: 30px;">' +
                    jwb +
                    '</div>';
                $(`#box${nomorSoal}`).append(badge);
            }
        } else {
            if (!badges.length) {
                var badge = '<div id="badge' + nomorSoal + '" class="badge badge-pill badge-success border border-dark"' +
                    ' style="font-size:12pt; width: 30px; height: 30px; margin-top: -60px; margin-left: 30px;">' +
                    '&check;</div>';
                $(`#box${nomorSoal}`).append(badge);
            }
        }
    }

    function submitJawaban(opsi) {
        var jawaban_Siswa = '', jawaban_Alias = '';
        if (jenisSoal == 1) {
            jawaban_Siswa = $(opsi).data('jawabansiswa');
            jawaban_Alias = $(opsi).data('jawabanalias');
            $('.container-jawaban').removeClass('selected-card');
            $(opsi).closest('.container-jawaban').addClass('selected-card');
        } else if (jenisSoal == 2) {
            var isChecked = $('#konten-jawaban').find("input:checked");
            var max = $(opsi).data('max');
            //console.log('max:'+max, 'checked:'+isChecked.length);
            if (isChecked.length > max) {
                $(opsi).prop('checked', !$(opsi).prop('checked'));
                $.toast({
                    heading: 'Warning',
                    text: 'Maksimal <b>' + max + ' jawaban',
                    showHideTransition: 'slide',
                    icon: 'error',
                    loaderBg: '#f2a654',
                    position: 'bottom-center'
                });
                return;
            } else {
                var selected = [];
                $('#konten-jawaban input:checked').each(function () {
                    selected.push($(this).val());
                });
                jawaban_Siswa = selected;
            }
        } else if (jenisSoal == 3) {
            jawaban_Siswa = opsi
        } else {
            jawaban_Siswa = $('#jawaban-essai').val();
        }
        jawabanBaru = jawaban_Siswa;
        if (jenisSoal == 2) {
            if ($.isArray(jawabanBaru)) jawabanBaru.sort();
        }

        updateModal(jawaban_Alias);
        jsonJawaban = createJsonJawaban(jawaban_Alias, jawaban_Siswa);
    }

    function createJsonJawaban(jawab_Alias, jawab_Siswa) {
        var siswa = $('#up').find('input[name="siswa"]').val();
        var jadwal = $('#up').find('input[name="jadwal"]').val();
        var bank = $('#up').find('input[name="bank"]').val();

        var item = {};
        item ["no_soal_alias"] = nomorSoal;
        item ["jawaban_alias"] = jawab_Alias;
        item ["jawaban_siswa"] = jawab_Siswa;
        item ["jenis"] = jenisSoal;
        item ["id_soal"] = idSoal;
        item ["id_soal_siswa"] = idSoalSiswa;
        item ["id_jadwal"] = jadwal;
        item ["id_bank"] = bank;
        item ["id_siswa"] = siswa;

        return item;
    }

    function getDataTable() {
        var tbl = $('#table-jodohkan tr').get().map(function (row) {
            var $tables = [];

            $(row).find('th').get().map(function (cell) {
                var klm = $(cell).text().trim();
                $tables.push(klm == "" ? "#" : encode(klm));
            });

            $(row).find('td').get().map(function (cell) {
                if ($(cell).children('input').length > 0) {
                    $tables.push($(cell).find('input').prop("checked") === true ? "1" : "0");
                } else {
                    $tables.push(encode($(cell).text().trim()))
                }
            });

            return $tables;
        });
        return tbl;
    }

    function convertTable(data) {
        const head = []
        const body = []
        $.each(data.tabel, function (idx, val) {
            if (idx === 0) {
                $.each(val, function (id, vl) {
                    if (vl !== "#") head.push(encode(vl))
                })
            } else {
                $.each(val, function (id, vl) {
                    if (id === 0) body.push(encode(vl))
                })
            }
        })
        var kanan = data.thead;
        var kiri = [];
        $.each(data.tbody, function (i, v) {
            kiri.push(encode(v.shift()));
        });
        kanan.shift();

        var linked = [];
        $.each(data.tbody, function (n, arv) {
            $.each(arv, function (t, v) {
                if (v == '1') {
                    var it = {};
                    it['from'] = encode(body[n]);
                    it['to'] = encode(head[t]);
                    linked.push(it);
                }
            });
        });
        var item = {};
        item['type'] = data.type;
        item['jawaban'] = [body, head];
        item['linked'] = linked;
        return item;
    }

    function convertTableToList(data) {
        var kanan = data.thead;
        //console.log('kanan', kanan);
        var kiri = [];
        $.each(data.tbody, function (i, v) {
            kiri.push(decode(v.shift()));
        });
        kanan.shift();
        //console.log('kiri', kiri);
        $.each(kanan, function (i, v) {
            kanan[i] = (decode(v));
        });

        var linked = [];
        $.each(data.tbody, function (n, arv) {
            $.each(arv, function (t, v) {
                if (v == '1') {
                    var it = {};
                    it['from'] = decode(kiri[n]);
                    it['to'] = decode(kanan[t]);
                    linked.push(it);
                }
            });
        });
        var item = {};
        item['type'] = data.type;
        item['jawaban'] = [kiri, kanan];
        item['linked'] = linked;
        //console.log('test', item);
        return item;
    }

    function getListData() {
        var kolom = [];
        var baris = [];
        $(".FL-left li").each(function () {
            baris.push(encode($(this).text()));
        });
        $(".FL-right li").each(function () {
            kolom.push(encode($(this).text()));
        });
        return [kolom, baris];
    }

    function selesai() {
        if (soalTotal === soalTerjawab) {
            swal.fire({
                title: "Kamu yakin?",
                text: "Kamu akan menyelesaikan ujian",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Selesaikan!"
            }).then(result => {
                if (result.value) {
                    $.ajax({
                        url: base_url + 'siswa/selesaiujian',
                        method: "POST",
                        data: $('#up').serialize(),
                        success: function (respon) {
                            $('#next').removeAttr('disabled');
                            $('#loading').addClass('d-none');
                            //console.log(respon);
                            if (respon.status) {
                                if (window.ReactNativeWebView) {
                                    window.ReactNativeWebView.postMessage(JSON.stringify({
                                        type: 'EXAM_FINISHED',
                                        id_jadwal: '<?= $jadwal->id_jadwal ?>',
                                        id_siswa: '<?= $siswa->id_siswa ?>'
                                    }));
                                }
                                window.location.href = base_url + 'siswa/cbt';
                            } else {
                                swal.fire({
                                    title: "Gagal",
                                    text: "Tidak bisa menyelesaikan ujian",
                                    icon: "error"
                                });
                            }
                        },
                        error: function (xhr, error, status) {
                            console.log(xhr.responseText);
                            swal.fire({
                                title: "Gagal",
                                text: "Tidak bisa menyelesaikan ujian",
                                icon: "error"
                            });
                        }
                    });
                } else {
                    $('#next').removeAttr('disabled');
                    $('#loading').addClass('d-none');
                }
            });
        } else {
            swal.fire({
                title: "BELUM SELESAI!",
                text: "Masih ada soal yang belum dikerjakan",
                icon: "error",
                confirmButtonColor: "#3085d6",
            }).then(result => {
                if (result.value) {
                    $('#next').removeAttr('disabled');
                    $('#loading').addClass('d-none');
                }
            });
        }
    }

    function dialogWaktu() {
        swal.fire({
            title: "Sudah Habis",
            text: "Waktu Ujian sudah habis, hubungi proktor",
            icon: "warning",
            allowOutsideClick: false,
            confirmButtonColor: "#3085d6",
            confirmButtonText: "OK"
        }).then(result => {
            if (result.value) {
                window.location.href = base_url + 'siswa/cbt';
            }
        });
    }

    function rtclickcheck(keyp) {
        if (navigator.appName == "Netscape" && keyp.which == 3) {
            alert(message);
            return false;
        }

        if (navigator.appVersion.indexOf("MSIE") != -1 && event.button == 2) {
            alert(message);
            return false;
        }
    }

    function openFullscreen() {
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.mozRequestFullScreen) {
            /* Firefox */
            elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) {
            /* Chrome, Safari & Opera */
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            /* IE/Edge */
            elem = window.top.document.body; //To break out of frame in IE
            elem.msRequestFullscreen();
        }
    }

    function getMinutes(startTime) {
        var endTime = new Date();
        endTime.setHours(endTime.getHours() - startTime.getHours());
        endTime.setMinutes(endTime.getMinutes() - startTime.getMinutes());
        endTime.setSeconds(endTime.getSeconds() - startTime.getSeconds());

        return {h: endTime.getHours(), m: endTime.getMinutes(), s: endTime.getSeconds()}
    }

    function createTimerCountdown(durasi, elapsed, func) {
        const perdetik = 1000;
        const permenit = 60 * perdetik;
        const perjam = 60 * permenit;
        const t_dur = durasi * (1000 * 60);

        let t_jam = Number(elapsed[0]);
        let t_mnt = Number(elapsed[1]);
        let t_dtk = Number(elapsed[2]);

        testTimer();

        function testTimer() {
            if (timerOut) {
                clearTimeout(timerOut);
                timerOut = null;
            }

            const elapsedMicro = (t_jam * perjam) + (t_mnt * permenit) + (t_dtk * perdetik);
            const t_remaining = t_dur - elapsedMicro;
            if (t_remaining <= 0) {
                if (func && (typeof func == "function")) {
                    func(true, 'Waktu habis', zeroPad(t_jam) + ':' + zeroPad(t_mnt) + ':' + zeroPad(t_dtk));
                }
            } else {
                // elapsed
                t_dtk++;
                if (t_dtk > 59) {
                    t_dtk = 0;
                    t_mnt++;
                }
                if (t_mnt > 59) {
                    t_mnt = 0;
                    t_jam++;
                }

                // remaining
                const r_jam = Math.floor(t_remaining / perjam);
                const r_mnt = Math.floor((t_remaining % perjam) / permenit);
                const r_dtk = Math.floor((t_remaining % permenit) / perdetik);

                if (func && (typeof func == "function")) {
                    func(false,
                        zeroPad(r_jam) + ':' + zeroPad(r_mnt) + ':' + zeroPad(r_dtk),
                        zeroPad(t_jam) + ':' + zeroPad(t_mnt) + ':' + zeroPad(t_dtk)
                    );
                }
                timerOut = setTimeout(testTimer, 1000);
            }
        }
    }

    function zeroPad(no) {
        return no < 10 ? '0' + no : no;
    }

    function encode(str) {
        var decoded = decodeURIComponent(str)
        var isEncoded = decoded !== str
        var encoded = encodeURIComponent(str)
        if (isEncoded) {
            return str
        } else {
            return encoded
        }
    }

    function decode(str) {
        var decoded = decodeURIComponent(str)
        var encoded = encodeURIComponent(decoded)
        var isEncoded = encoded === str
        if (isEncoded) {
            return decoded
        } else {
            return str
        }
    }

    // =========================================================================
    // SADUCI CBT - ADVANCED ANTI-CHEAT & RESILIENCE ENGINE
    // =========================================================================

    // 1. Dynamic Watermark Generator
    function initWatermark() {
        var watermarkEl = document.getElementById('watermark-layer');
        if (!watermarkEl) return;
        var studentText = 'SADUCI CBT \u2022 <?= addslashes($siswa->nama) ?> (<?= $siswa->nis ?>) \u2022 <?= addslashes($siswa->nama_kelas) ?>';
        var html = '';
        for (var i = 0; i < 18; i++) {
            html += '<div class="exam-watermark-text">' + studentText + '</div>';
        }
        watermarkEl.innerHTML = html;
    }
    initWatermark();

    // 2. Anti-Cheat: Screen Shield (Disable context menu, copy, paste, inspect, print)
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
        return false;
    });

    document.addEventListener('keydown', function (e) {
        // Block F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C, Ctrl+U, Ctrl+C, Ctrl+V, PrintScreen
        if (e.keyCode === 123 ||
            (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67)) ||
            (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 67 || e.keyCode === 86 || e.keyCode === 88 || e.keyCode === 80)) ||
            e.keyCode === 44
        ) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof swal !== 'undefined') {
                swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Aksi pintasan diblokir demi integritas ujian!',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
            return false;
        }
    });

    // 3. Anti-Cheat: Tab Switch / Blur Detector with 3x Tolerance
    var MAX_TAB_VIOLATIONS = 3;
    var examJadwalKey = 'saduci_violations_<?= $jadwal->id_jadwal ?>_<?= $siswa->id_siswa ?>';
    var tabViolations = parseInt(sessionStorage.getItem(examJadwalKey) || '0');
    var isWarningOpen = false;

    function handleTabViolation() {
        if (isWarningOpen) return;
        // Don't trigger if exam already finished or timer stopped
        if (typeof timerOut === 'undefined' || timerOut === null) return;

        tabViolations++;
        sessionStorage.setItem(examJadwalKey, tabViolations);

        // Notifikasi ke Native Exambro App jika berjalan di dalam container mobile
        if (window.ReactNativeWebView) {
            window.ReactNativeWebView.postMessage(JSON.stringify({
                type: 'VIOLATION_OCCURRED',
                tabViolations: tabViolations,
                maxViolations: MAX_TAB_VIOLATIONS,
                id_jadwal: '<?= $jadwal->id_jadwal ?>',
                id_siswa: '<?= $siswa->id_siswa ?>'
            }));
        }

        if (tabViolations < MAX_TAB_VIOLATIONS) {
            isWarningOpen = true;
            var sisaPeringatan = MAX_TAB_VIOLATIONS - tabViolations;
            if (typeof swal !== 'undefined') {
                swal.fire({
                    title: '⚠️ Peringatan Pindah Layar!',
                    html: '<p class="mb-2">Anda terdeteksi meninggalkan layar ujian.</p>' +
                          '<p class="mb-1 text-danger font-weight-bold">Pelanggaran ke-<b>' + tabViolations + '</b> dari <b>' + MAX_TAB_VIOLATIONS + '</b> batas toleransi.</p>' +
                          '<small class="text-muted">Tersisa ' + sisaPeringatan + ' kesempatan sebelum ujian terkunci otomatis!</small>',
                    icon: 'warning',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Saya Mengerti & Kembali ke Ujian',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    isWarningOpen = false;
                });
            }
        } else {
            isWarningOpen = true;
            if (typeof swal !== 'undefined') {
                swal.fire({
                    title: '🚫 Ujian Terkunci!',
                    html: '<p>Anda telah melakukan perpindahan layar melebihi batas toleransi (' + MAX_TAB_VIOLATIONS + 'x).</p>' +
                          '<p class="text-danger font-weight-bold mb-0">Ujian Anda telah dikunci oleh sistem dan harus di-reset oleh pengawas/administrator.</p>',
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Tutup & Laporkan ke Pengawas',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    location.href = base_url + "siswa/leavecbt/<?= $jadwal->id_jadwal ?>/<?= $siswa->id_siswa ?>";
                });
            } else {
                location.href = base_url + "siswa/leavecbt/<?= $jadwal->id_jadwal ?>/<?= $siswa->id_siswa ?>";
            }
        }
    }

    document.addEventListener("visibilitychange", function () {
        if (document.hidden) {
            handleTabViolation();
        }
    });

    window.addEventListener("blur", function () {
        handleTabViolation();
    });

    // 4. Offline Resilience & LocalStorage Dual-Save
    function setOnlineStatus(isOnline) {
        var pill = $('#network-pill');
        var text = $('#network-text');
        if (isOnline) {
            pill.removeClass('network-offline').addClass('network-online');
            text.text('Online');
        } else {
            pill.removeClass('network-online').addClass('network-offline');
            text.text('Offline (Disimpan di HP)');
        }
    }

    function saveAnswerLocally(soalId, dataJson) {
        try {
            var localKey = 'saduci_answer_<?= $jadwal->id_jadwal ?>_<?= $siswa->id_siswa ?>_' + soalId;
            localStorage.setItem(localKey, JSON.stringify(dataJson));
        } catch (e) {
            console.warn('LocalStorage error:', e);
        }
    }

    function getLocalAnswer(soalId) {
        try {
            var localKey = 'saduci_answer_<?= $jadwal->id_jadwal ?>_<?= $siswa->id_siswa ?>_' + soalId;
            var val = localStorage.getItem(localKey);
            return val ? JSON.parse(val) : null;
        } catch (e) {
            return null;
        }
    }

    function queueAnswerOffline(soalId, dataJson, siswaId, bankId) {
        try {
            var queueKey = 'saduci_offline_queue_<?= $jadwal->id_jadwal ?>_<?= $siswa->id_siswa ?>';
            var queue = JSON.parse(localStorage.getItem(queueKey) || '[]');
            queue = queue.filter(function(item) { return item.soalId !== soalId; });
            queue.push({
                soalId: soalId,
                data: dataJson,
                siswa: siswaId,
                bank: bankId,
                jadwal: '<?= $jadwal->id_jadwal ?>',
                time: new Date().getTime()
            });
            localStorage.setItem(queueKey, JSON.stringify(queue));
            showWarningToast('Koneksi internet terputus. Jawaban No. ' + nomorSoal + ' diamankan di HP & akan disinkron otomatis!');
        } catch (e) {
            console.error('Queue error:', e);
        }
    }

    function removeAnswerFromOfflineQueue(soalId) {
        try {
            var queueKey = 'saduci_offline_queue_<?= $jadwal->id_jadwal ?>_<?= $siswa->id_siswa ?>';
            var queue = JSON.parse(localStorage.getItem(queueKey) || '[]');
            queue = queue.filter(function(item) { return item.soalId !== soalId; });
            localStorage.setItem(queueKey, JSON.stringify(queue));
        } catch (e) {}
    }

    function syncOfflineQueue() {
        var queueKey = 'saduci_offline_queue_<?= $jadwal->id_jadwal ?>_<?= $siswa->id_siswa ?>';
        var queue = JSON.parse(localStorage.getItem(queueKey) || '[]');
        if (!queue || queue.length === 0) return;

        var itemsToSync = queue.slice();
        var syncCount = 0;

        itemsToSync.forEach(function (item) {
            var fd = new FormData();
            fd.append('siswa', item.siswa);
            fd.append('bank', item.bank);
            fd.append('jadwal', item.jadwal);
            fd.append('data', JSON.stringify(item.data));

            $.ajax({
                url: base_url + 'siswa/savejawaban',
                method: 'POST',
                processData: false,
                contentType: false,
                data: fd,
                success: function () {
                    removeAnswerFromOfflineQueue(item.soalId);
                    syncCount++;
                    if (syncCount === itemsToSync.length) {
                        showSuccessToast('Semua jawaban offline (' + syncCount + ') berhasil disinkronkan ke server!');
                    }
                }
            });
        });
    }

    window.addEventListener('online', function () {
        setOnlineStatus(true);
        syncOfflineQueue();
    });

    window.addEventListener('offline', function () {
        setOnlineStatus(false);
    });

    if (navigator.onLine) {
        syncOfflineQueue();
    } else {
        setOnlineStatus(false);
    }

    // 5. Exambro Mobile Kiosk Handshake
    if (window.ReactNativeWebView) {
        window.ReactNativeWebView.postMessage(JSON.stringify({
            type: 'EXAM_STARTED',
            id_jadwal: '<?= $jadwal->id_jadwal ?>',
            id_siswa: '<?= $siswa->id_siswa ?>',
            nama: '<?= addslashes($siswa->nama) ?>',
            nis: '<?= $siswa->nis ?>'
        }));
    }
</script>

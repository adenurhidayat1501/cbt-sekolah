<?php
$logo_app = (isset($setting->logo_kanan) && !empty($setting->logo_kanan)) ? base_url() . $setting->logo_kanan : base_url('assets/img/saduci_logo.png');
$app_title = (!empty($setting->nama_aplikasi)) ? $setting->nama_aplikasi : 'SADUCI CBT';
$school_subtitle = (!empty($setting->sekolah)) ? $setting->sekolah : 'SMP NEGERI SATU ATAP 2 CIPATUJAH';
?>
<div class="modern-login-wrapper">
    <div class="modern-login-card">
        <!-- Brand & School Header -->
        <div class="text-center mb-4">
            <div class="modern-login-logo">
                <img src="<?= $logo_app ?>" alt="Logo">
            </div>
            <h4 class="modern-login-title"><?= $app_title ?></h4>
            <p class="modern-login-subtitle mb-0"><?= $school_subtitle ?></p>
        </div>

        <!-- Feedback & Error Message -->
        <div id="infoMessage"><?php echo $message; ?></div>

        <!-- Login Method Navigation Tabs -->
        <div class="login-method-nav">
            <button type="button" class="login-method-btn active" id="tab-qr" onclick="switchLoginMethod('qr')">
                <i class="fas fa-qrcode"></i> Scan Kartu QR
            </button>
            <button type="button" class="login-method-btn" id="tab-manual" onclick="switchLoginMethod('manual')">
                <i class="fas fa-keyboard"></i> Login Manual
            </button>
        </div>

        <!-- Panel 1: QR Code Scanner Login (Primary for Students) -->
        <div id="pane-qr">
            <div class="qr-scanner-box">
                <!-- Camera Live Stream Container -->
                <div id="qr-camera-container" class="qr-camera-wrap" style="display: none;">
                    <div id="qr-camera-reader" style="width: 100%;"></div>
                </div>

                <!-- Animated Viewfinder Graphic (Shown when camera is off) -->
                <div id="qr-viewfinder-box">
                    <div class="qr-viewfinder">
                        <i class="fas fa-qrcode text-primary" style="font-size: 56px; opacity: 0.85;"></i>
                        <div class="qr-laser-line"></div>
                    </div>
                    <div class="font-weight-bold text-white mb-1" style="font-size: 15px;">Arahkan Kartu Peserta Ujian</div>
                    <p class="text-muted mb-0" style="font-size: 13px;">Dekatkan QR Code pada kartu siswa ke kamera atau scanner pengawas untuk login otomatis.</p>
                </div>

                <!-- Action button to activate camera -->
                <button type="button" id="btn-toggle-camera" class="btn btn-primary btn-block mt-3 font-weight-bold" style="border-radius: 12px; height: 46px;">
                    <i class="fas fa-camera mr-2"></i>Buka Kamera untuk Scan
                </button>
            </div>

            <!-- Hardware Barcode / QR Scanner Input Field -->
            <div class="modern-input-group has-feedback mb-3">
                <span class="fas fa-barcode modern-input-icon"></span>
                <input type="text" id="qr-scanner-input" class="form-control" autocomplete="off" autofocus
                       placeholder="Atau scan menggunakan Barcode Scanner USB..." style="font-size: 13px;">
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="cbt-only-qr" name="cbt-only" value="1" checked="checked">
                    <label class="custom-control-label font-weight-normal text-muted" for="cbt-only-qr" style="cursor: pointer; font-size: 13px;">
                        Langsung Buka Mode Ujian (CBT Only)
                    </label>
                </div>
            </div>
        </div>

        <!-- Panel 2: Manual Login Form (Fallback & Admin/Guru) -->
        <div id="pane-manual" style="display: none;">
            <?= form_open("auth/cek_login", array('id' => 'login')); ?>
            
            <div class="modern-input-group has-feedback">
                <span class="fas fa-user modern-input-icon"></span>
                <?= form_input($identity, '', 'required placeholder="Username atau NISN"'); ?>
                <div class="help-block text-danger font-weight-bold mt-1 text-sm"></div>
            </div>

            <div class="modern-input-group has-feedback">
                <span class="fas fa-lock modern-input-icon"></span>
                <?= form_input($password, '', 'required placeholder="Password"'); ?>
                <span id="toggle-password" class="fas fa-eye-slash modern-input-toggle"></span>
                <div class="help-block text-danger font-weight-bold mt-1 text-sm"></div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="cbt-only" name="cbt-only" value="1" checked="checked">
                    <label class="custom-control-label font-weight-normal text-muted" for="cbt-only" style="cursor: pointer; font-size: 14px;">
                        Mode Ujian (CBT Only)
                    </label>
                </div>
            </div>

            <div class="form-group mb-0">
                <?= form_submit('submit', lang('login_submit_btn'), array('id' => 'submit', 'class' => 'modern-login-btn')); ?>
            </div>

            <?= form_close(); ?>
        </div>
    </div>
</div>

<script src="<?= base_url() ?>/assets/plugins/html5-qrcode/html5-qrcode.min.js"></script>
<script src="<?= base_url() ?>/assets/app/js/jquery.backstretch.js"></script>
<script type="text/javascript">
    let base_url = '<?=base_url();?>';
    var img = ["wall1.jpg", "wall2.png", "wall3.jpg"];

    $.backstretch([
        base_url + 'assets/img/' + img[0],
        base_url + 'assets/img/' + img[1],
        base_url + 'assets/img/' + img[2]
    ], {
        fade: 1000,
        duration: 10000
    });

    // Audio Feedback Beep
    function playSuccessBeep() {
        try {
            var audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = audioCtx.createOscillator();
            var gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type = "sine";
            osc.frequency.setValueAtTime(880, audioCtx.currentTime); // 880Hz A5
            gain.gain.setValueAtTime(0.25, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.22);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.22);
        } catch(e) {}
    }

    // Switch between QR Login and Manual Login
    function switchLoginMethod(method) {
        if (method === 'qr') {
            $('#tab-qr').addClass('active');
            $('#tab-manual').removeClass('active');
            $('#pane-qr').show();
            $('#pane-manual').hide();
            setTimeout(function() {
                $('#qr-scanner-input').focus();
            }, 100);
        } else {
            $('#tab-manual').addClass('active');
            $('#tab-qr').removeClass('active');
            $('#pane-manual').show();
            $('#pane-qr').hide();
            stopCameraScanner();
            setTimeout(function() {
                $('[name="identity"]').focus();
            }, 100);
        }
    }

    // HTML5 Camera QR Scanner Handler
    let html5QrCode = null;
    let isCameraRunning = false;

    function startCameraScanner() {
        if (isCameraRunning) {
            stopCameraScanner();
            return;
        }

        $('#qr-viewfinder-box').hide();
        $('#qr-camera-container').show();
        $('#btn-toggle-camera').html('<i class="fas fa-stop mr-2"></i>Tutup Kamera').removeClass('btn-primary').addClass('btn-secondary');

        html5QrCode = new Html5Qrcode("qr-camera-reader");
        const config = { fps: 15, qrbox: { width: 220, height: 220 }, aspectRatio: 1.0 };

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            function onScanSuccess(decodedText) {
                if (decodedText) {
                    playSuccessBeep();
                    stopCameraScanner();
                    processQrLogin(decodedText);
                }
            },
            function onScanFailure(error) {
                // Ignore frame parse misses
            }
        ).then(() => {
            isCameraRunning = true;
        }).catch(err => {
            console.warn("Front facing camera fallback:", err);
            html5QrCode.start(
                { facingMode: "user" },
                config,
                function onScanSuccess(decodedText) {
                    if (decodedText) {
                        playSuccessBeep();
                        stopCameraScanner();
                        processQrLogin(decodedText);
                    }
                },
                function onScanFailure(error) {}
            ).then(() => {
                isCameraRunning = true;
            }).catch(err2 => {
                $('#infoMessage').html('<div class="alert alert-warning py-2 text-center mb-3"><i class="fas fa-exclamation-triangle mr-2"></i>Kamera tidak dapat diakses (' + err2 + '). Silakan gunakan scanner USB atau login manual.</div>');
                stopCameraScanner();
            });
        });
    }

    function stopCameraScanner() {
        if (html5QrCode && isCameraRunning) {
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
                isCameraRunning = false;
                $('#qr-camera-container').hide();
                $('#qr-viewfinder-box').show();
                $('#btn-toggle-camera').html('<i class="fas fa-camera mr-2"></i>Buka Kamera untuk Scan').removeClass('btn-secondary').addClass('btn-primary');
            }).catch(err => {
                console.error(err);
                isCameraRunning = false;
            });
        } else {
            $('#qr-camera-container').hide();
            $('#qr-viewfinder-box').show();
            $('#btn-toggle-camera').html('<i class="fas fa-camera mr-2"></i>Buka Kamera untuk Scan').removeClass('btn-secondary').addClass('btn-primary');
        }
    }

    // Process QR Login submission
    let isProcessingQr = false;
    function processQrLogin(qrData) {
        if (isProcessingQr) return;
        isProcessingQr = true;

        var infobox = $('#infoMessage');
        infobox.html('<div class="alert alert-info py-2 px-3 text-center mb-3"><i class="fas fa-spinner fa-spin mr-2"></i>Kartu Terdeteksi! Memverifikasi akun siswa...</div>');

        const isCbtMode = $('#cbt-only-qr').is(':checked');
        localStorage.setItem('saduciCBT.login', isCbtMode ? '1' : '0');
        localStorage.setItem('garudaCBT.login', isCbtMode ? '1' : '0');

        $.ajax({
            url: base_url + 'auth/cek_login_qr',
            type: 'POST',
            dataType: 'JSON',
            data: {
                qr_data: qrData,
                'cbt-only': isCbtMode ? '1' : '0'
            },
            success: function(res) {
                isProcessingQr = false;
                if (res && res.status) {
                    var namaSiswa = res.nama ? ('<b>' + res.nama + '</b>') : 'Siswa';
                    infobox.html('<div class="alert alert-success py-2 px-3 text-center mb-3"><i class="fas fa-check-circle mr-2"></i>Login Berhasil! Selamat datang, ' + namaSiswa + '. Mengalihkan ke lembar ujian...</div>');
                    setTimeout(function() {
                        window.location.href = base_url + (res.url || 'siswa/cbt');
                    }, 600);
                } else {
                    infobox.html('<div class="alert alert-danger py-2 px-3 text-center mb-3"><i class="fas fa-exclamation-triangle mr-2"></i>' + (res && res.failed ? res.failed : 'Kartu siswa tidak dikenali atau belum aktif.') + '</div>');
                    $('#qr-scanner-input').val('').focus();
                }
            },
            error: function(xhr, status, error) {
                isProcessingQr = false;
                infobox.html('<div class="alert alert-danger py-2 px-3 text-center mb-3"><i class="fas fa-exclamation-circle mr-2"></i>Gagal terhubung ke server. Periksa jaringan Anda.</div>');
                $('#qr-scanner-input').val('').focus();
            }
        });
    }

    $(document).ready(function(){
        // Focus scanner input by default for instant USB scanner reading
        $('#qr-scanner-input').focus();

        $('#btn-toggle-camera').on('click', function(e) {
            e.preventDefault();
            if (isCameraRunning) {
                stopCameraScanner();
            } else {
                startCameraScanner();
            }
        });

        // Handle USB Hardware Barcode/QR Scanner Enter Keypress
        $('#qr-scanner-input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                var val = $.trim($(this).val());
                if (val) {
                    playSuccessBeep();
                    processQrLogin(val);
                    $(this).val('');
                }
            }
        });

        // Global barcode scan capture (when USB scanner scans anywhere on page)
        let barcodeBuffer = '';
        let lastCharTime = Date.now();
        $(document).on('keypress', function(e) {
            if ($(e.target).is('input[type="text"], input[type="password"], textarea')) {
                if (!$(e.target).is('#qr-scanner-input')) {
                    return; // Ignore if focused inside username/password manual form
                }
            }

            const now = Date.now();
            if (now - lastCharTime > 150) {
                barcodeBuffer = '';
            }
            lastCharTime = now;

            if (e.which === 13) {
                if (barcodeBuffer.length > 5) {
                    playSuccessBeep();
                    processQrLogin(barcodeBuffer);
                    barcodeBuffer = '';
                }
            } else {
                barcodeBuffer += String.fromCharCode(e.which);
            }
        });

        // Manual Login Form Submission
        $('form#login').on('submit', function(e){
            e.preventDefault();
            e.stopImmediatePropagation();

            var infobox = $('#infoMessage');
            infobox.html('<div class="alert alert-info py-2 px-3 text-center mb-3"><i class="fas fa-spinner fa-spin mr-2"></i>Memverifikasi akun...</div>');

            var btnsubmit = $('#submit');
            btnsubmit.attr('disabled', 'disabled').val('Memproses...');

            const arrForm = $(this).serializeArray();
            const cbtOnly = arrForm.find(function (obj) {
                return obj.name === 'cbt-only';
            });
            localStorage.setItem('saduciCBT.login', cbtOnly !== undefined ? '1' : '0');
            localStorage.setItem('garudaCBT.login', cbtOnly !== undefined ? '1' : '0');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(data){
                    infobox.empty();
                    btnsubmit.removeAttr('disabled').val('Login');
                    if(data.status){
                        infobox.html('<div class="alert alert-success py-2 px-3 text-center mb-3"><i class="fas fa-check-circle mr-2"></i>Login berhasil! Mengalihkan...</div>');
                        const isLogin = localStorage.getItem('saduciCBT.login') || localStorage.getItem('garudaCBT.login');
                        const isCbtMode = isLogin ? isLogin === '1' : false;
                        let go = base_url + data.url;
                        if (isCbtMode && data.role === 'siswa') {
                            go = 'siswa/cbt';
                        }
                        window.location.href = go;
                    }else{
                        if(data.invalid){
                            $.each(data.invalid, function(key, val){
                                $('[name="'+key+'"]').parent().addClass('has-error');
                                $('[name="'+key+'"]').siblings('.help-block').text(val);
                                if(val == ''){
                                    $('[name="'+key+'"]').parent().removeClass('has-error');
                                    $('[name="'+key+'"]').siblings('.help-block').text('');
                                }
                            });
                        }
                        if(data.failed){
                            infobox.html('<div class="alert alert-danger py-2 px-3 text-center mb-3"><i class="fas fa-exclamation-triangle mr-2"></i>' + data.failed + '</div>');
                        }
                    }
                },
                error: function() {
                    btnsubmit.removeAttr('disabled').val('Login');
                    infobox.html('<div class="alert alert-danger py-2 px-3 text-center mb-3"><i class="fas fa-exclamation-circle mr-2"></i>Gagal terhubung ke server. Coba lagi.</div>');
                }
            });
        });

        $('#toggle-password').on('click', function (e) {
            const type = $('#password').attr('type') === 'password' ? 'text' : 'password';
            $('#password').attr('type', type);
            $(this).toggleClass('fa-eye-slash fa-eye');
        });
    });
</script>

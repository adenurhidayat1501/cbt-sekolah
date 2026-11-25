<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Controller Cbtwa
 * WhatsApp Bot & Fonnte Gateway Integration
 * SMP Negeri Satu Atap 2 Cipatujah (SADUCI CBT)
 *
 * @property CI_Session $session
 * @property CI_DB_query_builder $db
 * @property CI_Input $input
 * @property CI_Output $output
 * @property Ion_auth|Ion_auth_model $ion_auth
 * @property Master_model $master
 * @property Dashboard_model $dashboard
 * @property Cbt_model $cbt
 * @property Wa_model $wa
 * @property Dropdown_model $dropdown
 */
class Cbtwa extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Wa_model', 'wa');
        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Cbt_model', 'cbt');
        $this->load->model('Master_model', 'master');
        $this->load->model('Dropdown_model', 'dropdown');

        // Pengecualian otentikasi session untuk webhook Fonnte & CLI cron scheduler
        $method = $this->router->fetch_method();
        if ($method !== 'webhook' && !($method === 'cron_schedule' && is_cli())) {
            if (!$this->ion_auth->logged_in()) {
                redirect('auth');
            } else if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru')) {
                show_error('Hanya Administrator atau Guru yang berhak mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
            }
        }
    }

    public function output_json($data, $encode = true) {
        if ($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    public function index() {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $wa_setting = $this->wa->getSetting();

        $data = [
            'user' => $user,
            'judul' => 'WhatsApp Bot & Notifikasi',
            'subjudul' => 'Integrasi Fonnte Gateway & Monitoring WA',
            'setting' => $setting,
            'wa_setting' => $wa_setting,
            'banner_info' => $this->wa->getActiveBannerInfo($wa_setting),
            'webhook_url' => base_url('cbtwa/webhook'),
        ];

        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $data['tp_active'] = $tp;
        $data['smt_active'] = $smt;

        // Ambil jadwal aktif
        $this->db->select('a.id_jadwal, a.tgl_mulai, a.tgl_selesai, a.status, b.bank_kode, d.nama_mapel');
        $this->db->from('cbt_jadwal a');
        $this->db->join('cbt_bank_soal b', 'b.id_bank=a.id_bank');
        $this->db->join('master_mapel d', 'd.id_mapel=b.bank_mapel_id', 'left');
        $this->db->where('a.id_tp', $tp->id_tp);
        $this->db->where('a.id_smt', $smt->id_smt);
        $this->db->where('a.status', '1');
        $this->db->order_by('a.tgl_mulai', 'DESC');
        $data['jadwals'] = $this->db->get()->result();

        // Hitung siswa tidak ujian hari ini
        $unattended_reports = $this->wa->getSiswaTidakUjianHariIni();
        $total_unattended = 0;
        foreach ($unattended_reports as $ur) {
            $total_unattended += $ur['total_tidak_hadir'];
        }
        $data['total_unattended'] = $total_unattended;
        $data['unattended_reports'] = $unattended_reports;

        // Ambil riwayat log WA
        $data['logs'] = $this->wa->getLogs(30);

        if ($this->ion_auth->is_admin()) {
            $data['profile'] = $this->dashboard->getProfileAdmin($user->id);
            $this->load->view('_templates/dashboard/_header', $data);
            $this->load->view('cbt/wa/data', $data);
            $this->load->view('_templates/dashboard/_footer');
        } else {
            $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $data['guru'] = $guru;
            $this->load->view('members/guru/templates/header', $data);
            $this->load->view('cbt/wa/data', $data);
            $this->load->view('members/guru/templates/footer');
        }
    }

    /**
     * Simpan konfigurasi token dan target WhatsApp
     */
    public function save_setting() {
        $api_token = trim($this->input->post('api_token', true));
        $target_group = trim($this->input->post('target_group', true));
        $target_admin = trim($this->input->post('target_admin', true));
        $status = $this->input->post('status', true) ? 1 : 0;
        $header_pesan = trim($this->input->post('header_pesan', true));
        $footer_pesan = trim($this->input->post('footer_pesan', true));
        $auto_schedule_enabled = $this->input->post('auto_schedule_enabled', true) ? 1 : 0;
        $afternoon_time = trim($this->input->post('schedule_afternoon_time', true) ?? '15:00');
        $morning_time = trim($this->input->post('schedule_morning_time', true) ?? '05:00');
        if (!preg_match('/^\d{2}:\d{2}$/', $afternoon_time)) $afternoon_time = '15:00';
        if (!preg_match('/^\d{2}:\d{2}$/', $morning_time)) $morning_time = '05:00';
        $banner_enabled = $this->input->post('banner_enabled', true) ? 1 : 0;
        $reset_banner = $this->input->post('reset_banner', true) ? 1 : 0;

        $update_data = [
            'api_token' => $api_token,
            'target_group' => $target_group,
            'target_admin' => $target_admin,
            'status' => $status,
            'header_pesan' => $header_pesan ?: 'SADUCI CBT - SMPN Satu Atap 2 Cipatujah',
            'footer_pesan' => $footer_pesan ?: 'SMP Negeri Satu Atap 2 Cipatujah',
            'auto_schedule_enabled' => $auto_schedule_enabled,
            'schedule_afternoon_time' => $afternoon_time,
            'schedule_morning_time' => $morning_time,
            'banner_enabled' => $banner_enabled,
        ];

        if ($reset_banner) {
            $update_data['banner_path'] = 'uploads/wa_banner/banner_default.jpg';
        } elseif (!empty($_FILES['banner_file']['name']) && isset($_FILES['banner_file']['error']) && $_FILES['banner_file']['error'] === UPLOAD_ERR_OK) {
            $origName = (string) $_FILES['banner_file']['name'];
            $tmpPath  = (string) $_FILES['banner_file']['tmp_name'];
            $fileSize = intval($_FILES['banner_file']['size'] ?? 0);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $this->output_json(['status' => false, 'message' => 'Format banner harus JPG, JPEG, PNG, atau WEBP']);
                return;
            }
            if ($fileSize > 2 * 1024 * 1024) {
                $this->output_json(['status' => false, 'message' => 'Ukuran file banner maksimal 2 MB']);
                return;
            }
            $imgCheck = @getimagesize($tmpPath);
            if ($imgCheck === false) {
                $this->output_json(['status' => false, 'message' => 'File yang diunggah bukan gambar valid']);
                return;
            }

            $dirRel = 'uploads/wa_banner';
            $dirAbs = FCPATH . $dirRel;
            if (!is_dir($dirAbs)) {
                @mkdir($dirAbs, 0775, true);
            }
            $newFile = 'banner_custom_' . date('Ymd_His') . '.' . $ext;
            $destAbs = $dirAbs . DIRECTORY_SEPARATOR . $newFile;
            if (!@move_uploaded_file($tmpPath, $destAbs)) {
                $this->output_json(['status' => false, 'message' => 'Gagal menyimpan file banner ke server (cek izin folder uploads/wa_banner)']);
                return;
            }
            $update_data['banner_path'] = $dirRel . '/' . $newFile;
        }

        $res = $this->wa->saveSetting($update_data);
        if ($res) {
            $this->output_json(['status' => true, 'message' => 'Pengaturan WhatsApp Fonnte berhasil disimpan']);
        } else {
            $this->output_json(['status' => false, 'message' => 'Gagal menyimpan pengaturan WhatsApp']);
        }
    }

    /**
     * Mengambil daftar grup WhatsApp yang diikuti oleh device Fonnte
     */
    public function get_groups() {
        $res = $this->wa->getFonnteGroups();
        $this->output_json($res);
    }

    /**
     * Kirim pesan tes ke nomor Admin atau Grup
     */
    public function test_send() {
        $target = trim($this->input->post('target', true));
        if (empty($target)) {
            $setting = $this->wa->getSetting();
            $target = !empty($setting->target_admin) ? $setting->target_admin : $setting->target_group;
        }

        if (empty($target)) {
            $this->output_json(['status' => false, 'message' => 'Nomor tujuan atau ID grup tidak boleh kosong']);
            return;
        }

        $message = "🤖 *TES KONEKSI BOT SADUCI CBT*\n";
        $message .= "🏫 *SMP Negeri Satu Atap 2 Cipatujah*\n";
        $message .= "────────────────────────────\n\n";
        $message .= "Halo! Layanan WhatsApp Gateway Fonnte berhasil terhubung dengan server CBT.\n\n";
        $message .= "⏱️ Waktu Kirim: " . date('d/m/Y H:i:s') . " WIB\n";
        $message .= "Status: *AKTIF & SIAP DIGUNAKAN* ✅\n\n";
        $message .= "💡 *Daftar Perintah Monitoring WA:*\n";
        $message .= "• *!absen* : Cek siswa belum/tidak ujian hari ini\n";
        $message .= "• *!rekap* : Rekap nilai & unduh file Excel hasil ujian\n";
        $message .= "• *!server* : Cek status beban & kesehatan server\n";
        $message .= "• *!token* : Cek token ujian aktif hari ini\n";
        $message .= "• *!menu* : Tampilkan menu bantuan ini\n\n";
        $message .= "_Silakan ketik perintah di atas untuk mencoba._";

        $res = $this->wa->sendFonnte($target, $message);
        $this->output_json($res);
    }

    /**
     * Broadcast langsung laporan siswa belum ujian hari ini ke grup WhatsApp
     */
    public function broadcast_unattended() {
        $setting = $this->wa->getSetting();
        $target = !empty($setting->target_group) ? $setting->target_group : $setting->target_admin;

        if (empty($target)) {
            $this->output_json(['status' => false, 'message' => 'ID Grup WhatsApp belum diatur di Pengaturan']);
            return;
        }

        $reports = $this->wa->getSiswaTidakUjianHariIni();
        $message = $this->wa->formatAbsenMessage($reports);

        $res = $this->wa->sendFonnte($target, $message);
        $this->output_json($res);
    }

    /**
     * Broadcast rekap nilai mapel tertentu beserta file Excel ke grup WhatsApp
     */
    public function broadcast_rekap() {
        $id_jadwal = $this->input->post('id_jadwal', true);
        if (empty($id_jadwal)) {
            $this->output_json(['status' => false, 'message' => 'Pilih jadwal mata pelajaran terlebih dahulu']);
            return;
        }

        $setting = $this->wa->getSetting();
        $target = !empty($setting->target_group) ? $setting->target_group : $setting->target_admin;

        if (empty($target)) {
            $this->output_json(['status' => false, 'message' => 'ID Grup WhatsApp belum diatur di Pengaturan']);
            return;
        }

        $rekap_data = $this->wa->getRekapNilaiData($id_jadwal);
        if (!$rekap_data) {
            $this->output_json(['status' => false, 'message' => 'Belum ada data nilai ujian yang tersimpan pada jadwal ini']);
            return;
        }

        // Generate file Excel
        $excel_info = $this->wa->generateExcelRekap($rekap_data);
        $excel_url = $excel_info ? $excel_info['url'] : null;
        $excel_filename = $excel_info ? $excel_info['filename'] : null;

        $message = $this->wa->formatRekapMessage($rekap_data, $excel_url);

        $res = $this->wa->sendFonnte($target, $message, $excel_url, $excel_filename);
        if ($res['status']) {
            $res['excel_url'] = $excel_url;
        }
        $this->output_json($res);
    }

    /**
     * Broadcast manual jadwal ujian & nama pengawas (hari ini atau besok) ke grup WA
     * POST /cbtwa/broadcast_schedule  (mode: today | tomorrow)
     */
    public function broadcast_schedule() {
        $mode = ($this->input->post('mode', true) === 'tomorrow') ? 'tomorrow' : 'today';
        $setting = $this->wa->getSetting();
        $target = !empty($setting->target_group) ? $setting->target_group : $setting->target_admin;

        if (empty($target)) {
            $this->output_json(['status' => false, 'message' => 'ID Grup WhatsApp belum diatur di Pengaturan']);
            return;
        }

        $targetDate = ($mode === 'tomorrow') ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');
        $message = $this->wa->formatScheduleAndProctorMessage($targetDate, $mode, false);
        $res = $this->wa->sendFonnte($target, $message);
        $this->output_json($res);
    }

    /**
     * Cron Scheduler Otomatis Harian (dipanggil oleh cron VPS tiap 5 menit):
     *   - Sore (default 15:00 WIB): kirim jadwal ujian & pengawas BESOK (jika ada jadwal)
     *   - Pagi (default 05:00 WIB): kirim jadwal ujian & pengawas HARI INI + Kode Exambro (jika ada jadwal)
     * CLI: php /var/www/saduci_cbt/index.php cbtwa cron_schedule
     */
    public function cron_schedule() {
        date_default_timezone_set('Asia/Jakarta');
        $setting = $this->wa->getSetting();

        if (!$setting || empty($setting->status) || empty($setting->api_token) || empty($setting->auto_schedule_enabled)) {
            echo "SKIP: WA Bot or auto schedule disabled\n";
            return;
        }

        $target = !empty($setting->target_group) ? $setting->target_group : '';
        if ($target === '') {
            echo "SKIP: target_group empty\n";
            return;
        }

        $today = date('Y-m-d');
        $nowMin = intval(date('H')) * 60 + intval(date('i'));

        $parseMin = function ($hhmm, $fallback) {
            if (!preg_match('/^(\d{2}):(\d{2})$/', (string) $hhmm, $m)) {
                $hhmm = $fallback;
                preg_match('/^(\d{2}):(\d{2})$/', $hhmm, $m);
            }
            return intval($m[1]) * 60 + intval($m[2]);
        };

        $morningMin   = $parseMin($setting->schedule_morning_time ?? '05:00', '05:00');
        $afternoonMin = $parseMin($setting->schedule_afternoon_time ?? '15:00', '15:00');

        // 1. Notifikasi Pagi (window 0..30 menit sejak jam setting, maks 1x/hari)
        if ($nowMin >= $morningMin && $nowMin <= ($morningMin + 30) && ($setting->last_morning_sent_date ?? '') !== $today) {
            $msg = $this->wa->formatScheduleAndProctorMessage($today, 'today', true);
            $this->wa->saveSetting(['last_morning_sent_date' => $today]);
            if ($msg !== null) {
                $this->wa->sendFonnte($target, $msg);
                echo "SENT_MORNING: {$today}\n";
            } else {
                echo "NO_EXAM_TODAY: {$today}\n";
            }
        }

        // 2. Notifikasi Sore untuk Jadwal Besok (window 0..30 menit sejak jam setting, maks 1x/hari)
        if ($nowMin >= $afternoonMin && $nowMin <= ($afternoonMin + 30) && ($setting->last_afternoon_sent_date ?? '') !== $today) {
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $msg = $this->wa->formatScheduleAndProctorMessage($tomorrow, 'tomorrow', true);
            $this->wa->saveSetting(['last_afternoon_sent_date' => $today]);
            if ($msg !== null) {
                $this->wa->sendFonnte($target, $msg);
                echo "SENT_AFTERNOON_FOR_TOMORROW: {$tomorrow}\n";
            } else {
                echo "NO_EXAM_TOMORROW: {$tomorrow}\n";
            }
        }

        echo "OK: " . date('Y-m-d H:i:s') . "\n";
    }

    /**
     * Webhook Fonnte untuk menerima pesan masuk dari WhatsApp
     * Fokus ketat: HANYA merespons perintah berawalan ! / # dari ID Grup yang terdaftar di target_group.
     * Chat pribadi dan grup lain otomatis diabaikan.
     * URL Webhook: https://domain-anda.sch.id/cbtwa/webhook
     */
    public function webhook() {
        // Ambil payload dari Fonnte (POST JSON atau POST form-data)
        $raw_input = file_get_contents('php://input');
        $data = json_decode($raw_input, true);
        if (!$data) {
            $data = $this->input->post(null, true);
        }

        $sender = '';
        if (!empty($data['sender'])) {
            $sender = trim((string) $data['sender']);
        } elseif (!empty($data['pengirim'])) {
            $sender = trim((string) $data['pengirim']);
        } elseif (!empty($data['group'])) {
            $sender = trim((string) $data['group']);
        }

        $message = '';
        if (isset($data['message'])) {
            $message = trim((string) $data['message']);
        } elseif (isset($data['pesan'])) {
            $message = trim((string) $data['pesan']);
        }

        $name = isset($data['name']) ? trim((string) $data['name']) : 'Bapak/Ibu Guru';
        $member = isset($data['member']) ? trim((string) $data['member']) : '';

        if (empty($sender) || empty($message)) {
            $this->output_json(['status' => false, 'message' => 'No payload']);
            return;
        }

        // Normalisasi teks perintah
        $clean_msg = strtolower(trim($message));
        $clean_msg = preg_replace('/\s+/', ' ', $clean_msg);
        $is_prefixed_cmd = (strlen($clean_msg) > 1 && in_array($clean_msg[0], ['!', '/', '#'], true));

        // 1. Pastikan layanan Bot WA aktif dan Target Grup WA sudah diisi
        $wa_setting = $this->wa->getSetting();
        if (!$wa_setting || empty($wa_setting->status)) {
            if ($is_prefixed_cmd) {
                $this->wa->logMessage('in', $sender, 'SADUCI CBT', $message, 'webhook', 'IGNORED', 'Bot WA sedang nonaktif (status=0)');
            }
            $this->output_json(['status' => true, 'message' => 'Bot disabled']);
            return;
        }

        // Ambil daftar ID Grup yang diizinkan (mendukung 1 atau beberapa ID dipisah koma)
        $raw_groups = trim((string) ($wa_setting->target_group ?? ''));
        if ($raw_groups === '') {
            if ($is_prefixed_cmd) {
                $this->wa->logMessage('in', $sender, 'SADUCI CBT', $message, 'webhook', 'IGNORED', 'target_group belum diisi di pengaturan');
            }
            $this->output_json(['status' => true, 'message' => 'Ignored: target_group not configured']);
            return;
        }

        $allowed_groups = [];
        foreach (explode(',', $raw_groups) as $g) {
            $g = strtolower(trim($g));
            if ($g === '') continue;
            $allowed_groups[] = $g;
            $bare = preg_replace('/@g\.us$/', '', $g);
            $allowed_groups[] = $bare;
            $allowed_groups[] = $bare . '@g.us';
        }
        $allowed_groups = array_unique($allowed_groups);

        // 2. Tolak SEMUA chat pribadi dan grup lain yang bukan termasuk Target Grup WA terdaftar
        $sender_lower = strtolower($sender);
        $sender_bare = preg_replace('/@g\.us$/', '', $sender_lower);
        $is_group = (strpos($sender_lower, '@g.us') !== false) || ($member !== '');
        $is_allowed_group = $is_group && (
            in_array($sender_lower, $allowed_groups, true) ||
            in_array($sender_bare, $allowed_groups, true)
        );

        if (!$is_allowed_group) {
            // Catat hanya jika berupa perintah berawalan ! / # dari grup agar admin bisa cek kecocokan ID Grup di Riwayat Log
            if ($is_group && $is_prefixed_cmd) {
                $this->wa->logMessage(
                    'in',
                    $sender,
                    'SADUCI CBT',
                    $message,
                    'webhook',
                    'IGNORED',
                    "ID Grup pengirim ({$sender}) tidak cocok dengan target_group ({$raw_groups})"
                );
            }
            $this->output_json(['status' => true, 'message' => 'Ignored: sender not in allowed target_group']);
            return;
        }

        // Pastikan target balasan selalu berakhiran @g.us
        $reply_target = (strpos($sender_lower, '@g.us') !== false) ? $sender : ($sender . '@g.us');

        // 3. Parse perintah eksplisit berawalan !, /, atau # (mendukung argumen seperti !reset 2425001)
        if (!$is_prefixed_cmd) {
            $this->output_json(['status' => true, 'message' => 'Ignored: regular group conversation']);
            return;
        }

        $parts = explode(' ', trim($message), 2);
        $cmd_word = strtolower(ltrim($parts[0], '!/#'));
        $cmd_arg = isset($parts[1]) ? trim($parts[1]) : '';

        $valid_cmds = [
            'absen', 'rekap', 'nilai', 'server', 'token', 'menu', 'help', 'bantuan',
            'live', 'progres', 'kode', 'exambro', 'pelanggaran', 'reset', 'selesai', 'foto',
            'jadwal', 'besok', 'siap'
        ];

        if (!in_array($cmd_word, $valid_cmds, true)) {
            $this->output_json(['status' => true, 'message' => 'Ignored: unknown command']);
            return;
        }

        // Catat webhook perintah bot yang valid
        $this->wa->logMessage('in', $reply_target, 'SADUCI CBT', $message, 'webhook', 'SUCCESS', $raw_input);

        // Router Perintah Bot Lengkap
        if ($cmd_word === 'absen') {
            $reports = $this->wa->getSiswaTidakUjianHariIni();
            $reply = $this->wa->formatAbsenMessage($reports);
            $this->wa->sendFonnte($reply_target, $reply);

        } else if ($cmd_word === 'rekap' || $cmd_word === 'nilai') {
            $today_reports = $this->wa->getSiswaTidakUjianHariIni();
            $id_jadwal_target = null;
            if (!empty($today_reports) && isset($today_reports[0]['jadwal'])) {
                $id_jadwal_target = $today_reports[0]['jadwal']->id_jadwal;
            }

            if ($id_jadwal_target) {
                $rekap_data = $this->wa->getRekapNilaiData($id_jadwal_target);
                if ($rekap_data) {
                    $excel_info = $this->wa->generateExcelRekap($rekap_data);
                    $reply = $this->wa->formatRekapMessage($rekap_data, $excel_info['url']);
                    $this->wa->sendFonnte($reply_target, $reply, $excel_info['url'], $excel_info['filename']);
                } else {
                    $reply = "ℹ️ Belum ada data nilai ujian yang tersimpan untuk jadwal hari ini.\n\nKetik *!absen* untuk cek siswa belum ujian.";
                    $this->wa->sendFonnte($reply_target, $reply);
                }
            } else {
                $reply = "ℹ️ Tidak ada jadwal ujian aktif untuk direkap saat ini.\n\nKetik *!absen* untuk cek presensi ujian.";
                $this->wa->sendFonnte($reply_target, $reply);
            }

        } else if ($cmd_word === 'server') {
            $reply = $this->wa->formatServerStatusMessage();
            $this->wa->sendFonnte($reply_target, $reply);

        } else if ($cmd_word === 'token') {
            $reply = $this->wa->formatTokenMessage();
            $this->wa->sendFonnte($reply_target, $reply);

        } else if ($cmd_word === 'live' || $cmd_word === 'progres') {
            $reply = $this->wa->formatLiveProgressMessage();
            $this->wa->sendFonnte($reply_target, $reply);

        } else if ($cmd_word === 'kode' || $cmd_word === 'exambro') {
            $reply = $this->wa->formatExambroCodeMessage();
            $this->wa->sendFonnte($reply_target, $reply);

        } else if ($cmd_word === 'pelanggaran') {
            $reply = $this->wa->formatViolationListMessage();
            $this->wa->sendFonnte($reply_target, $reply);

        } else if ($cmd_word === 'reset') {
            $reply = $this->wa->resetStudentLoginFromWa($cmd_arg, $name);
            $this->wa->sendFonnte($reply_target, $reply);

        } else if ($cmd_word === 'selesai') {
            $reply = $this->wa->forceFinishStudentFromWa($cmd_arg, $name);
            $this->wa->sendFonnte($reply_target, $reply);

        } else if ($cmd_word === 'foto') {
            $snap = $this->wa->getStudentLatestSnapshotForWa($cmd_arg);
            if ($snap['found']) {
                $this->wa->sendFonnte($reply_target, $snap['caption'], $snap['url'], $snap['filename']);
            } else {
                $this->wa->sendFonnte($reply_target, $snap['message']);
            }

        } else if ($cmd_word === 'jadwal') {
            $reply = $this->wa->formatScheduleAndProctorMessage(date('Y-m-d'), 'today', false);
            $this->wa->sendFonnte($reply_target, $reply);

        } else if ($cmd_word === 'besok') {
            $reply = $this->wa->formatScheduleAndProctorMessage(date('Y-m-d', strtotime('+1 day')), 'tomorrow', false);
            $this->wa->sendFonnte($reply_target, $reply);

        } else if ($cmd_word === 'siap') {
            $reply = $this->wa->formatPreflightMessage();
            $this->wa->sendFonnte($reply_target, $reply);

        } else {
            // Menu Bantuan Utama
            $reply = "🤖 *BOT MONITORING & KONTROL SADUCI CBT*\n";
            $reply .= "SMP Negeri Satu Atap 2 Cipatujah\n\n";
            $reply .= "Halo, *{$name}*! 👋\n";
            $reply .= "Daftar perintah aktif khusus grup panitia:\n\n";
            $reply .= "📊 *Monitoring & Jadwal:*\n";
            $reply .= "• *!live* : Progres ujian, Exambro, & Pin Home\n";
            $reply .= "• *!absen* : Daftar siswa belum ujian hari ini\n";
            $reply .= "• *!pelanggaran* : Siswa pindah layar / non-Exambro\n";
            $reply .= "• *!jadwal* : Jadwal ujian & pengawas hari ini\n";
            $reply .= "• *!besok* : Jadwal ujian & pengawas besok\n";
            $reply .= "• *!siap* : Cek kesiapan ujian (Pre-Flight)\n";
            $reply .= "• *!rekap* : Rekap nilai + kirim file Excel\n";
            $reply .= "• *!server* : Kesehatan CPU/RAM/Disk VPS\n\n";
            $reply .= "🛠️ *Kontrol Pengawas:*\n";
            $reply .= "• *!kode* : Cek kode keluar Exambro & status\n";
            $reply .= "• *!token* : Cek token ujian aktif hari ini\n";
            $reply .= "• *!reset <NIS/Nama>* : Reset login 1 siswa\n";
            $reply .= "• *!reset semua* : Reset semua siswa terkunci\n";
            $reply .= "• *!selesai <NIS>* : Paksa selesai ujian siswa\n";
            $reply .= "• *!foto <NIS/Nama>* : Kirim bukti foto terakhir\n\n";
            $reply .= "_Semua perintah wajib diawali tanda seru (!)._";

            $this->wa->sendFonnte($reply_target, $reply);
        }

        $this->output_json(['status' => true, 'message' => 'Command processed']);
    }
}

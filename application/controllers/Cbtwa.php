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

        // Pengecualian otentikasi session hanya untuk webhook Fonnte
        $method = $this->router->fetch_method();
        if ($method !== 'webhook') {
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

        $update_data = [
            'api_token' => $api_token,
            'target_group' => $target_group,
            'target_admin' => $target_admin,
            'status' => $status,
            'header_pesan' => $header_pesan ?: 'SADUCI CBT - SMPN Satu Atap 2 Cipatujah',
            'footer_pesan' => $footer_pesan ?: 'SMP Negeri Satu Atap 2 Cipatujah',
        ];

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

        $message = "🤖 *TES KONEKSI BOT SADUCI CBT*\n\n";
        $message .= "Halo! Layanan WhatsApp Gateway Fonnte berhasil terhubung dengan server *SADUCI CBT (SMP Negeri Satu Atap 2 Cipatujah)*.\n\n";
        $message .= "⏱️ Waktu Kirim: " . date('d/m/Y H:i:s') . " WIB\n";
        $message .= "Status: *AKTIF & SIAP DIGUNAKAN* ✅\n\n";
        $message .= "Gunakan tombol di bawah atau ketik *!menu* untuk melihat fitur monitoring.";

        $buttons = [
            ['id' => '!absen', 'text' => '📋 Cek Siswa Belum Ujian'],
            ['id' => '!rekap', 'text' => '📊 Rekap Nilai Mapel'],
            ['id' => '!server', 'text' => '🖥️ Status Server']
        ];

        $res = $this->wa->sendFonnte($target, $message, null, null, $buttons);
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

        $buttons = [
            ['id' => '!absen', 'text' => '🔄 Refresh Data Absen'],
            ['id' => '!rekap', 'text' => '📊 Rekap Nilai Mapel'],
            ['id' => '!server', 'text' => '🖥️ Status Server']
        ];

        $res = $this->wa->sendFonnte($target, $message, null, null, $buttons);
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

        $buttons = [
            ['id' => '!absen', 'text' => '📋 Cek Siswa Belum Ujian'],
            ['id' => '!server', 'text' => '🖥️ Status Server'],
            ['id' => '!menu', 'text' => '🏠 Menu Utama']
        ];

        $res = $this->wa->sendFonnte($target, $message, $excel_url, $excel_filename, $buttons);
        if ($res['status']) {
            $res['excel_url'] = $excel_url;
        }
        $this->output_json($res);
    }

    /**
     * Webhook Fonnte untuk menerima pesan masuk / klik tombol dari WhatsApp
     * URL Webhook: https://domain-anda.sch.id/cbtwa/webhook
     */
    public function webhook() {
        // Ambil payload dari Fonnte (POST JSON atau POST form-data)
        $raw_input = file_get_contents('php://input');
        $data = json_decode($raw_input, true);
        if (!$data) {
            $data = $this->input->post(null, true);
        }

        $sender = isset($data['sender']) ? trim($data['sender']) : '';
        $message = isset($data['message']) ? trim($data['message']) : '';
        $name = isset($data['name']) ? trim($data['name']) : 'Bapak/Ibu Guru';
        $member = isset($data['member']) ? trim($data['member']) : '';

        if (empty($sender) || empty($message)) {
            $this->output_json(['status' => false, 'message' => 'No payload']);
            return;
        }

        // Catat webhook yang masuk
        $this->wa->logMessage('in', $sender, 'SADUCI CBT', $message, 'webhook', 'SUCCESS', $raw_input);

        $cmd = strtolower(trim($message));

        // Router Perintah Bot
        if (in_array($cmd, ['!absen', 'absen', 'tidak ujian', 'belum ujian', 'cek absen', 'btn_absen', 'absen ujian'])) {
            $reports = $this->wa->getSiswaTidakUjianHariIni();
            $reply = $this->wa->formatAbsenMessage($reports);
            $buttons = [
                ['id' => '!absen', 'text' => '🔄 Refresh Data Absen'],
                ['id' => '!rekap', 'text' => '📊 Rekap Nilai Mapel'],
                ['id' => '!server', 'text' => '🖥️ Status Server']
            ];
            $this->wa->sendFonnte($sender, $reply, null, null, $buttons);

        } else if (in_array($cmd, ['!rekap', 'rekap', 'nilai', '!nilai', 'rekap nilai', 'btn_rekap', 'hasil ujian'])) {
            // Dapatkan jadwal aktif hari ini atau terbaru
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
                    $buttons = [
                        ['id' => '!absen', 'text' => '📋 Cek Siswa Belum Ujian'],
                        ['id' => '!server', 'text' => '🖥️ Status Server'],
                        ['id' => '!menu', 'text' => '🏠 Menu Utama']
                    ];
                    $this->wa->sendFonnte($sender, $reply, $excel_info['url'], $excel_info['filename'], $buttons);
                } else {
                    $reply = "ℹ️ Belum ada data nilai ujian yang tersimpan untuk jadwal hari ini.\n\nKetik *!absen* untuk cek siswa belum ujian.";
                    $this->wa->sendFonnte($sender, $reply);
                }
            } else {
                $reply = "ℹ️ Tidak ada jadwal ujian aktif untuk direkap saat ini.\n\nKetik *!absen* untuk cek presensi ujian.";
                $this->wa->sendFonnte($sender, $reply);
            }

        } else if (in_array($cmd, ['!server', 'server', 'status server', 'btn_server', 'cek server'])) {
            $reply = $this->wa->formatServerStatusMessage();
            $buttons = [
                ['id' => '!absen', 'text' => '📋 Cek Siswa Belum Ujian'],
                ['id' => '!rekap', 'text' => '📊 Rekap Nilai Mapel'],
                ['id' => '!menu', 'text' => '🏠 Menu Utama']
            ];
            $this->wa->sendFonnte($sender, $reply, null, null, $buttons);

        } else if (in_array($cmd, ['!token', 'token', 'btn_token', 'cek token'])) {
            $reply = $this->wa->formatTokenMessage();
            $buttons = [
                ['id' => '!absen', 'text' => '📋 Cek Siswa Belum Ujian'],
                ['id' => '!server', 'text' => '🖥️ Status Server'],
                ['id' => '!menu', 'text' => '🏠 Menu Utama']
            ];
            $this->wa->sendFonnte($sender, $reply, null, null, $buttons);

        } else {
            // Menu Utama Default
            $reply = "🤖 *BOT MONITORING SADUCI CBT*\n";
            $reply .= "SMP Negeri Satu Atap 2 Cipatujah\n\n";
            $reply .= "Halo, *{$name}*! 👋\n";
            $reply .= "Gunakan tombol di bawah atau ketik perintah kata kunci untuk monitoring ujian secara langsung:\n\n";
            $reply .= "👉 *!absen* : Cek siswa yang belum/tidak ujian hari ini\n";
            $reply .= "👉 *!rekap* : Rekap nilai & unduh file Excel hasil ujian\n";
            $reply .= "👉 *!server* : Cek status beban & kesehatan server\n";
            $reply .= "👉 *!token* : Cek token ujian hari ini\n\n";
            $reply .= "_Ketik perintah di atas atau langsung klik tombol pilihan di bawah:_";

            $buttons = [
                ['id' => '!absen', 'text' => '📋 Siswa Tidak Ujian'],
                ['id' => '!rekap', 'text' => '📊 Rekap Nilai Mapel'],
                ['id' => '!server', 'text' => '🖥️ Status Server']
            ];
            $this->wa->sendFonnte($sender, $reply, null, null, $buttons);
        }

        $this->output_json(['status' => true, 'message' => 'Command processed']);
    }
}

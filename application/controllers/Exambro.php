<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Exambro
 * Menyediakan endpoint integrasi monitoring device, verifikasi kode keluar admin,
 * dan sinkronisasi status perangkat ujian untuk aplikasi React Native Saduci Exambro.
 */
class Exambro extends CI_Controller {

    // Kunci Darurat Master Offline (tersimpan di server dan di tanaman di coding aplikasi mobile)
    const MASTER_EMERGENCY_CODE = 'SADUCI-EMERGENCY-2026';
    const MASTER_NUMERIC_CODE   = '992811';

    public function __construct() {
        parent::__construct();
        // CORS support untuk request dari React Native WebView / Fetch
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        
        if ($this->input->method() === 'options') {
            exit();
        }

        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Cbt_model', 'cbt');
    }

    private function output_json($data) {
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($data));
    }

    /**
     * Endpoint info server & konfigurasi Exambro
     * GET /exambro/info
     */
    public function info() {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $setting = $this->dashboard->getSetting();

        $response = [
            'status'        => true,
            'app_name'      => 'Saduci CBT Exambro Guard',
            'server_domain' => 'asas.saduci.sch.id',
            'school_name'   => $setting->sekolah ?? 'SMA DUA CIKAMPEK',
            'tp'            => $tp->tahun ?? '-',
            'smt'           => $smt->nama_smt ?? '-',
            'server_time'   => date('Y-m-d H:i:s'),
            'daily_salt'    => date('Ymd'),
        ];

        $this->output_json($response);
    }

    /**
     * Hitung kode keluar harian dinamis (Daily Admin Code)
     * Format: SDC + Hari + Bulan (Contoh tanggal 16 Nov: SDC1611)
     */
    private function get_daily_admin_code() {
        return 'SDC' . date('dm');
    }

    /**
     * Endpoint untuk memverifikasi Kode Keluar dari Pengawas/Admin
     * POST /exambro/verify_exit
     * Payload: { code, id_jadwal, id_siswa }
     */
    public function verify_exit() {
        $input = json_decode($this->input->raw_input_stream, true);
        if (!$input) {
            $input = $this->input->post();
        }

        $code = trim($input['code'] ?? '');
        $id_jadwal = $input['id_jadwal'] ?? null;
        $id_siswa = $input['id_siswa'] ?? null;

        if (empty($code)) {
            $this->output_json([
                'status'  => false,
                'message' => 'Kode keluar tidak boleh kosong.'
            ]);
            return;
        }

        $code_upper = strtoupper($code);
        $isValid = false;
        $matched_type = '';

        // 1. Cek Master Emergency Backup Code (yang tertanam di coding)
        if ($code_upper === self::MASTER_EMERGENCY_CODE || $code === self::MASTER_NUMERIC_CODE) {
            $isValid = true;
            $matched_type = 'MASTER_EMERGENCY_CODE';
        }

        // 2. Cek Daily Dynamic Admin Code (SDC + TanggalBulan)
        if (!$isValid && $code_upper === strtoupper($this->get_daily_admin_code())) {
            $isValid = true;
            $matched_type = 'DAILY_ADMIN_CODE';
        }

        // 3. Cek Token Ujian Jadwal Aktif
        if (!$isValid && !empty($id_jadwal)) {
            $jadwal = $this->db->where('id_jadwal', $id_jadwal)->get('cbt_jadwal')->row();
            if ($jadwal && !empty($jadwal->token) && strtoupper($jadwal->token) === $code_upper) {
                $isValid = true;
                $matched_type = 'EXAM_TOKEN_CODE';
            }
        }

        // 4. Cek Password Administrator CBT
        if (!$isValid) {
            $adminUser = $this->db->where('username', 'admin')->get('users')->row();
            if ($adminUser && $this->ion_auth->hash_password_db($adminUser->id, $code)) {
                $isValid = true;
                $matched_type = 'ADMIN_SUPERUSER_PASS';
            }
        }

        // Log verifikasi jika siswa teridentifikasi
        if (!empty($id_siswa) && !empty($id_jadwal)) {
            $this->db->insert('log_ujian', [
                'log_time'  => date('Y-m-d H:i:s'),
                'id_siswa'  => $id_siswa,
                'id_jadwal' => $id_jadwal,
                'log_type'  => $isValid ? 2 : 1,
                'log_desc'  => $isValid ? "Exambro Unlocked via {$matched_type}" : "Gagal Unlock Exambro (Kode Salah: {$code})",
                'address'   => $this->input->ip_address(),
                'agent'     => 'SaduciExambro/2.0',
                'device'    => 'Android Kiosk',
                'reset'     => 0
            ]);
        }

        if ($isValid) {
            $this->output_json([
                'status'       => true,
                'message'      => 'Kode keluar valid. Mode Kiosk Exambro berhasil dibuka.',
                'matched_type' => $matched_type,
                'timestamp'    => date('Y-m-d H:i:s')
            ]);
        } else {
            $this->output_json([
                'status'  => false,
                'message' => 'Kode keluar salah atau tidak diizinkan oleh sistem.'
            ]);
        }
    }

    /**
     * Endpoint Device Heartbeat & Monitoring Telemetry
     * Mengirim status baterai, sinyal/koneksi, model HP, dan log pelanggaran
     * POST /exambro/heartbeat
     */
    public function heartbeat() {
        $input = json_decode($this->input->raw_input_stream, true);
        if (!$input) {
            $input = $this->input->post();
        }

        $id_siswa        = $input['id_siswa'] ?? null;
        $id_jadwal       = $input['id_jadwal'] ?? null;
        $battery_level   = isset($input['battery_level']) ? round($input['battery_level'] * 100) : '-';
        $is_charging     = !empty($input['is_charging']) ? 'Charging' : 'Battery';
        $network_type    = $input['network_type'] ?? 'Unknown';
        $device_model    = trim($input['device_model'] ?? 'Android Device');
        $device_name     = trim($input['device_name'] ?? '');
        $device_display  = trim($input['device_display'] ?? '');
        $os_version      = trim($input['os_version'] ?? 'Android');
        $violation_count = intval($input['violation_count'] ?? 0);
        $app_version     = $input['app_version'] ?? '1.0.0';

        // Susun nama tampilan perangkat siswa yang rapi & bebas karakter kurung siku
        if ($device_display === '') {
            if ($device_name !== '' && strcasecmp($device_name, $device_model) !== 0) {
                $device_display = $device_model . ' (' . $device_name . ')';
            } else {
                $device_display = $device_model;
            }
        }
        $clean_device = str_replace(['[', ']', '|'], '', $device_display);
        $clean_os     = str_replace(['[', ']', '|'], '', $os_version);

        if (!empty($id_siswa) && !empty($id_jadwal)) {
            // Update log_ujian yang sedang aktif (simpan di log_desc agar tidak merusak validasi reset_login)
            $logKey = $id_siswa . '0' . $id_jadwal . '1';
            $existingLog = $this->db->where('id_log', $logKey)->get('log_ujian')->row();

            $statusText = "Exambro v{$app_version} [Dev: {$clean_device} | OS: {$clean_os}] [Bat: {$battery_level}% ({$is_charging}) | Net: {$network_type}]";
            if ($violation_count > 0) {
                $statusText .= " [Pindah Layar/Switch: {$violation_count}x]";
            }

            if ($existingLog) {
                $this->db->set('log_desc', $statusText);
                $this->db->where('id_log', $logKey);
                $this->db->update('log_ujian');
            }
        }

        $this->output_json([
            'status'      => true,
            'message'     => 'Device heartbeat telemetry received.',
            'server_time' => date('Y-m-d H:i:s'),
            'synced'      => true
        ]);
    }

    /**
     * Mendapatkan kode keluar harian untuk Pengawas/Guru di dashboard admin
     * GET /exambro/get_daily_code
     */
    public function get_daily_code() {
        if (!$this->ion_auth->logged_in()) {
            $this->output_json(['status' => false, 'message' => 'Unauthorized']);
            return;
        }

        $this->output_json([
            'status'             => true,
            'daily_admin_code'   => $this->get_daily_admin_code(),
            'master_backup_code' => self::MASTER_EMERGENCY_CODE,
            'quick_numeric_code' => self::MASTER_NUMERIC_CODE,
            'instructions'       => 'Berikan kode harian kepada pengawas jika siswa membutuhkan izin darurat keluar dari aplikasi Exambro.'
        ]);
    }
}

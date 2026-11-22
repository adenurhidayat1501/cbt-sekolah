<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Exambro
 * Menyediakan endpoint integrasi monitoring device, verifikasi kode keluar admin,
 * pengaturan terpusat, remote command pengawas, dan auto-snapshot kamera depan
 * untuk aplikasi React Native Saduci Exambro.
 */
class Exambro extends CI_Controller {

    // Kunci Darurat Master Offline (tersimpan di server dan ditanam di coding aplikasi mobile)
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
        $this->load->model('Exambro_model', 'exambro');
    }

    private function output_json($data) {
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($data));
    }

    /**
     * Endpoint info server & konfigurasi Exambro (termasuk min_version, alarm, snapshot)
     * GET /exambro/info
     */
    public function info() {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $setting = $this->dashboard->getSetting();
        $ex = $this->exambro->getSetting();

        $response = [
            'status'                => true,
            'app_name'              => 'Saduci CBT Exambro Guard',
            'server_domain'         => 'asas.saduci.sch.id',
            'school_name'           => $setting->sekolah ?? 'SMPN SATU ATAP 2 CIPATUJAH',
            'tp'                    => $tp->tahun ?? '-',
            'smt'                   => $smt->nama_smt ?? '-',
            'server_time'           => date('Y-m-d H:i:s'),
            'daily_salt'            => date('Ymd'),
            'min_version'           => $ex->min_version ?? '1.0.0',
            'apk_download_url'      => $ex->apk_download_url ?? '',
            'require_exambro'       => !empty($ex->require_exambro),
            'alarm_enabled'         => !empty($ex->alarm_enabled),
            'snapshot_enabled'      => !empty($ex->snapshot_enabled),
            'snapshot_interval_min' => max(1, intval($ex->snapshot_interval_min ?? 3)),
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

        // 0. Cek Kode Keluar Kustom yang diatur Admin di Web (/cbtlive)
        $exSetting = $this->exambro->getSetting();
        $customCode = strtoupper(trim($exSetting->custom_exit_code ?? ''));
        if ($customCode !== '' && $code_upper === $customCode) {
            $isValid = true;
            $matched_type = 'CUSTOM_ADMIN_CODE';
        }

        // 1. Cek Master Emergency Backup Code (yang tertanam di coding)
        if (!$isValid && ($code_upper === self::MASTER_EMERGENCY_CODE || $code === self::MASTER_NUMERIC_CODE)) {
            $isValid = true;
            $matched_type = 'MASTER_EMERGENCY_CODE';
        }

        // 2. Cek Daily Dynamic Admin Code (SDC + TanggalBulan) — berlaku jika tidak ada custom override atau tetap valid
        if (!$isValid && $customCode === '' && $code_upper === strtoupper($this->get_daily_admin_code())) {
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
     * Endpoint Device Heartbeat & Monitoring Telemetry + Remote Commands
     * POST /exambro/heartbeat
     */
    public function heartbeat() {
        $input = json_decode($this->input->raw_input_stream, true);
        if (!$input) {
            $input = $this->input->post();
        }

        $id_siswa         = $input['id_siswa'] ?? null;
        $id_jadwal        = $input['id_jadwal'] ?? null;
        $battery_level    = isset($input['battery_level']) ? round($input['battery_level'] * 100) : '-';
        $is_charging      = !empty($input['is_charging']) ? 'Charging' : 'Battery';
        $network_type     = $input['network_type'] ?? 'Unknown';
        $device_model     = trim($input['device_model'] ?? 'Android Device');
        $device_name      = trim($input['device_name'] ?? '');
        $device_display   = trim($input['device_display'] ?? '');
        $os_version       = trim($input['os_version'] ?? 'Android');
        $is_screen_pinned = !empty($input['is_screen_pinned']);
        $violation_count  = intval($input['violation_count'] ?? 0);
        $app_version      = $input['app_version'] ?? '1.0.0';

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
        $pinLabel     = $is_screen_pinned ? 'AKTIF' : 'BELUM';

        $pendingCommands = [];

        if (!empty($id_siswa) && !empty($id_jadwal)) {
            $logKey = $id_siswa . '0' . $id_jadwal . '1';
            $existingLog = $this->db->where('id_log', $logKey)->get('log_ujian')->row();

            $statusText = "Exambro v{$app_version} [Dev: {$clean_device} | OS: {$clean_os}] [Pin: {$pinLabel}] [Bat: {$battery_level}% ({$is_charging}) | Net: {$network_type}]";
            if ($violation_count > 0) {
                $statusText .= " [Pindah Layar/Switch: {$violation_count}x]";
            }

            if ($existingLog) {
                $this->db->set('log_desc', $statusText);
                $this->db->where('id_log', $logKey);
                $this->db->update('log_ujian');
            }

            // Kirim notif WA otomatis jika pelanggaran mencapai >= 3x
            if ($violation_count >= 3) {
                $this->exambro->maybeAlertViolation($id_siswa, $id_jadwal, $violation_count, $clean_device);
            }

            // Ambil perintah jarak jauh dari pengawas (WARN / UNLOCK / RELOAD / SNAPSHOT)
            $pendingCommands = $this->exambro->popPendingCommands($id_siswa, $id_jadwal);
        }

        $ex = $this->exambro->getSetting();

        $this->output_json([
            'status'                => true,
            'message'               => 'Device heartbeat telemetry received.',
            'server_time'           => date('Y-m-d H:i:s'),
            'synced'                => true,
            'alarm_enabled'         => !empty($ex->alarm_enabled),
            'snapshot_enabled'      => !empty($ex->snapshot_enabled),
            'snapshot_interval_min' => max(1, intval($ex->snapshot_interval_min ?? 3)),
            'min_version'           => $ex->min_version ?? '1.0.0',
            'apk_download_url'      => $ex->apk_download_url ?? '',
            'pending_commands'      => $pendingCommands,
        ]);
    }

    /**
     * Endpoint upload foto Auto-Snapshot ringan dari kamera depan siswa (max ~150KB, menimpa foto lama)
     * POST /exambro/upload_snapshot
     * Payload JSON: { id_siswa, id_jadwal, image_base64, reason }
     */
    public function upload_snapshot() {
        $ex = $this->exambro->getSetting();
        if (empty($ex->snapshot_enabled)) {
            $this->output_json(['status' => false, 'message' => 'Fitur snapshot sedang dinonaktifkan pengawas.']);
            return;
        }

        $input = json_decode($this->input->raw_input_stream, true);
        if (!$input) {
            $input = $this->input->post();
        }

        $id_siswa = intval($input['id_siswa'] ?? 0);
        $id_jadwal = intval($input['id_jadwal'] ?? 0);
        $image_base64 = $input['image_base64'] ?? '';

        if ($id_siswa <= 0 || $id_jadwal <= 0 || empty($image_base64)) {
            $this->output_json(['status' => false, 'message' => 'Parameter snapshot tidak lengkap.']);
            return;
        }

        // Bersihkan prefix data:image/jpeg;base64,
        if (preg_match('/^data:image\/(jpeg|jpg);base64,/i', $image_base64)) {
            $image_base64 = preg_replace('/^data:image\/(jpeg|jpg);base64,/i', '', $image_base64);
        }

        $binary = base64_decode($image_base64, true);
        if ($binary === false || strlen($binary) < 500 || strlen($binary) > 200 * 1024) {
            $this->output_json(['status' => false, 'message' => 'Format/ukuran gambar snapshot tidak valid (maks 200KB).']);
            return;
        }

        // Validasi header magic bytes JPEG (FF D8 FF)
        if (substr($binary, 0, 3) !== "\xFF\xD8\xFF") {
            $this->output_json(['status' => false, 'message' => 'Hanya gambar JPEG yang diizinkan.']);
            return;
        }

        $dir = $this->exambro->getSnapshotDir();
        $filename = "snap_{$id_jadwal}_{$id_siswa}.jpg";
        $written = @file_put_contents($dir . $filename, $binary);

        if ($written === false) {
            $this->output_json(['status' => false, 'message' => 'Gagal menyimpan snapshot di server.']);
            return;
        }

        $this->output_json([
            'status'      => true,
            'message'     => 'Snapshot diterima.',
            'size_bytes'  => strlen($binary),
            'server_time' => date('H:i:s'),
        ]);
    }

    /**
     * Simpan pengaturan kontrol Exambro dari panel Admin/Guru (/cbtlive)
     * POST /exambro/save_setting
     */
    public function save_setting() {
        if (!$this->ion_auth->logged_in() || (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru'))) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $data = [
            'require_exambro'       => $this->input->post('require_exambro') ? 1 : 0,
            'custom_exit_code'      => strtoupper(trim($this->input->post('custom_exit_code', true) ?? '')),
            'min_version'           => trim($this->input->post('min_version', true) ?? '1.0.0') ?: '1.0.0',
            'apk_download_url'      => trim($this->input->post('apk_download_url', true) ?? ''),
            'alarm_enabled'         => $this->input->post('alarm_enabled') ? 1 : 0,
            'snapshot_enabled'      => $this->input->post('snapshot_enabled') ? 1 : 0,
            'snapshot_interval_min' => max(1, min(30, intval($this->input->post('snapshot_interval_min') ?: 3))),
            'wa_alert_enabled'      => $this->input->post('wa_alert_enabled') ? 1 : 0,
            'wa_cpu_threshold'      => max(50, min(99, intval($this->input->post('wa_cpu_threshold') ?: 85))),
        ];

        $ok = $this->exambro->saveSetting($data);
        $this->output_json([
            'status'           => (bool) $ok,
            'message'          => $ok ? 'Pengaturan Saduci Exambro berhasil disimpan!' : 'Gagal menyimpan pengaturan.',
            'active_exit_code' => $this->exambro->getActiveExitCode(),
            'setting'          => $this->exambro->getSetting(),
        ]);
    }

    /**
     * Kirim perintah jarak jauh dari Pengawas ke HP Siswa (/cbtlive)
     * POST /exambro/send_command
     * Payload: { id_siswa, id_jadwal, command (WARN|UNLOCK|RELOAD|SNAPSHOT), message }
     */
    public function send_command() {
        if (!$this->ion_auth->logged_in() || (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru'))) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $id_siswa = intval($this->input->post('id_siswa'));
        $id_jadwal = intval($this->input->post('id_jadwal'));
        $command = strtoupper(trim($this->input->post('command', true) ?? ''));
        $message = trim($this->input->post('message', true) ?? '');

        $allowed = ['WARN', 'UNLOCK', 'RELOAD', 'SNAPSHOT'];
        if ($id_siswa <= 0 || $id_jadwal <= 0 || !in_array($command, $allowed, true)) {
            $this->output_json(['status' => false, 'message' => 'Perintah tidak valid.']);
            return;
        }

        $user = $this->ion_auth->user()->row();
        $sender = $user ? $user->username : 'pengawas';

        $ok = $this->exambro->queueCommand($id_siswa, $id_jadwal, $command, $message, $sender);
        $this->output_json([
            'status'  => (bool) $ok,
            'message' => $ok
                ? "Perintah {$command} telah dikirim ke HP siswa (dieksekusi otomatis pada heartbeat berikutnya ≤30 detik)."
                : 'Gagal mengirim perintah.',
        ]);
    }

    /**
     * Mendapatkan kode keluar aktif & pengaturan Exambro untuk dashboard admin
     * GET /exambro/get_daily_code
     */
    public function get_daily_code() {
        if (!$this->ion_auth->logged_in()) {
            $this->output_json(['status' => false, 'message' => 'Unauthorized']);
            return;
        }

        $this->output_json([
            'status'             => true,
            'active_exit_code'   => $this->exambro->getActiveExitCode(),
            'daily_admin_code'   => $this->get_daily_admin_code(),
            'master_backup_code' => self::MASTER_EMERGENCY_CODE,
            'quick_numeric_code' => self::MASTER_NUMERIC_CODE,
            'setting'            => $this->exambro->getSetting(),
            'instructions'       => 'Berikan kode keluar aktif kepada pengawas jika siswa membutuhkan izin keluar dari aplikasi Exambro.'
        ]);
    }
}

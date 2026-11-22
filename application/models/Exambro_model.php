<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model Exambro_model
 * Pengaturan terpusat, antrean perintah jarak jauh (remote command),
 * pengelolaan snapshot kamera depan, dan alert WA untuk Saduci Exambro.
 */
class Exambro_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->initTables();
    }

    private function initTables() {
        if (!$this->db->table_exists('cbt_exambro_setting')) {
            $sql = "CREATE TABLE IF NOT EXISTS `cbt_exambro_setting` (
                `id_setting` int(11) NOT NULL AUTO_INCREMENT,
                `require_exambro` tinyint(1) NOT NULL DEFAULT 0,
                `custom_exit_code` varchar(100) NULL DEFAULT '',
                `min_version` varchar(20) NOT NULL DEFAULT '1.0.0',
                `apk_download_url` varchar(500) NULL DEFAULT '',
                `alarm_enabled` tinyint(1) NOT NULL DEFAULT 1,
                `snapshot_enabled` tinyint(1) NOT NULL DEFAULT 0,
                `snapshot_interval_min` int(11) NOT NULL DEFAULT 3,
                `wa_alert_enabled` tinyint(1) NOT NULL DEFAULT 1,
                `wa_cpu_threshold` int(11) NOT NULL DEFAULT 85,
                `last_server_alert_at` datetime NULL DEFAULT NULL,
                `updated_at` datetime NULL DEFAULT NULL,
                PRIMARY KEY (`id_setting`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->db->query($sql);

            $this->db->insert('cbt_exambro_setting', [
                'id_setting' => 1,
                'require_exambro' => 0,
                'custom_exit_code' => '',
                'min_version' => '1.0.0',
                'apk_download_url' => '',
                'alarm_enabled' => 1,
                'snapshot_enabled' => 0,
                'snapshot_interval_min' => 3,
                'wa_alert_enabled' => 1,
                'wa_cpu_threshold' => 85,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (!$this->db->table_exists('cbt_exambro_command')) {
            $sqlCmd = "CREATE TABLE IF NOT EXISTS `cbt_exambro_command` (
                `id_cmd` int(11) NOT NULL AUTO_INCREMENT,
                `id_siswa` int(11) NOT NULL,
                `id_jadwal` int(11) NOT NULL,
                `command` varchar(30) NOT NULL,
                `message` text NULL,
                `created_by` varchar(100) NULL DEFAULT '',
                `created_at` datetime NOT NULL,
                `executed_at` datetime NULL DEFAULT NULL,
                PRIMARY KEY (`id_cmd`),
                KEY `idx_exambro_cmd_lookup` (`id_siswa`, `id_jadwal`, `executed_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->db->query($sqlCmd);
        }
    }

    public function getSetting() {
        $row = $this->db->where('id_setting', 1)->get('cbt_exambro_setting')->row();
        if (!$row) {
            $default = [
                'id_setting' => 1,
                'require_exambro' => 0,
                'custom_exit_code' => '',
                'min_version' => '1.0.0',
                'apk_download_url' => '',
                'alarm_enabled' => 1,
                'snapshot_enabled' => 0,
                'snapshot_interval_min' => 3,
                'wa_alert_enabled' => 1,
                'wa_cpu_threshold' => 85,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $this->db->insert('cbt_exambro_setting', $default);
            return (object) $default;
        }
        return $row;
    }

    public function saveSetting($data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $exists = $this->db->where('id_setting', 1)->get('cbt_exambro_setting')->row();
        if ($exists) {
            return $this->db->where('id_setting', 1)->update('cbt_exambro_setting', $data);
        }
        $data['id_setting'] = 1;
        return $this->db->insert('cbt_exambro_setting', $data);
    }

    public function getActiveExitCode() {
        $setting = $this->getSetting();
        $custom = trim($setting->custom_exit_code ?? '');
        if ($custom !== '') {
            return strtoupper($custom);
        }
        return 'SDC' . date('dm');
    }

    public function isExambroUserAgent() {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return (stripos($ua, 'SaduciExambro') !== false);
    }

    public function queueCommand($id_siswa, $id_jadwal, $command, $message = '', $created_by = '') {
        return $this->db->insert('cbt_exambro_command', [
            'id_siswa' => intval($id_siswa),
            'id_jadwal' => intval($id_jadwal),
            'command' => strtoupper(trim($command)),
            'message' => trim($message),
            'created_by' => trim($created_by),
            'created_at' => date('Y-m-d H:i:s'),
            'executed_at' => null,
        ]);
    }

    public function popPendingCommands($id_siswa, $id_jadwal) {
        $cutoff = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $rows = $this->db
            ->where('id_siswa', intval($id_siswa))
            ->where('id_jadwal', intval($id_jadwal))
            ->where('executed_at IS NULL', null, false)
            ->where('created_at >=', $cutoff)
            ->order_by('id_cmd', 'ASC')
            ->get('cbt_exambro_command')
            ->result();

        if (!empty($rows)) {
            $ids = array_map(function ($r) {
                return intval($r->id_cmd);
            }, $rows);
            $this->db->where_in('id_cmd', $ids)->update('cbt_exambro_command', [
                'executed_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return array_map(function ($r) {
            return [
                'id_cmd' => intval($r->id_cmd),
                'command' => $r->command,
                'message' => $r->message ?? '',
                'created_at' => $r->created_at,
            ];
        }, $rows);
    }

    public function getSnapshotDir() {
        $dir = FCPATH . 'uploads/exambro_snapshots/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $htaccess = $dir . '.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php3|php4|php5|phar)$\">\n    Require all denied\n</FilesMatch>\n");
        }
        return $dir;
    }

    public function getSnapshotInfo($id_jadwal, $id_siswa) {
        $filename = 'snap_' . intval($id_jadwal) . '_' . intval($id_siswa) . '.jpg';
        $fullpath = FCPATH . 'uploads/exambro_snapshots/' . $filename;
        if (file_exists($fullpath)) {
            $mtime = @filemtime($fullpath);
            return [
                'exists' => true,
                'url' => base_url('uploads/exambro_snapshots/' . $filename) . '?t=' . $mtime,
                'updated_at' => date('H:i:s', $mtime),
            ];
        }
        return null;
    }

    public function sendWaAlert($message) {
        try {
            $exambroSetting = $this->getSetting();
            if (empty($exambroSetting->wa_alert_enabled)) {
                return false;
            }
            $this->load->model('Wa_model', 'wa');
            $waSetting = $this->wa->getSetting();
            if (!$waSetting || empty($waSetting->status) || empty($waSetting->api_token)) {
                return false;
            }
            $target = !empty($waSetting->target_group) ? $waSetting->target_group : $waSetting->target_admin;
            if (empty($target)) {
                return false;
            }
            $header = !empty($waSetting->header_pesan) ? "*{$waSetting->header_pesan}*\n" : "*SADUCI CBT ALERT*\n";
            $this->wa->sendFonnte($target, $header . $message);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function maybeAlertServerLoad($cpu_pct, $ram_pct, $live_students) {
        $setting = $this->getSetting();
        if (empty($setting->wa_alert_enabled)) {
            return;
        }
        $threshold = max(50, min(99, intval($setting->wa_cpu_threshold ?: 85)));
        if ($cpu_pct < $threshold && $ram_pct < $threshold) {
            return;
        }

        // Cooldown 15 menit agar tidak spam grup WA
        if (!empty($setting->last_server_alert_at)) {
            $diff = time() - strtotime($setting->last_server_alert_at);
            if ($diff < 900) {
                return;
            }
        }

        $this->db->where('id_setting', 1)->update('cbt_exambro_setting', [
            'last_server_alert_at' => date('Y-m-d H:i:s'),
        ]);

        $msg = "⚠️ *PERINGATAN BEBAN SERVER VPS*\n"
             . "CPU Load: *{$cpu_pct}%*\n"
             . "RAM Usage: *{$ram_pct}%*\n"
             . "Siswa Live Ujian: *{$live_students}*\n"
             . "Waktu: " . date('d/m/Y H:i:s') . "\n\n"
             . "_Mohon pantau dashboard Live Monitor (/cbtlive)._";
        $this->sendWaAlert($msg);
    }

    public function maybeAlertViolation($id_siswa, $id_jadwal, $violation_count, $device_display) {
        if ($violation_count < 3) {
            return;
        }
        $setting = $this->getSetting();
        if (empty($setting->wa_alert_enabled)) {
            return;
        }

        $cacheFile = APPPATH . 'cache/exambro_viol_' . intval($id_jadwal) . '_' . intval($id_siswa) . '.txt';
        $lastAlerted = file_exists($cacheFile) ? intval(@file_get_contents($cacheFile)) : 0;
        if ($violation_count <= $lastAlerted) {
            return;
        }
        @file_put_contents($cacheFile, (string) $violation_count);

        $siswa = $this->db->select('nama, nis')->where('id_siswa', intval($id_siswa))->get('master_siswa')->row();
        $namaSiswa = $siswa ? $siswa->nama : "ID #{$id_siswa}";
        $nisSiswa = $siswa ? $siswa->nis : '-';

        $msg = "🚨 *PERINGATAN PELANGGARAN EXAMBRO*\n"
             . "Siswa: *{$namaSiswa}* (NIS: {$nisSiswa})\n"
             . "Pelanggaran Pindah Layar: *{$violation_count}x* (Batas tercapai!)\n"
             . "Perangkat: {$device_display}\n"
             . "Waktu: " . date('H:i:s') . "\n\n"
             . "_Silakan cek pengawas ruang atau pantau di /cbtlive._";
        $this->sendWaAlert($msg);
    }
}

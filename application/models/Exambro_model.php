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
        $idx = $dir . 'index.html';
        if (!file_exists($idx)) {
            @file_put_contents($idx, '<!DOCTYPE html><html><head><title>403</title></head><body>Forbidden</body></html>');
        }
        return $dir;
    }

    private function slugifySegment($text, $fallback = 'item') {
        $clean = preg_replace('/[^a-zA-Z0-9]+/', '_', trim((string) $text));
        $clean = trim($clean, '_');
        if ($clean === '') {
            return $fallback;
        }
        return substr($clean, 0, 42);
    }

    /**
     * Dapatkan (atau buat) folder mapel untuk pengelompokan bukti foto:
     * uploads/exambro_snapshots/{id_jadwal}_{nama_mapel}/
     */
    public function getJadwalSnapshotFolder($id_jadwal, $create = false) {
        $baseDir = $this->getSnapshotDir();
        $id_jadwal = intval($id_jadwal);
        $prefix = $id_jadwal . '_';

        // Cari folder existing dengan prefix id_jadwal_ agar konsisten
        $existing = glob($baseDir . $prefix . '*', GLOB_ONLYDIR);
        if (!empty($existing)) {
            $folderName = basename($existing[0]);
            return [
                'abs_dir' => $baseDir . $folderName . '/',
                'rel_dir' => 'uploads/exambro_snapshots/' . $folderName . '/',
                'folder_name' => $folderName,
            ];
        }

        if (!$create) {
            return null;
        }

        // Ambil nama mapel & kode bank dari DB
        $row = $this->db
            ->select('a.id_jadwal, b.bank_kode, d.nama_mapel')
            ->from('cbt_jadwal a')
            ->join('cbt_bank_soal b', 'b.id_bank=a.id_bank', 'left')
            ->join('master_mapel d', 'd.id_mapel=b.bank_mapel_id', 'left')
            ->where('a.id_jadwal', $id_jadwal)
            ->get()
            ->row();

        $label = $row
            ? trim(($row->nama_mapel ?? '') . ' ' . ($row->bank_kode ?? ''))
            : 'Mapel';
        $folderName = $prefix . $this->slugifySegment($label, 'Mapel');
        $absDir = $baseDir . $folderName . '/';

        if (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }
        if (!file_exists($absDir . 'index.html')) {
            @file_put_contents($absDir . 'index.html', '');
        }

        return [
            'abs_dir' => $absDir,
            'rel_dir' => 'uploads/exambro_snapshots/' . $folderName . '/',
            'folder_name' => $folderName,
        ];
    }

    /**
     * Dapatkan (atau buat) folder siswa di dalam folder mapel:
     * uploads/exambro_snapshots/{id_jadwal}_{mapel}/{id_siswa}_{nis}_{nama_siswa}/
     */
    public function getStudentSnapshotFolder($id_jadwal, $id_siswa, $create = false) {
        $jadwalFolder = $this->getJadwalSnapshotFolder($id_jadwal, $create);
        if (!$jadwalFolder) {
            return null;
        }

        $id_siswa = intval($id_siswa);
        $prefix = $id_siswa . '_';
        $existing = glob($jadwalFolder['abs_dir'] . $prefix . '*', GLOB_ONLYDIR);
        if (!empty($existing)) {
            $studentFolderName = basename($existing[0]);
            return [
                'abs_dir' => $jadwalFolder['abs_dir'] . $studentFolderName . '/',
                'rel_dir' => $jadwalFolder['rel_dir'] . $studentFolderName . '/',
                'folder_name' => $studentFolderName,
                'jadwal_folder' => $jadwalFolder['folder_name'],
            ];
        }

        if (!$create) {
            return null;
        }

        $siswa = $this->db
            ->select('nis, nama')
            ->where('id_siswa', $id_siswa)
            ->get('master_siswa')
            ->row();

        $nisSlug = $siswa && !empty($siswa->nis) ? $this->slugifySegment($siswa->nis, '0') : '0';
        $namaSlug = $siswa && !empty($siswa->nama) ? $this->slugifySegment($siswa->nama, 'Siswa') : 'Siswa';
        $studentFolderName = $prefix . $nisSlug . '_' . $namaSlug;
        $absDir = $jadwalFolder['abs_dir'] . $studentFolderName . '/';

        if (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }
        if (!file_exists($absDir . 'index.html')) {
            @file_put_contents($absDir . 'index.html', '');
        }

        return [
            'abs_dir' => $absDir,
            'rel_dir' => $jadwalFolder['rel_dir'] . $studentFolderName . '/',
            'folder_name' => $studentFolderName,
            'jadwal_folder' => $jadwalFolder['folder_name'],
        ];
    }

    /**
     * Simpan foto bukti ujian (tidak menimpa foto lama, dikelompokkan per mapel -> per siswa)
     * Batas pengaman: maks 60 foto/siswa/mapel (jika lebih, hapus foto 'periodic' paling lama, bukti 'violation' selalu dijaga).
     */
    public function saveStudentSnapshot($id_jadwal, $id_siswa, $binary, $reason = 'periodic') {
        $folder = $this->getStudentSnapshotFolder($id_jadwal, $id_siswa, true);
        if (!$folder) {
            return false;
        }

        $safeReason = preg_replace('/[^a-z0-9_]/i', '', strtolower((string) $reason));
        if (!in_array($safeReason, ['exam_start', 'periodic', 'violation', 'proctor_request'], true)) {
            $safeReason = 'periodic';
        }

        $filename = date('Ymd_His') . '_' . $safeReason . '.jpg';
        $target = $folder['abs_dir'] . $filename;

        // Jika di detik yang sama sudah ada file, tambah suffix angka
        if (file_exists($target)) {
            $filename = date('Ymd_His') . '_' . mt_rand(10, 99) . '_' . $safeReason . '.jpg';
            $target = $folder['abs_dir'] . $filename;
        }

        $written = @file_put_contents($target, $binary);
        if ($written === false) {
            return false;
        }

        // Batas aman 60 foto per siswa per mapel agar disk tidak bisa penuh tak terkendali
        $allFiles = glob($folder['abs_dir'] . '*.jpg');
        if (is_array($allFiles) && count($allFiles) > 60) {
            sort($allFiles);
            $toDelete = count($allFiles) - 60;
            foreach ($allFiles as $f) {
                if ($toDelete <= 0) break;
                // Prioritaskan hapus foto periodic lama; jaga bukti violation & exam_start
                if (stripos(basename($f), '_periodic.jpg') !== false) {
                    @unlink($f);
                    $toDelete--;
                }
            }
        }

        return [
            'filename' => $filename,
            'rel_path' => $folder['rel_dir'] . $filename,
            'folder' => $folder['jadwal_folder'] . '/' . $folder['folder_name'],
            'size_bytes' => strlen($binary),
        ];
    }

    /**
     * Ambil seluruh daftar bukti foto seorang siswa pada jadwal/mapel tertentu (terbaru dulu)
     */
    public function getStudentSnapshots($id_jadwal, $id_siswa) {
        $items = [];
        $folder = $this->getStudentSnapshotFolder($id_jadwal, $id_siswa, false);

        if ($folder && is_dir($folder['abs_dir'])) {
            $files = glob($folder['abs_dir'] . '*.jpg');
            if (is_array($files)) {
                rsort($files); // Nama file Ymd_His_* -> rsort = terbaru di atas
                foreach ($files as $fullpath) {
                    $fname = basename($fullpath);
                    $mtime = @filemtime($fullpath) ?: time();
                    $sizeKb = round((@filesize($fullpath) ?: 0) / 1024, 1);

                    $reason = 'periodic';
                    if (stripos($fname, 'violation') !== false) {
                        $reason = 'violation';
                    } elseif (stripos($fname, 'exam_start') !== false) {
                        $reason = 'exam_start';
                    } elseif (stripos($fname, 'proctor_request') !== false) {
                        $reason = 'proctor_request';
                    }

                    $items[] = [
                        'filename' => $fname,
                        'url' => base_url($folder['rel_dir'] . $fname) . '?t=' . $mtime,
                        'time' => date('H:i:s', $mtime),
                        'datetime' => date('d/m/Y H:i:s', $mtime),
                        'reason' => $reason,
                        'size_kb' => $sizeKb,
                    ];
                }
            }
        }

        // Fallback kompatibilitas file lama (snap_{jadwal}_{siswa}.jpg) jika pernah ada
        $legacyFile = 'snap_' . intval($id_jadwal) . '_' . intval($id_siswa) . '.jpg';
        $legacyPath = FCPATH . 'uploads/exambro_snapshots/' . $legacyFile;
        if (file_exists($legacyPath)) {
            $mtime = @filemtime($legacyPath) ?: time();
            $items[] = [
                'filename' => $legacyFile,
                'url' => base_url('uploads/exambro_snapshots/' . $legacyFile) . '?t=' . $mtime,
                'time' => date('H:i:s', $mtime),
                'datetime' => date('d/m/Y H:i:s', $mtime),
                'reason' => 'periodic',
                'size_kb' => round((@filesize($legacyPath) ?: 0) / 1024, 1),
            ];
        }

        return $items;
    }

    /**
     * Ringkasan foto untuk tabel Live Monitor (/cbtlive)
     */
    public function getSnapshotInfo($id_jadwal, $id_siswa) {
        $items = $this->getStudentSnapshots($id_jadwal, $id_siswa);
        if (empty($items)) {
            return null;
        }
        $violationPhotos = 0;
        foreach ($items as $it) {
            if ($it['reason'] === 'violation') {
                $violationPhotos++;
            }
        }
        return [
            'exists' => true,
            'count' => count($items),
            'violation_photos' => $violationPhotos,
            'url' => $items[0]['url'],
            'updated_at' => $items[0]['time'],
        ];
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

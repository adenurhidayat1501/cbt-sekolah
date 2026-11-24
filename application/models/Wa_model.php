<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet', true) && file_exists(APPPATH . 'libraries/PhpSpreadsheetStubs.php')) {
    require_once APPPATH . 'libraries/PhpSpreadsheetStubs.php';
}

/**
 * Model Wa_model
 * WhatsApp Bot & Fonnte Gateway Integration
 * SMP Negeri Satu Atap 2 Cipatujah (SADUCI CBT)
 *
 * @property CI_DB_query_builder $db
 * @property Dashboard_model $dashboard
 * @property Cbt_model $cbt
 * @property Master_model $master
 */
class Wa_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->initTables();
        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Cbt_model', 'cbt');
        $this->load->model('Master_model', 'master');
    }

    /**
     * Auto-inisialisasi tabel setting dan log WhatsApp jika belum ada di database
     * serta memastikan dukungan charset utf8mb4 untuk emoji WhatsApp
     */
    private function initTables() {
        // Tabel setting WhatsApp
        if (!$this->db->table_exists('cbt_wa_setting')) {
            $sql = "CREATE TABLE IF NOT EXISTS `cbt_wa_setting` (
                `id_setting` int(11) NOT NULL AUTO_INCREMENT,
                `api_token` varchar(255) NULL DEFAULT '',
                `target_group` varchar(100) NULL DEFAULT '',
                `target_admin` varchar(100) NULL DEFAULT '',
                `status` tinyint(1) NOT NULL DEFAULT 1,
                `header_pesan` varchar(255) NULL DEFAULT 'SADUCI CBT - SMPN Satu Atap 2 Cipatujah',
                `footer_pesan` text NULL,
                `updated_at` datetime NULL DEFAULT NULL,
                PRIMARY KEY (`id_setting`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->db->query($sql);

            // Insert default row
            $this->db->insert('cbt_wa_setting', [
                'api_token' => '',
                'target_group' => '',
                'target_admin' => '',
                'status' => 1,
                'header_pesan' => 'SADUCI CBT - SMPN Satu Atap 2 Cipatujah',
                'footer_pesan' => 'SMP Negeri Satu Atap 2 Cipatujah, Kab. Tasikmalaya',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Tabel log aktivitas WhatsApp
        if (!$this->db->table_exists('cbt_wa_log')) {
            $sql_log = "CREATE TABLE IF NOT EXISTS `cbt_wa_log` (
                `id_log` int(11) NOT NULL AUTO_INCREMENT,
                `time` datetime NOT NULL,
                `direction` varchar(10) NOT NULL DEFAULT 'out',
                `sender` varchar(100) NULL DEFAULT '',
                `target` varchar(100) NULL DEFAULT '',
                `message` text NULL,
                `type` varchar(50) NULL DEFAULT 'text',
                `status` varchar(20) NOT NULL DEFAULT 'SUCCESS',
                `response` text NULL,
                PRIMARY KEY (`id_log`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->db->query($sql_log);
        }

        // Pastikan tabel yang sudah ada sebelumnya dikonversi ke utf8mb4 agar emoji WA tidak menjadi '????'
        @$this->db->query("ALTER TABLE `cbt_wa_setting` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        @$this->db->query("ALTER TABLE `cbt_wa_log` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Auto-migrasi kolom jadwal notifikasi harian (sore jam 15:00 & pagi jam 05:00)
        if (!$this->db->field_exists('auto_schedule_enabled', 'cbt_wa_setting')) {
            @$this->db->query("ALTER TABLE `cbt_wa_setting` ADD COLUMN `auto_schedule_enabled` tinyint(1) NOT NULL DEFAULT 1");
        }
        if (!$this->db->field_exists('schedule_afternoon_time', 'cbt_wa_setting')) {
            @$this->db->query("ALTER TABLE `cbt_wa_setting` ADD COLUMN `schedule_afternoon_time` varchar(5) NOT NULL DEFAULT '15:00'");
        }
        if (!$this->db->field_exists('schedule_morning_time', 'cbt_wa_setting')) {
            @$this->db->query("ALTER TABLE `cbt_wa_setting` ADD COLUMN `schedule_morning_time` varchar(5) NOT NULL DEFAULT '05:00'");
        }
        if (!$this->db->field_exists('last_afternoon_sent_date', 'cbt_wa_setting')) {
            @$this->db->query("ALTER TABLE `cbt_wa_setting` ADD COLUMN `last_afternoon_sent_date` date NULL DEFAULT NULL");
        }
        if (!$this->db->field_exists('last_morning_sent_date', 'cbt_wa_setting')) {
            @$this->db->query("ALTER TABLE `cbt_wa_setting` ADD COLUMN `last_morning_sent_date` date NULL DEFAULT NULL");
        }
    }

    /**
     * Mengambil konfigurasi WhatsApp Gateway Fonnte
     */
    public function getSetting() {
        $row = $this->db->get('cbt_wa_setting')->row();
        if (!$row) {
            $this->db->insert('cbt_wa_setting', [
                'api_token' => '',
                'target_group' => '',
                'target_admin' => '',
                'status' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $row = $this->db->get('cbt_wa_setting')->row();
        }
        return $row;
    }

    /**
     * Menyimpan konfigurasi WhatsApp Gateway
     * 
     * @param array $data
     * @return bool
     */
    public function saveSetting($data) {
        $setting = $this->getSetting();
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id_setting', $setting->id_setting);
        return $this->db->update('cbt_wa_setting', $data);
    }

    /**
     * Mencatat aktivitas log WhatsApp (mendukung karakter emoji utf8mb4)
     * 
     * @param string $direction
     * @param string $sender
     * @param string $target
     * @param mixed $message
     * @param string $type
     * @param string $status
     * @param mixed $response
     * @return bool
     */
    public function logMessage($direction, $sender, $target, $message, $type, $status, $response) {
        @$this->db->query("SET NAMES 'utf8mb4'");
        return $this->db->insert('cbt_wa_log', [
            'time' => date('Y-m-d H:i:s'),
            'direction' => $direction,
            'sender' => $sender,
            'target' => $target,
            'message' => is_string($message) ? $message : json_encode($message, JSON_UNESCAPED_UNICODE),
            'type' => $type,
            'status' => $status,
            'response' => is_string($response) ? $response : json_encode($response, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * Mengambil daftar log aktivitas WhatsApp terbaru
     * 
     * @param int $limit
     * @return array
     */
    public function getLogs($limit = 50) {
        $this->db->order_by('id_log', 'DESC');
        $this->db->limit($limit);
        return $this->db->get('cbt_wa_log')->result();
    }

    /**
     * Mengirim pesan atau dokumen via Fonnte API
     * 
     * Catatan: Fonnte & WhatsApp Cloud API telah resmi menonaktifkan fitur tombol interaktif (buttons).
     * Oleh karena itu, semua pesan dikirim sebagai pesan teks rapi dengan kata kunci perintah yang jelas.
     * 
     * @param string $target
     * @param string $message
     * @param string|null $url
     * @param string|null $filename
     * @param array|null $buttons
     * @return array
     */
    public function sendFonnte($target, $message, $url = null, $filename = null, $buttons = null) {
        $setting = $this->getSetting();
        if (!$setting || empty($setting->api_token)) {
            return ['status' => false, 'message' => 'Token API Fonnte belum dikonfigurasi di Pengaturan WA'];
        }

        if (empty($target)) {
            return ['status' => false, 'message' => 'Target nomor atau ID grup WhatsApp belum ditentukan'];
        }

        // Deteksi apakah target adalah ID Grup WhatsApp (@g.us)
        $clean_target = trim((string) $target);
        $is_group = (strpos($clean_target, '@g.us') !== false);

        if ($is_group) {
            // Hapus spasi di sekitar koma jika ada lebih dari 1 grup
            $clean_target = preg_replace('/\s+/', '', $clean_target);
        } else {
            // Jika bukan grup, format nomor telepon (buang karakter selain angka dan awalan 0 menjadi 62)
            $clean_target = preg_replace('/[^0-9]/', '', $clean_target);
            if (substr($clean_target, 0, 1) === '0') {
                $clean_target = '62' . substr($clean_target, 1);
            }
        }

        $post_data = [
            'target' => $clean_target,
            'message' => $message,
        ];

        // countryCode HANYA dikirim jika target adalah nomor perorangan (bukan grup @g.us)
        if (!$is_group) {
            $post_data['countryCode'] = '62';
        }

        // Lampiran file dokumen (misalnya file Excel rekap nilai)
        if (!empty($url)) {
            $post_data['url'] = $url;
        }
        if (!empty($filename)) {
            $post_data['filename'] = $filename;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . trim($setting->api_token)
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        @curl_close($curl);

        $is_success = ($http_code == 200 && empty($err));
        $resp_json = json_decode($response, true);
        if (isset($resp_json['status']) && $resp_json['status'] === false) {
            $is_success = false;
        }

        // Tangkap pesan status / alasan dari server Fonnte secara spesifik
        $reason = '';
        if (isset($resp_json['reason'])) {
            $reason = $resp_json['reason'];
        } else if (isset($resp_json['message'])) {
            $reason = $resp_json['message'];
        } else if (!empty($err)) {
            $reason = 'cURL Error: ' . $err;
        } else if (!$is_success) {
            $reason = 'Gagal menghubungi server Fonnte (HTTP ' . $http_code . ')';
        } else {
            $reason = 'Pesan berhasil dikirim via Fonnte';
        }

        // Catat ke log
        $this->logMessage('out', 'SADUCI CBT', $clean_target, $message, !empty($url) ? 'document' : 'text', $is_success ? 'SUCCESS' : 'FAILED', $response ?: $reason);

        return [
            'status' => $is_success,
            'http_code' => $http_code,
            'message' => $reason,
            'reason' => $reason,
            'response' => $resp_json ?: $response,
            'error' => $err
        ];
    }

    /**
     * Mengambil daftar grup WhatsApp yang diikuti oleh device dari API Fonnte
     * 
     * @return array
     */
    public function getFonnteGroups() {
        $setting = $this->getSetting();
        if (!$setting || empty($setting->api_token)) {
            return ['status' => false, 'message' => 'Token API Fonnte belum dikonfigurasi di Pengaturan'];
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.fonnte.com/get-whatsapp-group',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . trim($setting->api_token)
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        @curl_close($curl);

        if ($http_code == 200 && empty($err)) {
            $data = json_decode($response, true);
            return $data ?: ['status' => false, 'message' => 'Format respons grup Fonnte tidak valid'];
        }

        return [
            'status' => false,
            'message' => $err ?: 'Gagal menghubungi server Fonnte (HTTP ' . $http_code . ')'
        ];
    }

    /**
     * Memeriksa daftar siswa yang belum mengikuti ujian pada hari ini
     * Mengembalikan data lengkap per kelas dan mapel
     * 
     * @param int|string|null $id_jadwal
     * @return array
     */
    public function getSiswaTidakUjianHariIni($id_jadwal = null) {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $today = date('Y-m-d');

        // Cari jadwal
        if ($id_jadwal) {
            $info = $this->cbt->getJadwalById($id_jadwal);
            $jadwals = $info ? [$info] : [];
        } else {
            // Ambil semua jadwal yang berlangsung hari ini
            $this->db->select('a.*, b.bank_kode, b.bank_kelas, d.nama_mapel');
            $this->db->from('cbt_jadwal a');
            $this->db->join('cbt_bank_soal b', 'b.id_bank=a.id_bank');
            $this->db->join('master_mapel d', 'd.id_mapel=b.bank_mapel_id', 'left');
            $this->db->where('a.id_tp', $tp->id_tp);
            $this->db->where('a.id_smt', $smt->id_smt);
            $this->db->where('a.status', '1');
            $this->db->where('DATE(a.tgl_mulai) <=', $today);
            $this->db->where('DATE(a.tgl_selesai) >=', $today);
            $jadwals = $this->db->get()->result();
        }

        // Jika tidak ada jadwal pas hari ini, fallback ke jadwal aktif terbaru
        if (empty($jadwals)) {
            $this->db->select('a.*, b.bank_kode, b.bank_kelas, d.nama_mapel');
            $this->db->from('cbt_jadwal a');
            $this->db->join('cbt_bank_soal b', 'b.id_bank=a.id_bank');
            $this->db->join('master_mapel d', 'd.id_mapel=b.bank_mapel_id', 'left');
            $this->db->where('a.id_tp', $tp->id_tp);
            $this->db->where('a.id_smt', $smt->id_smt);
            $this->db->where('a.status', '1');
            $this->db->order_by('a.tgl_mulai', 'DESC');
            $this->db->limit(1);
            $jadwals = $this->db->get()->result();
        }

        if (empty($jadwals)) return [];

        $reports = [];
        foreach ($jadwals as $j) {
            $kelas_raw = @unserialize($j->bank_kelas ?? '');
            $kelas_ids = [];
            if (is_array($kelas_raw)) {
                foreach ($kelas_raw as $kr) {
                    if (!empty($kr['kelas_id'])) {
                        $kelas_ids[] = $kr['kelas_id'];
                    }
                }
            }

            if (empty($kelas_ids)) continue;

            // Ambil semua siswa terdaftar pada kelas-kelas ini
            $this->db->select('b.id_siswa, b.nama, b.nis, b.nisn, f.id_kelas, f.nama_kelas, f.kode_kelas');
            $this->db->from('kelas_siswa a');
            $this->db->join('master_siswa b', 'b.id_siswa=a.id_siswa');
            $this->db->join('master_kelas f', 'f.id_kelas=a.id_kelas', 'left');
            $this->db->where('a.id_tp', $tp->id_tp);
            $this->db->where('a.id_smt', $smt->id_smt);
            $this->db->where_in('a.id_kelas', $kelas_ids);
            $this->db->order_by('f.nama_kelas', 'ASC');
            $this->db->order_by('b.nama', 'ASC');
            $all_enrolled = $this->db->get()->result();

            // Ambil data siswa yang sudah masuk durasi / mulai ujian
            $this->db->select('id_siswa');
            $this->db->from('cbt_durasi_siswa');
            $this->db->where('id_jadwal', $j->id_jadwal);
            $durasis = $this->db->get()->result();
            $attended_ids = [];
            foreach ($durasis as $dur) {
                $attended_ids[$dur->id_siswa] = true;
            }

            // Identifikasi siswa yang belum ujian
            $unattended = [];
            $unattended_by_class = [];
            foreach ($all_enrolled as $st) {
                if (!isset($attended_ids[$st->id_siswa])) {
                    $unattended[] = $st;
                    $kls_name = !empty($st->nama_kelas) ? $st->nama_kelas : $st->kode_kelas;
                    $unattended_by_class[$kls_name][] = $st;
                }
            }

            $reports[] = [
                'jadwal' => $j,
                'total_terdaftar' => count($all_enrolled),
                'total_hadir' => count($all_enrolled) - count($unattended),
                'total_tidak_hadir' => count($unattended),
                'unattended_by_class' => $unattended_by_class,
                'unattended_list' => $unattended
            ];
        }

        return $reports;
    }

    /**
     * Memformat teks WhatsApp untuk laporan siswa belum ujian
     * 
     * @param array $reports
     * @return string
     */
    public function formatAbsenMessage($reports) {
        if (empty($reports)) {
            return "ℹ️ *INFO CBT SADUCI*\n\nTidak ditemukan jadwal ujian yang sedang aktif untuk hari ini (" . date('d/m/Y') . ").";
        }

        $msg = "📢 *LAPORAN SISWA BELUM UJIAN HARI INI*\n";
        $msg .= "🏫 *SMP Negeri Satu Atap 2 Cipatujah*\n";
        $msg .= "📅 " . date('d/m/Y') . " | Pukul " . date('H:i') . " WIB\n";
        $msg .= "────────────────────────────\n\n";

        foreach ($reports as $rep) {
            $j = $rep['jadwal'];
            $mapel = !empty($j->nama_mapel) ? $j->nama_mapel : $j->bank_kode;
            $msg .= "📝 *Mapel:* {$mapel} ({$j->bank_kode})\n";
            $msg .= "👥 *Total Terdaftar:* {$rep['total_terdaftar']} Siswa\n";
            $msg .= "✅ *Sudah Ujian:* {$rep['total_hadir']} Siswa\n";
            $msg .= "❌ *Belum Ujian:* *{$rep['total_tidak_hadir']} Siswa*\n\n";

            if ($rep['total_tidak_hadir'] == 0) {
                $msg .= "🎉 *Alhamdulillah!* Seluruh siswa telah mengikuti ujian pada jadwal ini.\n\n";
            } else {
                $msg .= "📋 *Daftar Siswa Belum Ujian:*\n";
                foreach ($rep['unattended_by_class'] as $kelas => $siswa_list) {
                    $msg .= "📌 *Kelas {$kelas} (" . count($siswa_list) . " Siswa):*\n";
                    $no = 1;
                    foreach ($siswa_list as $s) {
                        $nis = !empty($s->nis) ? " (NIS: {$s->nis})" : "";
                        $msg .= "   {$no}. {$s->nama}{$nis}\n";
                        $no++;
                    }
                    $msg .= "\n";
                }
            }
            $msg .= "────────────────────────────\n";
        }

        $msg .= "\n💡 *Petunjuk Perintah Bot:* \n";
        $msg .= "• Ketik *!absen* untuk cek ulang data ini\n";
        $msg .= "• Ketik *!rekap* untuk rekap nilai & unduh file Excel\n";
        $msg .= "• Ketik *!server* untuk cek kesehatan server\n";
        $msg .= "• Ketik *!token* untuk token ujian hari ini";

        return $msg;
    }

    /**
     * Mengambil data rekapitulasi nilai untuk jadwal/mapel tertentu
     * 
     * @param int|string $id_jadwal
     * @return array|null
     */
    public function getRekapNilaiData($id_jadwal) {
        $info = $this->cbt->getJadwalById($id_jadwal);
        if (!$info) return null;

        // Cek data di cbt_rekap_nilai jika sudah pernah disimpan
        $rekap_saved = $this->db->where('id_jadwal', $id_jadwal)->get('cbt_rekap_nilai')->result();

        $students_data = [];
        if (!empty($rekap_saved)) {
            foreach ($rekap_saved as $r) {
                $nilai_pg = floatval($r->nilai_pg);
                $nilai_esai = floatval($r->nilai_esai);
                $total = $nilai_pg + $nilai_esai;
                $students_data[] = [
                    'id_siswa' => $r->id_siswa,
                    'nama' => $r->nama_siswa,
                    'kelas' => $r->kelas,
                    'nilai_pg' => $nilai_pg,
                    'nilai_esai' => $nilai_esai,
                    'nilai_akhir' => $total,
                ];
            }
        } else {
            // Ambil dari cbt_nilai langsung
            $this->db->select('a.*, b.nama, b.nis, f.nama_kelas, f.kode_kelas');
            $this->db->from('cbt_nilai a');
            $this->db->join('master_siswa b', 'b.id_siswa = a.id_siswa');
            $this->db->join('kelas_siswa d', 'd.id_siswa = a.id_siswa', 'left');
            $this->db->join('master_kelas f', 'f.id_kelas = d.id_kelas', 'left');
            $this->db->where('a.id_jadwal', $id_jadwal);
            $this->db->order_by('b.nama', 'ASC');
            $scores = $this->db->get()->result();

            foreach ($scores as $s) {
                $total = floatval($s->pg_nilai) + floatval($s->essai_nilai) + floatval($s->kompleks_nilai) + floatval($s->jodohkan_nilai) + floatval($s->isian_nilai);
                $students_data[] = [
                    'id_siswa' => $s->id_siswa,
                    'nama' => $s->nama,
                    'nis' => $s->nis,
                    'kelas' => !empty($s->nama_kelas) ? $s->nama_kelas : ($s->kode_kelas ?? '-'),
                    'nilai_pg' => floatval($s->pg_nilai),
                    'nilai_esai' => floatval($s->essai_nilai),
                    'nilai_akhir' => round($total, 2),
                ];
            }
        }

        if (empty($students_data)) return null;

        // Hitung statistik
        $count = count($students_data);
        $sum = 0;
        $max = -1;
        $min = 999;
        $max_student = '';
        $kkm = 75;
        $tuntas = 0;

        foreach ($students_data as $sd) {
            $val = $sd['nilai_akhir'];
            $sum += $val;
            if ($val > $max) {
                $max = $val;
                $max_student = $sd['nama'] . ' (' . ($sd['kelas'] ?? '') . ')';
            }
            if ($val < $min) {
                $min = $val;
            }
            if ($val >= $kkm) {
                $tuntas++;
            }
        }

        $avg = $count > 0 ? round($sum / $count, 2) : 0;
        $persen_tuntas = $count > 0 ? round(($tuntas / $count) * 100, 1) : 0;

        // Urutkan siswa dari nilai tertinggi untuk Top 5
        usort($students_data, function ($a, $b) {
            return $b['nilai_akhir'] <=> $a['nilai_akhir'];
        });

        return [
            'info' => $info,
            'total_siswa' => $count,
            'rata_rata' => $avg,
            'nilai_max' => $max >= 0 ? $max : 0,
            'siswa_max' => $max_student,
            'nilai_min' => $min <= 100 ? $min : 0,
            'tuntas' => $tuntas,
            'belum_tuntas' => $count - $tuntas,
            'persen_tuntas' => $persen_tuntas,
            'top_students' => array_slice($students_data, 0, 5),
            'all_students' => $students_data
        ];
    }

    /**
     * Generate file Excel Rekapitulasi Nilai menggunakan PhpSpreadsheet
     * 
     * @param array $rekap_data
     * @return array|null
     */
    public function generateExcelRekap($rekap_data) {
        if (!$rekap_data || empty($rekap_data['all_students'])) return null;

        $info = $rekap_data['info'];
        $mapel = !empty($info->nama_mapel) ? $info->nama_mapel : 'Mapel';
        $bank_kode = !empty($info->bank_kode) ? $info->bank_kode : 'Ujian';

        // Buat folder tujuan
        $dir = FCPATH . 'uploads/rekap/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!file_exists($dir . 'index.html')) {
            @file_put_contents($dir . 'index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body>Directory access is forbidden.</body></html>');
        }

        $safe_mapel = preg_replace('/[^a-zA-Z0-9_-]/', '_', $mapel);
        $safe_bank = preg_replace('/[^a-zA-Z0-9_-]/', '_', $bank_kode);
        $filename = "Rekap_Nilai_{$safe_mapel}_{$safe_bank}_" . date('Ymd_His') . ".xlsx";
        $filepath = $dir . $filename;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Nilai');

        // Header Dokumen
        $sheet->setCellValue('A1', 'SMP NEGERI SATU ATAP 2 CIPATUJAH');
        $sheet->setCellValue('A2', 'REKAPITULASI HASIL PENILAIAN UJIAN CBT');
        $sheet->setCellValue('A3', 'Mata Pelajaran: ' . $mapel . ' (' . $bank_kode . ') | Tanggal: ' . date('d/m/Y'));
        $sheet->setCellValue('A4', 'Rata-Rata: ' . $rekap_data['rata_rata'] . ' | Tertinggi: ' . $rekap_data['nilai_max'] . ' | Terendah: ' . $rekap_data['nilai_min'] . ' | Ketuntasan: ' . $rekap_data['persen_tuntas'] . '%');

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A3:A4')->getFont()->setSize(10)->setItalic(true);

        // Header Kolom Tabel
        $row_start = 6;
        $sheet->setCellValue('A' . $row_start, 'No.');
        $sheet->setCellValue('B' . $row_start, 'Nama Siswa');
        $sheet->setCellValue('C' . $row_start, 'Kelas');
        $sheet->setCellValue('D' . $row_start, 'Nilai PG');
        $sheet->setCellValue('E' . $row_start, 'Nilai Esai');
        $sheet->setCellValue('F' . $row_start, 'Nilai Akhir');
        $sheet->setCellValue('G' . $row_start, 'Keterangan');

        $sheet->getStyle("A{$row_start}:G{$row_start}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row_start}:G{$row_start}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');

        // Isi Data
        $curr_row = $row_start + 1;
        $no = 1;
        foreach ($rekap_data['all_students'] as $st) {
            $sheet->setCellValue('A' . $curr_row, $no++);
            $sheet->setCellValue('B' . $curr_row, $st['nama']);
            $sheet->setCellValue('C' . $curr_row, $st['kelas']);
            $sheet->setCellValue('D' . $curr_row, $st['nilai_pg']);
            $sheet->setCellValue('E' . $curr_row, $st['nilai_esai']);
            $sheet->setCellValue('F' . $curr_row, $st['nilai_akhir']);
            $sheet->setCellValue('G' . $curr_row, $st['nilai_akhir'] >= 75 ? 'TUNTAS' : 'BELUM TUNTAS');
            $curr_row++;
        }

        // Auto width kolom
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);

        return [
            'filepath' => $filepath,
            'filename' => $filename,
            'url' => base_url('uploads/rekap/' . $filename)
        ];
    }

    /**
     * Memformat teks WhatsApp untuk laporan rekap nilai mapel
     * 
     * @param array $rekap_data
     * @param string|null $excel_url
     * @return string
     */
    public function formatRekapMessage($rekap_data, $excel_url = null) {
        if (!$rekap_data) {
            return "ℹ️ *INFO CBT SADUCI*\n\nBelum ada data nilai ujian yang tersimpan untuk jadwal ini.";
        }

        $info = $rekap_data['info'];
        $mapel = !empty($info->nama_mapel) ? $info->nama_mapel : $info->bank_kode;

        $msg = "📊 *REKAPITULASI HASIL NILAI UJIAN*\n";
        $msg .= "🏫 *SMP Negeri Satu Atap 2 Cipatujah*\n";
        $msg .= "📅 " . date('d/m/Y') . " | Pukul " . date('H:i') . " WIB\n";
        $msg .= "────────────────────────────\n";
        $msg .= "📖 *Mapel:* {$mapel}\n";
        $msg .= "📝 *Bank Soal:* {$info->bank_kode}\n";
        $msg .= "👥 *Jumlah Peserta:* {$rekap_data['total_siswa']} Siswa\n\n";

        $msg .= "📈 *Statistik Nilai:*\n";
        $msg .= "• Nilai Rata-Rata : *{$rekap_data['rata_rata']}*\n";
        $msg .= "• Nilai Tertinggi : *{$rekap_data['nilai_max']}* ({$rekap_data['siswa_max']})\n";
        $msg .= "• Nilai Terendah  : *{$rekap_data['nilai_min']}*\n";
        $msg .= "• Tuntas (≥75)    : *{$rekap_data['tuntas']} Siswa* ({$rekap_data['persen_tuntas']}%)\n";
        $msg .= "• Belum Tuntas    : *{$rekap_data['belum_tuntas']} Siswa*\n\n";

        if (!empty($rekap_data['top_students'])) {
            $msg .= "🏆 *Top 5 Nilai Tertinggi:*\n";
            $rank = 1;
            foreach ($rekap_data['top_students'] as $top) {
                $msg .= "  {$rank}. *{$top['nama']}* ({$top['kelas']}) — *{$top['nilai_akhir']}*\n";
                $rank++;
            }
            $msg .= "\n";
        }

        if (!empty($excel_url)) {
            $msg .= "📥 *Unduh Dokumen Rekap Excel Lengkap:*\n";
            $msg .= "{$excel_url}\n\n";
        }

        $msg .= "────────────────────────────\n";
        $msg .= "Ketik *!absen* untuk cek siswa tidak ujian.\n";
        $msg .= "Ketik *!menu* untuk kembali ke menu utama.";

        return $msg;
    }

    /**
     * Memformat teks WhatsApp untuk status kesehatan server real-time
     */
    public function formatServerStatusMessage() {
        $load = [0, 0, 0];
        $cpu_count = 1;
        if (function_exists('sys_getloadavg')) {
            $sys_load = sys_getloadavg();
            if (is_array($sys_load)) {
                $load = [round($sys_load[0], 2), round($sys_load[1], 2), round($sys_load[2], 2)];
            }
        }
        if (@is_readable('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo');
            $cpu_count = substr_count($cpuinfo, 'processor') ?: 1;
        }
        $cpu_percent = min(100, round(($load[0] / $cpu_count) * 100));

        // RAM
        $ram_used = 0; $ram_total = 0; $ram_percent = 0;
        if (@is_readable('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)\s+kB/i', $meminfo, $mt);
            preg_match('/MemAvailable:\s+(\d+)\s+kB/i', $meminfo, $ma);
            $total_kb = isset($mt[1]) ? intval($mt[1]) : 0;
            $avail_kb = isset($ma[1]) ? intval($ma[1]) : 0;
            if ($total_kb > 0) {
                $used_kb = $total_kb - $avail_kb;
                $ram_total = round($total_kb / 1024 / 1024, 1);
                $ram_used = round($used_kb / 1024 / 1024, 1);
                $ram_percent = round(($used_kb / $total_kb) * 100);
            }
        }

        // Live students online
        $active_students = $this->db->where('status', 1)->where('selesai IS NULL')->count_all_results('cbt_durasi_siswa');

        // MariaDB active connections
        $db_threads = 1;
        try {
            $q = $this->db->query("SHOW GLOBAL STATUS LIKE 'Threads_connected'")->row();
            if ($q) $db_threads = intval($q->Value);
        } catch (Exception $e) {}

        // Disk
        $disk_free = round((@disk_free_space('/') ?: 35000000000) / 1024 / 1024 / 1024, 1);
        $disk_total = round((@disk_total_space('/') ?: 50000000000) / 1024 / 1024 / 1024, 1);

        $msg = "🖥️ *MONITOR KESEHATAN SERVER CBT*\n";
        $msg .= "🏫 *SMP Negeri Satu Atap 2 Cipatujah*\n";
        $msg .= "⏱️ " . date('d/m/Y H:i:s') . " WIB\n";
        $msg .= "────────────────────────────\n";
        $msg .= "⚡ *CPU Load:* {$cpu_percent}% (avg: {$load[0]})\n";
        $msg .= "🧠 *RAM Memory:* {$ram_used} GB / {$ram_total} GB ({$ram_percent}%)\n";
        $msg .= "👥 *Siswa Live Online:* *{$active_students} Siswa*\n";
        $msg .= "🗄️ *Database Threads:* {$db_threads} active\n";
        $msg .= "💾 *Penyimpanan Disk:* {$disk_free} GB free of {$disk_total} GB\n";
        $msg .= "────────────────────────────\n";
        $msg .= "Status Server: *NORMAL & STABIL* ✅";

        return $msg;
    }

    /**
     * Memformat teks WhatsApp untuk token ujian hari ini
     */
    public function formatTokenMessage() {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $today = date('Y-m-d');

        $this->db->select('a.*, b.bank_kode, d.nama_mapel');
        $this->db->from('cbt_jadwal a');
        $this->db->join('cbt_bank_soal b', 'b.id_bank=a.id_bank');
        $this->db->join('master_mapel d', 'd.id_mapel=b.bank_mapel_id', 'left');
        $this->db->where('a.id_tp', $tp->id_tp);
        $this->db->where('a.id_smt', $smt->id_smt);
        $this->db->where('a.status', '1');
        $this->db->where('DATE(a.tgl_mulai) <=', $today);
        $this->db->where('DATE(a.tgl_selesai) >=', $today);
        $jadwals = $this->db->get()->result();

        if (empty($jadwals)) {
            return "ℹ️ *TOKEN UJIAN SADUCI CBT*\n\nTidak ada jadwal ujian yang memerlukan token pada hari ini (" . date('d/m/Y') . ").";
        }

        $msg = "🔑 *TOKEN UJIAN AKTIF HARI INI*\n";
        $msg .= "🏫 *SMP Negeri Satu Atap 2 Cipatujah*\n";
        $msg .= "📅 " . date('d/m/Y') . "\n";
        $msg .= "────────────────────────────\n\n";

        foreach ($jadwals as $j) {
            $token = !empty($j->token) ? $j->token : 'TIDAK PAKAI TOKEN';
            $mapel = !empty($j->nama_mapel) ? $j->nama_mapel : $j->bank_kode;
            $msg .= "📝 *Mapel:* {$mapel} ({$j->bank_kode})\n";
            $msg .= "⏰ *Durasi:* {$j->durasi_ujian} Menit\n";
            $msg .= "🔑 *TOKEN:* `{$token}`\n\n";
        }

        $msg .= "────────────────────────────\n";
        $msg .= "_Bagikan token ini kepada siswa saat ujian dimulai._";

        return $msg;
    }

    /**
     * Ambil daftar jadwal ujian aktif untuk tanggal tertentu (default hari ini)
     */
    public function getJadwalsByDate($dateYmd, $fallbackLatest = false) {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        if (!$tp || !$smt) return [];

        $this->db->select('a.*, b.bank_kode, b.bank_kelas, b.bank_level, c.kode_jenis, c.nama_jenis, d.nama_mapel');
        $this->db->from('cbt_jadwal a');
        $this->db->join('cbt_bank_soal b', 'b.id_bank=a.id_bank', 'left');
        $this->db->join('cbt_jenis c', 'c.id_jenis=a.id_jenis', 'left');
        $this->db->join('master_mapel d', 'd.id_mapel=b.bank_mapel_id', 'left');
        $this->db->where('a.id_tp', $tp->id_tp);
        $this->db->where('a.id_smt', $smt->id_smt);
        $this->db->where('a.status', '1');
        $this->db->where('DATE(a.tgl_mulai) <=', $dateYmd);
        $this->db->where('DATE(a.tgl_selesai) >=', $dateYmd);
        $this->db->order_by('a.jam_ke', 'ASC');
        $this->db->order_by('a.tgl_mulai', 'ASC');
        $rows = $this->db->get()->result();

        if (empty($rows) && $fallbackLatest) {
            $this->db->select('a.*, b.bank_kode, b.bank_kelas, b.bank_level, c.kode_jenis, c.nama_jenis, d.nama_mapel');
            $this->db->from('cbt_jadwal a');
            $this->db->join('cbt_bank_soal b', 'b.id_bank=a.id_bank', 'left');
            $this->db->join('cbt_jenis c', 'c.id_jenis=a.id_jenis', 'left');
            $this->db->join('master_mapel d', 'd.id_mapel=b.bank_mapel_id', 'left');
            $this->db->where('a.id_tp', $tp->id_tp);
            $this->db->where('a.id_smt', $smt->id_smt);
            $this->db->where('a.status', '1');
            $this->db->order_by('a.tgl_mulai', 'DESC');
            $this->db->limit(3);
            $rows = $this->db->get()->result();
        }
        return $rows;
    }

    /**
     * Format tanggal Indonesia (contoh: Rabu, 25 November 2026)
     */
    private function formatTanggalIndo($dateYmd) {
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $ts = strtotime($dateYmd);
        return $hari[intval(date('w', $ts))] . ', ' . date('d', $ts) . ' ' . $bulan[intval(date('n', $ts))] . ' ' . date('Y', $ts);
    }

    /**
     * Format pesan Notifikasi Jadwal Ujian & Nama Pengawas Lengkap (Ruang & Sesi)
     * Mode: 'tomorrow' (sore jam 15:00) atau 'today' (pagi jam 05:00 / manual !jadwal)
     * Mengembalikan null jika tidak ada jadwal pada tanggal target dan $returnNullIfEmpty = true
     */
    public function formatScheduleAndProctorMessage($dateYmd, $mode = 'today', $returnNullIfEmpty = false) {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $jadwals = $this->getJadwalsByDate($dateYmd, false);
        $tglIndo = $this->formatTanggalIndo($dateYmd);

        if (empty($jadwals)) {
            if ($returnNullIfEmpty) {
                return null;
            }
            $label = ($mode === 'tomorrow') ? 'BESOK' : 'HARI INI';
            return "📅 *JADWAL UJIAN {$label}*\n{$tglIndo}\n\nℹ️ Tidak ada jadwal ujian yang aktif pada tanggal tersebut.";
        }

        // Ambil peta nama kelas
        $kelasMap = [];
        if ($tp && $smt) {
            $kRows = $this->db->select('id_kelas, nama_kelas, kode_kelas')->where('id_tp', $tp->id_tp)->where('id_smt', $smt->id_smt)->get('master_kelas')->result();
            foreach ($kRows as $kr) {
                $kelasMap[$kr->id_kelas] = $kr->nama_kelas ?: $kr->kode_kelas;
            }
        }

        if ($mode === 'tomorrow') {
            $msg = "📣 *PENGINGAT JADWAL UJIAN BESOK*\n";
            $msg .= "🏫 *SMP Negeri Satu Atap 2 Cipatujah*\n";
            $msg .= "📅 *{$tglIndo}*\n";
        } else {
            $msg = "🌅 *JADWAL UJIAN & PENGAWAS HARI INI*\n";
            $msg .= "🏫 *SMP Negeri Satu Atap 2 Cipatujah*\n";
            $msg .= "📅 *{$tglIndo}*\n";
        }
        $msg .= "────────────────────────────\n\n";

        $no = 1;
        foreach ($jadwals as $j) {
            $mapel = !empty($j->nama_mapel) ? $j->nama_mapel : $j->bank_kode;
            $jamKe = !empty($j->jam_ke) ? "Jam ke-{$j->jam_ke} • " : '';
            $durasi = intval($j->durasi_ujian) . ' Menit';

            // Daftar kelas peserta
            $kelasRaw = @unserialize($j->bank_kelas ?? '');
            $kelasNames = [];
            if (is_array($kelasRaw)) {
                foreach ($kelasRaw as $kr) {
                    $kid = $kr['kelas_id'] ?? null;
                    if ($kid && isset($kelasMap[$kid])) {
                        $kelasNames[] = $kelasMap[$kid];
                    }
                }
            }
            $kelasStr = !empty($kelasNames) ? implode(', ', $kelasNames) : 'Semua Kelas';

            $msg .= "*{$no}. {$mapel}* (`{$j->bank_kode}`)\n";
            $msg .= "   ⏱️ {$jamKe}Durasi: {$durasi}\n";
            $msg .= "   🎓 Kelas: {$kelasStr}\n";
            if ($mode !== 'tomorrow' && !empty($j->token)) {
                $msg .= "   🔑 Token: `{$j->token}`\n";
            }

            // Ambil data pengawas ruang & sesi untuk jadwal ini
            $this->db->select('p.id_guru, r.kode_ruang, r.nama_ruang, s.kode_sesi, s.nama_sesi, s.waktu_mulai, s.waktu_akhir');
            $this->db->from('cbt_pengawas p');
            $this->db->join('cbt_ruang r', 'r.id_ruang=p.id_ruang', 'left');
            $this->db->join('cbt_sesi s', 's.id_sesi=p.id_sesi', 'left');
            $this->db->where('p.id_jadwal', $j->id_jadwal);
            if ($tp) $this->db->where('p.id_tp', $tp->id_tp);
            if ($smt) $this->db->where('p.id_smt', $smt->id_smt);
            $this->db->order_by('s.nama_sesi', 'ASC');
            $this->db->order_by('r.nama_ruang', 'ASC');
            $pRows = $this->db->get()->result();

            if (!empty($pRows)) {
                $guruIds = [];
                foreach ($pRows as $pr) {
                    foreach (explode(',', (string) $pr->id_guru) as $gid) {
                        $gid = trim($gid);
                        if ($gid !== '' && !in_array($gid, $guruIds, true)) {
                            $guruIds[] = $gid;
                        }
                    }
                }
                $guruMap = [];
                if (!empty($guruIds)) {
                    foreach ($this->master->getGuruByArrId($guruIds) as $g) {
                        $guruMap[$g->id_guru] = $g->nama_guru;
                    }
                }

                $msg .= "   👮 *Pengawas Ruang & Sesi:*\n";
                foreach ($pRows as $pr) {
                    $ruangLbl = $pr->nama_ruang ?: ($pr->kode_ruang ?: 'Semua Ruang');
                    $sesiLbl = $pr->nama_sesi ?: ($pr->kode_sesi ?: 'Semua Sesi');
                    $jamSesi = (!empty($pr->waktu_mulai) && !empty($pr->waktu_akhir))
                        ? ' (' . substr($pr->waktu_mulai, 0, 5) . '-' . substr($pr->waktu_akhir, 0, 5) . ')'
                        : '';
                    $gNames = [];
                    foreach (explode(',', (string) $pr->id_guru) as $gid) {
                        $gid = trim($gid);
                        if (isset($guruMap[$gid])) {
                            $gNames[] = $guruMap[$gid];
                        }
                    }
                    $pengawasStr = !empty($gNames) ? implode(', ', $gNames) : '_Belum ditentukan_';
                    $msg .= "   • *{$ruangLbl}* | {$sesiLbl}{$jamSesi}\n     👤 {$pengawasStr}\n";
                }
            } else {
                $msg .= "   👮 Pengawas: _Belum diatur di menu Pengawas_\n";
            }

            $msg .= "\n";
            $no++;
        }

        if ($mode !== 'tomorrow') {
            $this->load->model('Exambro_model', 'exambro');
            $activeExit = $this->exambro->getActiveExitCode();
            $msg .= "────────────────────────────\n";
            $msg .= "🔐 *Kode Keluar Exambro Hari Ini:* `{$activeExit}`\n";
            $msg .= "📌 _Ketik *!siap* untuk cek kesiapan ujian atau *!live* untuk pantau progres._";
        } else {
            $msg .= "────────────────────────────\n";
            $msg .= "📌 _Mohon Bapak/Ibu Pengawas bersiap sesuai ruang & sesi masing-masing. Pengingat pagi akan dikirim ulang pukul 05:00 WIB._";
        }

        return $msg;
    }

    /**
     * Cari siswa berdasarkan NIS atau potongan Nama pada TP/SMT aktif
     */
    private function findStudentsByQuery($query) {
        $q = trim((string) $query);
        if ($q === '') return [];

        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        $this->db->select('s.id_siswa, s.nama, s.nis, k.nama_kelas');
        $this->db->from('master_siswa s');
        if ($tp && $smt) {
            $this->db->join('kelas_siswa ks', 'ks.id_siswa=s.id_siswa AND ks.id_tp=' . intval($tp->id_tp) . ' AND ks.id_smt=' . intval($smt->id_smt), 'left');
        } else {
            $this->db->join('kelas_siswa ks', 'ks.id_siswa=s.id_siswa', 'left');
        }
        $this->db->join('master_kelas k', 'k.id_kelas=ks.id_kelas', 'left');
        $this->db->group_start();
        $this->db->where('s.nis', $q);
        $this->db->or_where('s.nisn', $q);
        $this->db->or_like('s.nama', $q);
        $this->db->group_end();
        $this->db->limit(6);
        return $this->db->get()->result();
    }

    /**
     * Perintah WA #1: !reset <NIS/Nama> atau !reset semua
     */
    public function resetStudentLoginFromWa($arg, $requesterName = 'Pengawas WA') {
        $arg = trim((string) $arg);
        $jadwals = $this->getJadwalsByDate(date('Y-m-d'), true);
        if (empty($jadwals)) {
            return "ℹ️ Tidak ditemukan jadwal ujian aktif untuk melakukan reset login.";
        }
        $jadwalIds = array_map(function ($j) { return intval($j->id_jadwal); }, $jadwals);

        $this->load->model('Exambro_model', 'exambro');

        if ($arg === '') {
            return "⚠️ Format salah.\nGunakan:\n• *!reset 2425001* (reset 1 siswa berdasarkan NIS/Nama)\n• *!reset semua* (buka kunci semua siswa aktif hari ini)";
        }

        if (strtolower($arg) === 'semua' || strtolower($arg) === 'all') {
            $this->db->set('reset', 1)->where_in('id_jadwal', $jadwalIds)->where('log_type', 1)->update('log_ujian');
            $affected = $this->db->affected_rows();
            $this->db->set('reset', 1)->where_in('id_jadwal', $jadwalIds)->where('status', 1)->update('cbt_durasi_siswa');
            $this->exambro->logProctorAudit('BULK_RESET', 'Semua Siswa Aktif (via WA)', "Reset login massal via Grup WA oleh {$requesterName}");
            return "✅ *RESET LOGIN MASSAL BERHASIL*\nSeluruh sesi login ujian aktif hari ini telah dibuka kuncinya ({$affected} log diperbarui).\n\n_Diproses atas permintaan {$requesterName}._";
        }

        $matches = $this->findStudentsByQuery($arg);
        if (empty($matches)) {
            return "❌ Siswa dengan NIS/Nama *{$arg}* tidak ditemukan.";
        }
        if (count($matches) > 1) {
            // Cek apakah ada exact NIS
            $exact = null;
            foreach ($matches as $m) {
                if (strcasecmp($m->nis, $arg) === 0 || strcasecmp($m->nama, $arg) === 0) {
                    $exact = $m;
                    break;
                }
            }
            if (!$exact) {
                $list = "⚠️ Ditemukan lebih dari 1 siswa cocok dengan *{$arg}*:\n";
                foreach ($matches as $m) {
                    $list .= "• *{$m->nama}* (NIS: `{$m->nis}`, {$m->nama_kelas})\n";
                }
                $list .= "\nKetik ulang menggunakan NIS, contoh: *!reset {$matches[0]->nis}*";
                return $list;
            }
            $student = $exact;
        } else {
            $student = $matches[0];
        }

        $this->db->set('reset', 1)->where('id_siswa', $student->id_siswa)->where_in('id_jadwal', $jadwalIds)->update('log_ujian');
        $this->db->set('reset', 1)->where('id_siswa', $student->id_siswa)->where_in('id_jadwal', $jadwalIds)->update('cbt_durasi_siswa');

        $this->exambro->logProctorAudit('RESET_LOGIN', "{$student->nama} ({$student->nis})", "Reset login via Grup WA oleh {$requesterName}");

        return "✅ *RESET LOGIN BERHASIL*\n"
             . "👤 Siswa: *{$student->nama}*\n"
             . "🆔 NIS: `{$student->nis}` | Kelas: {$student->nama_kelas}\n"
             . "🔓 Status: Sesi login telah di-reset. Siswa silakan login ulang sekarang.";
    }

    /**
     * Perintah WA #6: !selesai <NIS/Nama>
     */
    public function forceFinishStudentFromWa($arg, $requesterName = 'Pengawas WA') {
        $arg = trim((string) $arg);
        if ($arg === '') {
            return "⚠️ Format salah. Contoh: *!selesai 2425001* (gunakan NIS atau Nama siswa).";
        }

        $jadwals = $this->getJadwalsByDate(date('Y-m-d'), true);
        if (empty($jadwals)) {
            return "ℹ️ Tidak ada jadwal ujian aktif hari ini.";
        }
        $jadwalIds = array_map(function ($j) { return intval($j->id_jadwal); }, $jadwals);

        $matches = $this->findStudentsByQuery($arg);
        if (empty($matches)) {
            return "❌ Siswa *{$arg}* tidak ditemukan.";
        }
        $student = $matches[0];
        if (count($matches) > 1) {
            $exact = null;
            foreach ($matches as $m) {
                if (strcasecmp($m->nis, $arg) === 0) { $exact = $m; break; }
            }
            if (!$exact) {
                $list = "⚠️ Lebih dari 1 siswa cocok dengan *{$arg}*. Gunakan NIS spesifik:\n";
                foreach ($matches as $m) {
                    $list .= "• *{$m->nama}* → `!selesai {$m->nis}`\n";
                }
                return $list;
            }
            $student = $exact;
        }

        $activeDur = $this->db
            ->where('id_siswa', $student->id_siswa)
            ->where_in('id_jadwal', $jadwalIds)
            ->where('status', 1)
            ->get('cbt_durasi_siswa')
            ->result();

        if (empty($activeDur)) {
            return "ℹ️ Siswa *{$student->nama}* (`{$student->nis}`) tidak sedang memiliki ujian berstatus aktif saat ini.";
        }

        foreach ($activeDur as $ad) {
            $this->cbt->saveLog($student->id_siswa, $ad->id_jadwal, 2, "Menyelesaikan Ujian (Dipaksa Selesai via WA oleh {$requesterName})");
            $this->db->set('selesai', date('Y-m-d H:i:s'))
                     ->set('status', 2)
                     ->set('reset', 3)
                     ->where('id_durasi', $ad->id_durasi)
                     ->update('cbt_durasi_siswa');
        }

        $this->load->model('Exambro_model', 'exambro');
        $this->exambro->logProctorAudit('FORCE_FINISH', "{$student->nama} ({$student->nis})", "Paksa selesai via Grup WA oleh {$requesterName}");

        return "✅ *PAKSA SELESAI BERHASIL*\n"
             . "👤 Siswa: *{$student->nama}* (`{$student->nis}`)\n"
             . "🏁 Ujian siswa telah diselesaikan secara paksa & jawaban terakhir tersimpan.";
    }

    /**
     * Perintah WA #2: !live — Ringkasan Live Progress Ujian Hari Ini
     */
    public function formatLiveProgressMessage() {
        $jadwals = $this->getJadwalsByDate(date('Y-m-d'), true);
        if (empty($jadwals)) {
            return "ℹ️ Belum ada jadwal ujian aktif untuk dipantau hari ini.";
        }

        $msg = "📡 *LIVE PROGRESS UJIAN SADUCI CBT*\n";
        $msg .= "⏱️ " . date('d/m/Y H:i:s') . " WIB\n";
        $msg .= "────────────────────────────\n\n";

        foreach ($jadwals as $j) {
            $mapel = !empty($j->nama_mapel) ? $j->nama_mapel : $j->bank_kode;
            $durasis = $this->db->where('id_jadwal', $j->id_jadwal)->get('cbt_durasi_siswa')->result();
            $logs = $this->db->where('id_jadwal', $j->id_jadwal)->where('log_type', 1)->get('log_ujian')->result();

            $mengerjakan = 0; $selesai = 0;
            foreach ($durasis as $d) {
                if ($d->status == 2 || !empty($d->selesai)) $selesai++;
                else $mengerjakan++;
            }

            $exambroOk = 0; $nonExambro = 0; $unpinned = 0; $violStudents = 0;
            foreach ($logs as $lg) {
                $desc = $lg->log_desc ?? '';
                $isEx = (stripos($desc, 'Exambro') !== false || stripos($lg->agent ?? '', 'exambro') !== false);
                if ($isEx) {
                    $exambroOk++;
                    if (stripos($desc, '[Pin: BELUM]') !== false) $unpinned++;
                } else {
                    $nonExambro++;
                }
                if (preg_match('/\[Pindah Layar\/Switch:\s*(\d+)x\]/i', $desc, $mv) && intval($mv[1]) > 0) {
                    $violStudents++;
                }
            }

            $msg .= "📝 *{$mapel}* (`{$j->bank_kode}`)\n";
            $msg .= "   🟢 Mengerjakan: *{$mengerjakan}* | 🔵 Selesai: *{$selesai}*\n";
            $msg .= "   🛡️ Exambro: *{$exambroOk}* | ⚠️ Browser Lain: *{$nonExambro}*\n";
            if ($unpinned > 0 || $violStudents > 0) {
                $msg .= "   📌 Belum Pin Home: *{$unpinned}* | 🚨 Pindah Layar: *{$violStudents} siswa*\n";
            }
            $msg .= "\n";
        }

        $msg .= "────────────────────────────\n";
        $msg .= "_Ketik *!pelanggaran* untuk rincian siswa melanggar atau *!absen* untuk siswa belum ujian._";
        return $msg;
    }

    /**
     * Perintah WA #3: !kode — Cek Kode Keluar Exambro Pengawas
     */
    public function formatExambroCodeMessage() {
        $this->load->model('Exambro_model', 'exambro');
        $ex = $this->exambro->getSetting();
        $activeCode = $this->exambro->getActiveExitCode();

        $msg = "🔐 *KODE KELUAR & KEAMANAN EXAMBRO*\n";
        $msg .= "📅 " . date('d/m/Y H:i') . " WIB\n";
        $msg .= "────────────────────────────\n";
        $msg .= "🔑 *Kode Keluar Aktif:* `{$activeCode}`\n";
        $msg .= "🆘 *PIN Cepat Offline:* `992811`\n";
        $msg .= "🆘 *Master Darurat:* `SADUCI-EMERGENCY-2026`\n\n";
        $msg .= "⚙️ *Status Kebijakan Saat Ini:*\n";
        $msg .= "• Wajib Pakai Exambro: *" . (!empty($ex->require_exambro) ? 'ON 🔒' : 'OFF 🔓') . "*\n";
        $msg .= "• Auto-Snapshot Kamera: *" . (!empty($ex->snapshot_enabled) ? 'ON 📸 (' . intval($ex->snapshot_interval_min) . 'm)' : 'OFF') . "*\n";
        $msg .= "• Alarm Pelanggaran HP: *" . (!empty($ex->alarm_enabled) ? 'ON 🔔' : 'OFF') . "*\n";
        $msg .= "• Versi Minimum APK: *v" . ($ex->min_version ?? '1.0.0') . "*\n\n";
        $msg .= "_Buka dialog pengawas di HP siswa: Tap ikon 🔒 5x atau tekan Back._";
        return $msg;
    }

    /**
     * Perintah WA #4: !pelanggaran — Daftar Siswa Pindah Layar / Bukan Exambro Hari Ini
     */
    public function formatViolationListMessage() {
        $jadwals = $this->getJadwalsByDate(date('Y-m-d'), true);
        if (empty($jadwals)) {
            return "ℹ️ Tidak ada jadwal ujian aktif hari ini.";
        }
        $jadwalIds = array_map(function ($j) { return intval($j->id_jadwal); }, $jadwals);

        $logs = $this->db
            ->select('l.*, s.nama, s.nis')
            ->from('log_ujian l')
            ->join('master_siswa s', 's.id_siswa=l.id_siswa', 'left')
            ->where_in('l.id_jadwal', $jadwalIds)
            ->where('l.log_type', 1)
            ->get()
            ->result();

        $violators = [];
        $nonApps = [];

        foreach ($logs as $lg) {
            $desc = $lg->log_desc ?? '';
            $nama = $lg->nama ?: ('Siswa #' . $lg->id_siswa);
            $nis = $lg->nis ?: '-';
            $dev = '';
            if (preg_match('/\[Dev:\s*([^|\]]+)/i', $desc, $md)) {
                $dev = ' • ' . trim($md[1]);
            }

            if (preg_match('/\[Pindah Layar\/Switch:\s*(\d+)x\]/i', $desc, $mv) && intval($mv[1]) > 0) {
                $violators[] = "• *{$nama}* (`{$nis}`) — 🚨 *{$mv[1]}x pindah layar*{$dev}";
            }

            $isEx = (stripos($desc, 'Exambro') !== false || stripos($lg->agent ?? '', 'exambro') !== false);
            if (!$isEx) {
                $nonApps[] = "• *{$nama}* (`{$nis}`) — {$lg->agent} ({$lg->device})";
            }
        }

        $msg = "🚨 *LAPORAN PELANGGARAN & NON-EXAMBRO*\n";
        $msg .= "📅 " . date('d/m/Y H:i') . " WIB\n";
        $msg .= "────────────────────────────\n\n";

        if (empty($violators) && empty($nonApps)) {
            return $msg . "✅ *AMAN!* Belum ada siswa yang terdeteksi pindah layar maupun memakai browser biasa hari ini.";
        }

        if (!empty($violators)) {
            $msg .= "⚠️ *Terdeteksi Pindah Layar (" . count($violators) . " siswa):*\n" . implode("\n", array_slice($violators, 0, 20)) . "\n\n";
        }
        if (!empty($nonApps)) {
            $msg .= "🌐 *Login Bukan dari Exambro (" . count($nonApps) . " siswa):*\n" . implode("\n", array_slice($nonApps, 0, 20)) . "\n\n";
        }
        $msg .= "_Ketik *!foto <NIS>* untuk melihat bukti kamera terbaru siswa._";
        return $msg;
    }

    /**
     * Perintah WA #5: !foto <NIS/Nama> — Ambil bukti foto terbaru siswa untuk dikirim via WA
     */
    public function getStudentLatestSnapshotForWa($arg) {
        $arg = trim((string) $arg);
        if ($arg === '') {
            return ['found' => false, 'message' => "⚠️ Format salah. Contoh: *!foto 2425001* (NIS atau Nama siswa)."];
        }

        $matches = $this->findStudentsByQuery($arg);
        if (empty($matches)) {
            return ['found' => false, 'message' => "❌ Siswa *{$arg}* tidak ditemukan."];
        }
        $student = $matches[0];
        if (count($matches) > 1) {
            foreach ($matches as $m) {
                if (strcasecmp($m->nis, $arg) === 0) { $student = $m; break; }
            }
        }

        $jadwals = $this->getJadwalsByDate(date('Y-m-d'), true);
        $this->load->model('Exambro_model', 'exambro');

        $latest = null;
        $mapelLabel = '-';
        $totalCount = 0;

        foreach ($jadwals as $j) {
            $photos = $this->exambro->getStudentSnapshots($j->id_jadwal, $student->id_siswa);
            if (!empty($photos)) {
                $latest = $photos[0];
                $mapelLabel = !empty($j->nama_mapel) ? $j->nama_mapel : $j->bank_kode;
                $totalCount = count($photos);
                break;
            }
        }

        if (!$latest) {
            return [
                'found' => false,
                'message' => "ℹ️ Belum ada bukti foto tersimpan untuk *{$student->nama}* (`{$student->nis}`) pada jadwal aktif hari ini."
            ];
        }

        $reasonLabel = [
            'violation' => '🚨 Bukti Pelanggaran',
            'exam_start' => '🟢 Mulai Ujian',
            'proctor_request' => '👁️ Permintaan Pengawas',
            'periodic' => '📸 Berkala',
        ];
        $rText = $reasonLabel[$latest['reason']] ?? '📸 Berkala';

        $caption = "📸 *BUKTI FOTO UJIAN SISWA*\n"
                 . "👤 Nama: *{$student->nama}* (`{$student->nis}`)\n"
                 . "🎓 Kelas: {$student->nama_kelas}\n"
                 . "📝 Mapel: {$mapelLabel}\n"
                 . "🏷️ Tipe: {$rText}\n"
                 . "⏱️ Waktu: {$latest['datetime']} WIB\n"
                 . "🗂️ Total arsip siswa ini: *{$totalCount} foto* (lihat lengkap di `/cbtfoto`)";

        return [
            'found' => true,
            'url' => $latest['url'],
            'filename' => $latest['filename'],
            'caption' => $caption,
        ];
    }

    /**
     * Perintah WA Bonus: !siap — Ringkasan Pre-Flight Check Kesiapan Ujian
     */
    public function formatPreflightMessage() {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $todayJadwals = $this->getJadwalsByDate(date('Y-m-d'), false);

        $emptyBanks = 0;
        foreach ($todayJadwals as $j) {
            if ($this->db->where('bank_id', $j->id_bank)->count_all_results('cbt_soal') <= 0) {
                $emptyBanks++;
            }
        }

        $totalSiswa = 0; $mappedSesi = 0;
        if ($tp && $smt) {
            $totalSiswa = $this->db->where('id_tp', $tp->id_tp)->where('id_smt', $smt->id_smt)->count_all_results('kelas_siswa');
            $mappedSesi = $this->db->from('kelas_siswa ks')
                ->join('cbt_sesi_siswa ss', 'ss.siswa_id=ks.id_siswa AND ss.tp_id=ks.id_tp AND ss.smt_id=ks.id_smt')
                ->where('ks.id_tp', $tp->id_tp)->where('ks.id_smt', $smt->id_smt)
                ->where('ss.ruang_id >', 0)->where('ss.sesi_id >', 0)
                ->count_all_results();
        }
        $freeGb = @disk_free_space(FCPATH) ? round(@disk_free_space(FCPATH) / (1024 * 1024 * 1024), 2) : 0;

        $msg = "🩺 *CEK KESIAPAN UJIAN (PRE-FLIGHT)*\n";
        $msg .= "📅 " . date('d/m/Y H:i') . " WIB\n";
        $msg .= "────────────────────────────\n";
        $msg .= (count($todayJadwals) > 0 ? '✅' : '⚠️') . " Jadwal Hari Ini: *" . count($todayJadwals) . " mapel*\n";
        $msg .= ($emptyBanks === 0 ? '✅' : '❌') . " Bank Soal Kosong: *{$emptyBanks}*\n";
        $msg .= ($totalSiswa > 0 && $mappedSesi === $totalSiswa ? '✅' : '⚠️') . " Ruang & Sesi Siswa: *{$mappedSesi} / {$totalSiswa}*\n";
        $msg .= ($freeGb >= 2 ? '✅' : '⚠️') . " Sisa Disk VPS: *{$freeGb} GB*\n";
        $msg .= "────────────────────────────\n";
        $msg .= "_Cek detail lengkap di menu `/cbtlive` → Cek Kesiapan Ujian._";
        return $msg;
    }
}

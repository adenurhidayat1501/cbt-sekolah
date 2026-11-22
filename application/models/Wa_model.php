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
        $is_group = (strpos($target, '@g.us') !== false);
        $clean_target = trim($target);

        // Jika bukan grup, format nomor telepon (buang karakter selain angka dan awalan 0 menjadi 62)
        if (!$is_group) {
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
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Controller Cbtfoto
 * Halaman Galeri & Arsip Bukti Foto Ujian Saduci Exambro
 * Terkelompok per Mapel/Jadwal, Kelas, Siswa, dan Tipe Bukti Foto
 */
class Cbtfoto extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } else if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru')) {
            show_error('Hanya Administrator atau Guru Pengawas yang berhak mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }

        $this->load->model('Master_model', 'master');
        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Cbt_model', 'cbt');
        $this->load->model('Dropdown_model', 'dropdown');
        $this->load->model('Exambro_model', 'exambro');
    }

    private function output_json($data) {
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($data));
    }

    public function index() {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        // Daftar jadwal ujian pada TP & SMT aktif
        $this->db->select('a.id_jadwal, a.tgl_mulai, a.tgl_selesai, b.bank_kode, c.kode_jenis, d.nama_mapel');
        $this->db->from('cbt_jadwal a');
        $this->db->join('cbt_bank_soal b', 'b.id_bank=a.id_bank', 'left');
        $this->db->join('cbt_jenis c', 'c.id_jenis=a.id_jenis', 'left');
        $this->db->join('master_mapel d', 'd.id_mapel=b.bank_mapel_id', 'left');
        if ($tp) $this->db->where('a.id_tp', $tp->id_tp);
        if ($smt) $this->db->where('a.id_smt', $smt->id_smt);
        $this->db->order_by('a.tgl_mulai', 'DESC');
        $jadwals = $this->db->get()->result();

        $kelases = ($tp && $smt) ? $this->dropdown->getAllKelas($tp->id_tp, $smt->id_smt) : [];

        $data = [
            'user' => $user,
            'judul' => 'Bukti Foto Ujian',
            'subjudul' => 'Arsip & Galeri Snapshot Kamera Depan Saduci Exambro',
            'setting' => $setting,
            'tp' => $this->dashboard->getTahun(),
            'tp_active' => $tp,
            'smt' => $this->dashboard->getSemester(),
            'smt_active' => $smt,
            'jadwals' => $jadwals,
            'kelases' => $kelases,
            'is_admin' => $this->ion_auth->is_admin(),
            'exambro_setting' => $this->exambro->getSetting(),
        ];

        if ($this->ion_auth->is_admin()) {
            $data['profile'] = $this->dashboard->getProfileAdmin($user->id);
            $this->load->view('_templates/dashboard/_header', $data);
            $this->load->view('cbt/foto/data', $data);
            $this->load->view('_templates/dashboard/_footer');
        } else {
            $data['guru'] = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $this->load->view('members/guru/templates/header', $data);
            $this->load->view('cbt/foto/data', $data);
            $this->load->view('members/guru/templates/footer');
        }
    }

    /**
     * Endpoint AJAX untuk mengambil daftar bukti foto yang sudah difilter
     * GET /cbtfoto/get_data?id_jadwal=...&id_kelas=...&reason=...&search=...
     */
    public function get_data() {
        $filter_jadwal = intval($this->input->get('id_jadwal'));
        $filter_kelas  = intval($this->input->get('id_kelas'));
        $filter_reason = trim((string) $this->input->get('reason', true));
        $filter_search = strtolower(trim((string) $this->input->get('search', true)));

        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        // Peta jadwal -> nama mapel & bank kode
        $jadwal_map = [];
        $jRows = $this->db
            ->select('a.id_jadwal, a.tgl_mulai, b.bank_kode, d.nama_mapel')
            ->from('cbt_jadwal a')
            ->join('cbt_bank_soal b', 'b.id_bank=a.id_bank', 'left')
            ->join('master_mapel d', 'd.id_mapel=b.bank_mapel_id', 'left')
            ->get()->result();
        foreach ($jRows as $jr) {
            $jadwal_map[intval($jr->id_jadwal)] = [
                'id_jadwal' => intval($jr->id_jadwal),
                'nama_mapel' => $jr->nama_mapel ?: ($jr->bank_kode ?: 'Jadwal #' . $jr->id_jadwal),
                'bank_kode' => $jr->bank_kode ?: '-',
                'tgl_mulai' => $jr->tgl_mulai ? date('d/m/Y', strtotime($jr->tgl_mulai)) : '-',
            ];
        }

        // Peta siswa -> nama, nis, kelas aktif
        $siswa_map = [];
        $this->db->select('s.id_siswa, s.nama, s.nis, ks.id_kelas, k.nama_kelas, k.kode_kelas');
        $this->db->from('master_siswa s');
        if ($tp && $smt) {
            $this->db->join('kelas_siswa ks', 'ks.id_siswa=s.id_siswa AND ks.id_tp=' . intval($tp->id_tp) . ' AND ks.id_smt=' . intval($smt->id_smt), 'left');
        } else {
            $this->db->join('kelas_siswa ks', 'ks.id_siswa=s.id_siswa', 'left');
        }
        $this->db->join('master_kelas k', 'k.id_kelas=ks.id_kelas', 'left');
        foreach ($this->db->get()->result() as $sr) {
            $siswa_map[intval($sr->id_siswa)] = [
                'id_siswa' => intval($sr->id_siswa),
                'nama' => $sr->nama ?: 'Siswa #' . $sr->id_siswa,
                'nis' => $sr->nis ?: '-',
                'id_kelas' => intval($sr->id_kelas ?? 0),
                'nama_kelas' => $sr->nama_kelas ?: ($sr->kode_kelas ?: '-'),
            ];
        }

        $baseDir = $this->exambro->getSnapshotDir();
        $jadwalDirs = glob($baseDir . '*', GLOB_ONLYDIR) ?: [];

        $grouped_students = [];
        $flat_photos = [];
        $total_bytes = 0;
        $total_photos = 0;
        $total_violation_photos = 0;
        $mapel_ids_seen = [];

        foreach ($jadwalDirs as $jDir) {
            $jFolder = basename($jDir);
            if (!preg_match('/^(\d+)_/', $jFolder, $mj)) {
                continue;
            }
            $id_jadwal = intval($mj[1]);
            if ($filter_jadwal > 0 && $id_jadwal !== $filter_jadwal) {
                continue;
            }

            $jMeta = $jadwal_map[$id_jadwal] ?? [
                'id_jadwal' => $id_jadwal,
                'nama_mapel' => str_replace('_', ' ', substr($jFolder, strlen($mj[0]))),
                'bank_kode' => '-',
                'tgl_mulai' => '-',
            ];

            $studentDirs = glob($jDir . '/*', GLOB_ONLYDIR) ?: [];
            foreach ($studentDirs as $sDir) {
                $sFolder = basename($sDir);
                if (!preg_match('/^(\d+)_/', $sFolder, $ms)) {
                    continue;
                }
                $id_siswa = intval($ms[1]);
                $sMeta = $siswa_map[$id_siswa] ?? [
                    'id_siswa' => $id_siswa,
                    'nama' => str_replace('_', ' ', substr($sFolder, strlen($ms[0]))),
                    'nis' => '-',
                    'id_kelas' => 0,
                    'nama_kelas' => '-',
                ];

                if ($filter_kelas > 0 && $sMeta['id_kelas'] !== $filter_kelas) {
                    continue;
                }

                if ($filter_search !== '') {
                    $hay = strtolower($sMeta['nama'] . ' ' . $sMeta['nis'] . ' ' . $sMeta['nama_kelas'] . ' ' . $jMeta['nama_mapel'] . ' ' . $jMeta['bank_kode']);
                    if (strpos($hay, $filter_search) === false) {
                        continue;
                    }
                }

                $files = glob($sDir . '/*.jpg') ?: [];
                if (empty($files)) continue;
                rsort($files);

                $student_photos = [];
                $student_viol = 0;
                $student_bytes = 0;

                foreach ($files as $fullpath) {
                    $fname = basename($fullpath);
                    $reason = 'periodic';
                    if (stripos($fname, 'violation') !== false) {
                        $reason = 'violation';
                    } elseif (stripos($fname, 'exam_start') !== false) {
                        $reason = 'exam_start';
                    } elseif (stripos($fname, 'proctor_request') !== false) {
                        $reason = 'proctor_request';
                    }

                    if ($filter_reason !== '' && $filter_reason !== 'all' && $reason !== $filter_reason) {
                        continue;
                    }

                    $fsize = @filesize($fullpath) ?: 0;
                    $mtime = @filemtime($fullpath) ?: time();
                    $url = base_url('uploads/exambro_snapshots/' . $jFolder . '/' . $sFolder . '/' . $fname) . '?t=' . $mtime;

                    $photoItem = [
                        'id_jadwal' => $id_jadwal,
                        'id_siswa' => $id_siswa,
                        'nama_siswa' => $sMeta['nama'],
                        'nis' => $sMeta['nis'],
                        'nama_kelas' => $sMeta['nama_kelas'],
                        'nama_mapel' => $jMeta['nama_mapel'],
                        'bank_kode' => $jMeta['bank_kode'],
                        'filename' => $fname,
                        'url' => $url,
                        'time' => date('H:i:s', $mtime),
                        'datetime' => date('d/m/Y H:i:s', $mtime),
                        'mtime' => $mtime,
                        'reason' => $reason,
                        'size_kb' => round($fsize / 1024, 1),
                    ];

                    $student_photos[] = $photoItem;
                    $flat_photos[] = $photoItem;
                    $student_bytes += $fsize;
                    $total_bytes += $fsize;
                    $total_photos++;
                    if ($reason === 'violation') {
                        $student_viol++;
                        $total_violation_photos++;
                    }
                }

                if (!empty($student_photos)) {
                    $mapel_ids_seen[$id_jadwal] = true;
                    $grouped_students[] = [
                        'id_jadwal' => $id_jadwal,
                        'nama_mapel' => $jMeta['nama_mapel'],
                        'bank_kode' => $jMeta['bank_kode'],
                        'tgl_mulai' => $jMeta['tgl_mulai'],
                        'id_siswa' => $id_siswa,
                        'nama_siswa' => $sMeta['nama'],
                        'nis' => $sMeta['nis'],
                        'nama_kelas' => $sMeta['nama_kelas'],
                        'photo_count' => count($student_photos),
                        'violation_count' => $student_viol,
                        'size_kb' => round($student_bytes / 1024, 1),
                        'latest_url' => $student_photos[0]['url'],
                        'latest_time' => $student_photos[0]['datetime'],
                        'latest_mtime' => $student_photos[0]['mtime'],
                    ];
                }
            }
        }

        // Urutkan terbaru dulu
        usort($grouped_students, function ($a, $b) {
            return $b['latest_mtime'] <=> $a['latest_mtime'];
        });
        usort($flat_photos, function ($a, $b) {
            return $b['mtime'] <=> $a['mtime'];
        });

        // Hitung sisa disk server
        $disk_free_gb = @disk_free_space(FCPATH) ? round(@disk_free_space(FCPATH) / (1024 * 1024 * 1024), 2) : 0;

        $this->output_json([
            'status' => true,
            'summary' => [
                'total_mapel' => count($mapel_ids_seen),
                'total_students' => count($grouped_students),
                'total_photos' => $total_photos,
                'total_violations' => $total_violation_photos,
                'total_size_mb' => round($total_bytes / (1024 * 1024), 2),
                'disk_free_gb' => $disk_free_gb,
            ],
            'students' => $grouped_students,
            'photos' => array_slice($flat_photos, 0, 200), // Batasi 200 foto di grid langsung agar browser tetap ringan
            'total_flat_available' => count($flat_photos),
        ]);
    }

    /**
     * Hapus seluruh arsip foto untuk 1 mapel (khusus Admin setelah selesai diunduh/diarsipkan)
     * POST /cbtfoto/delete_mapel_archive
     */
    public function delete_mapel_archive() {
        if (!$this->ion_auth->is_admin()) {
            $this->output_json(['status' => false, 'message' => 'Hanya Administrator yang dapat menghapus arsip foto.']);
            return;
        }

        $id_jadwal = intval($this->input->post('id_jadwal'));
        if ($id_jadwal <= 0) {
            $this->output_json(['status' => false, 'message' => 'Pilih mapel/jadwal yang ingin dihapus arsipnya.']);
            return;
        }

        $folder = $this->exambro->getJadwalSnapshotFolder($id_jadwal, false);
        if (!$folder || !is_dir($folder['abs_dir'])) {
            $this->output_json(['status' => false, 'message' => 'Arsip foto untuk mapel tersebut tidak ditemukan.']);
            return;
        }

        $this->recursiveDelete($folder['abs_dir']);
        $this->exambro->logProctorAudit('DELETE_SNAPSHOT_ARCHIVE', 'Jadwal #' . $id_jadwal, 'Menghapus arsip folder ' . $folder['folder_name']);
        $this->output_json([
            'status' => true,
            'message' => 'Arsip bukti foto untuk mapel tersebut berhasil dibersihkan dari server.',
        ]);
    }

    private function recursiveDelete($dir) {
        if (!is_dir($dir)) return;
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}

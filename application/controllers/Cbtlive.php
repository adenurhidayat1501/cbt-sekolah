<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Controller Cbtlive
 * Live Real-time Exam & Server Monitoring
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
 * @property Dropdown_model $dropdown
 */
class Cbtlive extends CI_Controller {

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

    public function output_json($data, $encode = true) {
        if ($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    public function index() {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();

        $data = [
            'user' => $user,
            'judul' => 'Live Monitor Ujian',
            'subjudul' => 'Real-Time Monitoring Ujian & Server',
            'setting' => $setting,
        ];

        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $data['tp'] = $this->dashboard->getTahun();
        $data['tp_active'] = $tp;
        $data['smt'] = $this->dashboard->getSemester();
        $data['smt_active'] = $smt;

        $today = date('Y-m-d');
        $data['today'] = $today;

        // Ambil semua jadwal aktif pada TP dan SMT berjalan
        $this->db->select('a.id_jadwal, a.id_tp, a.id_smt, a.id_bank, a.id_jenis, a.tgl_mulai, a.tgl_selesai,
                           a.durasi_ujian, a.acak_soal, a.acak_opsi, a.token, a.status, a.ulang, a.reset_login, a.jam_ke,
                           b.bank_kode, b.bank_level, b.bank_kelas, c.kode_jenis, d.kode, d.nama_mapel');
        $this->db->from('cbt_jadwal a');
        $this->db->join('cbt_bank_soal b', 'b.id_bank=a.id_bank');
        $this->db->join('cbt_jenis c', 'c.id_jenis=a.id_jenis', 'left');
        $this->db->join('master_mapel d', 'd.id_mapel=b.bank_mapel_id', 'left');
        $this->db->where('a.id_tp', $tp->id_tp);
        $this->db->where('a.id_smt', $smt->id_smt);
        $this->db->where('a.status', '1');
        $this->db->order_by('a.tgl_mulai', 'DESC');
        $all_jadwals = $this->db->get()->result();

        // Pisahkan jadwal hari ini dan jadwal lainnya
        $today_jadwals = [];
        $other_jadwals = [];
        foreach ($all_jadwals as $j) {
            $mulai = date('Y-m-d', strtotime($j->tgl_mulai));
            $selesai = date('Y-m-d', strtotime($j->tgl_selesai));
            if ($today >= $mulai && $today <= $selesai) {
                $today_jadwals[] = $j;
            } else {
                $other_jadwals[] = $j;
            }
        }

        $data['all_jadwals'] = $all_jadwals;
        $data['today_jadwals'] = $today_jadwals;
        $data['other_jadwals'] = $other_jadwals;
        $data['kelases'] = $this->dropdown->getAllKelas($tp->id_tp, $smt->id_smt);
        $data['exambro_setting'] = $this->exambro->getSetting();
        $data['active_exit_code'] = $this->exambro->getActiveExitCode();

        if ($this->ion_auth->is_admin()) {
            $data['profile'] = $this->dashboard->getProfileAdmin($user->id);
            $this->load->view('_templates/dashboard/_header', $data);
            $this->load->view('cbt/live/data', $data);
            $this->load->view('_templates/dashboard/_footer');
        } else {
            $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $data['guru'] = $guru;
            $this->load->view('members/guru/templates/header', $data);
            $this->load->view('cbt/live/data', $data);
            $this->load->view('members/guru/templates/footer');
        }
    }

    /**
     * Mengambil daftar kelas yang berhak mengikuti jadwal ujian tertentu
     */
    public function get_kelas_by_jadwal() {
        $id_jadwal = $this->input->get('id_jadwal', true);
        if (!$id_jadwal) {
            $this->output_json([]);
            return;
        }

        $jadwal = $this->cbt->getJadwalById($id_jadwal);
        if (!$jadwal) {
            $this->output_json([]);
            return;
        }

        $kelas_raw = @unserialize($jadwal->bank_kelas ?? '');
        $kelas_ids = [];
        if (is_array($kelas_raw)) {
            foreach ($kelas_raw as $k) {
                if (!empty($k['kelas_id'])) {
                    $kelas_ids[] = $k['kelas_id'];
                }
            }
        }

        if (empty($kelas_ids)) {
            $this->output_json([]);
            return;
        }

        $this->db->select('id_kelas, nama_kelas, kode_kelas');
        $this->db->from('master_kelas');
        $this->db->where_in('id_kelas', $kelas_ids);
        $this->db->order_by('level_id', 'ASC');
        $this->db->order_by('nama_kelas', 'ASC');
        $classes = $this->db->get()->result();

        $this->output_json($classes);
    }

    /**
     * Mengambil live data ujian siswa berdasarkan id_jadwal dan id_kelas
     */
    public function get_live_data() {
        $id_jadwal = $this->input->get('id_jadwal', true);
        $id_kelas = $this->input->get('id_kelas', true);

        if (!$id_jadwal || !$id_kelas) {
            $this->output_json(['status' => false, 'message' => 'Parameter jadwal dan kelas wajib diisi']);
            return;
        }

        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        // 1. Info Jadwal
        $info = $this->cbt->getJadwalById($id_jadwal);
        if (!$info) {
            $this->output_json(['status' => false, 'message' => 'Jadwal tidak ditemukan']);
            return;
        }

        // 2. Daftar Siswa pada Kelas
        $this->db->select('b.id_siswa, b.nama, b.nis, b.nisn, b.username, b.foto, 
                           f.id_kelas, f.nama_kelas, f.kode_kelas, 
                           g.nomor_peserta, 
                           d.id_ruang, d.kode_ruang, d.nama_ruang, 
                           e.id_sesi, e.kode_sesi, e.nama_sesi');
        $this->db->from('kelas_siswa a');
        $this->db->join('master_siswa b', 'b.id_siswa=a.id_siswa');
        $this->db->join('master_kelas f', 'f.id_kelas=a.id_kelas', 'left');
        $this->db->join('cbt_nomor_peserta g', 'g.id_siswa=a.id_siswa AND g.id_tp=' . $tp->id_tp, 'left');
        $this->db->join('cbt_sesi_siswa c', 'c.siswa_id=a.id_siswa AND c.tp_id=' . $tp->id_tp . ' AND c.smt_id=' . $smt->id_smt, 'left');
        $this->db->join('cbt_ruang d', 'd.id_ruang=c.ruang_id', 'left');
        $this->db->join('cbt_sesi e', 'e.id_sesi=c.sesi_id', 'left');
        $this->db->where('a.id_tp', $tp->id_tp);
        $this->db->where('a.id_smt', $smt->id_smt);
        $this->db->where('a.id_kelas', $id_kelas);
        $this->db->order_by('b.nama', 'ASC');
        $siswas = $this->db->get()->result();

        // 3. Durasi & Log Siswa pada Jadwal ini
        $durasies = $this->cbt->getDurasiSiswaByJadwal($id_jadwal);
        $durasi_map = [];
        foreach ($durasies as $dur) {
            $durasi_map[$dur->id_siswa] = $dur;
        }

        $logs = $this->cbt->getLogUjianByJadwal($id_jadwal);
        $log_map = [];
        foreach ($logs as $log) {
            $log_map[$log->id_siswa][] = $log;
        }

        // 4. Progres Soal dari cbt_soal_siswa
        $this->db->select('id_siswa, COUNT(id_soal_siswa) as total_soal, 
                           SUM(CASE WHEN jawaban_siswa IS NOT NULL AND TRIM(jawaban_siswa) != "" AND jawaban_siswa != "N;" THEN 1 ELSE 0 END) as terjawab');
        $this->db->from('cbt_soal_siswa');
        $this->db->where('id_jadwal', $id_jadwal);
        $this->db->group_by('id_siswa');
        $progress_rows = $this->db->get()->result();

        $progress_map = [];
        foreach ($progress_rows as $pr) {
            $total = intval($pr->total_soal);
            $terjawab = intval($pr->terjawab);
            $persen = $total > 0 ? round(($terjawab / $total) * 100) : 0;
            $progress_map[$pr->id_siswa] = [
                'total' => $total,
                'terjawab' => $terjawab,
                'persen' => $persen
            ];
        }

        // 5. Data Pengawas untuk Jadwal ini
        $this->db->select('p.*, r.kode_ruang, r.nama_ruang, s.kode_sesi, s.nama_sesi');
        $this->db->from('cbt_pengawas p');
        $this->db->join('cbt_ruang r', 'r.id_ruang=p.id_ruang', 'left');
        $this->db->join('cbt_sesi s', 's.id_sesi=p.id_sesi', 'left');
        $this->db->where('p.id_jadwal', $id_jadwal);
        $this->db->where('p.id_tp', $tp->id_tp);
        $this->db->where('p.id_smt', $smt->id_smt);
        $pengawas_rows = $this->db->get()->result();

        $pengawas_list = [];
        $ids_guru_all = [];
        foreach ($pengawas_rows as $prow) {
            if (!empty($prow->id_guru)) {
                $ids = explode(',', $prow->id_guru);
                foreach ($ids as $ig) {
                    $ig = trim($ig);
                    if ($ig !== '' && !in_array($ig, $ids_guru_all)) {
                        $ids_guru_all[] = $ig;
                    }
                }
            }
        }

        $guru_map = [];
        if (!empty($ids_guru_all)) {
            $gurus = $this->master->getGuruByArrId($ids_guru_all);
            foreach ($gurus as $g) {
                $guru_map[$g->id_guru] = $g;
            }
        }

        foreach ($pengawas_rows as $prow) {
            $g_names = [];
            if (!empty($prow->id_guru)) {
                $ids = explode(',', $prow->id_guru);
                foreach ($ids as $ig) {
                    $ig = trim($ig);
                    if (isset($guru_map[$ig])) {
                        $g_names[] = [
                            'id_guru' => $ig,
                            'nama' => $guru_map[$ig]->nama_guru,
                            'nip' => $guru_map[$ig]->nip ?? '-',
                        ];
                    }
                }
            }

            $pengawas_list[] = [
                'ruang' => !empty($prow->nama_ruang) ? $prow->nama_ruang : ($prow->kode_ruang ?? 'Semua Ruang'),
                'sesi' => !empty($prow->nama_sesi) ? $prow->nama_sesi : ($prow->kode_sesi ?? 'Semua Sesi'),
                'gurus' => $g_names
            ];
        }

        // 6. Evaluasi Setiap Siswa
        $student_list = [];
        $count_total = count($siswas);
        $count_mengerjakan = 0;
        $count_selesai = 0;
        $count_belum = 0;
        $count_reset = 0;
        $count_exambro = 0;
        $count_non_exambro = 0;
        $count_unpinned = 0;
        $count_total_violations = 0;

        $durasi_menit_jadwal = intval($info->durasi_ujian);

        foreach ($siswas as $s) {
            $dur = isset($durasi_map[$s->id_siswa]) ? $durasi_map[$s->id_siswa] : null;
            $s_logs = isset($log_map[$s->id_siswa]) ? $log_map[$s->id_siswa] : [];

            $status_code = 0; // 0 = Belum Mulai, 1 = Mengerjakan, 2 = Selesai, 3 = Perlu Reset
            $status_label = 'Belum Mulai';
            $status_badge = 'badge-secondary';

            $waktu_mulai = '-';
            $waktu_selesai = '-';
            $sisa_waktu = '-';
            $sisa_menit = 0;
            $lama_ujian = '-';
            $ip_address = '-';
            $user_agent = '-';
            $tab_switch_count = 0;
            $login_reset_flag = false;
            $exambro_battery = null;
            $exambro_charging = false;
            $exambro_network = null;
            $exambro_device_name = null;
            $exambro_os = null;
            $is_screen_pinned = null;
            $exambro_telemetry = '';
            $detected_exambro_desc = false;

            // Periksa logs
            foreach ($s_logs as $lg) {
                if ($lg->log_type == '1') { // Login ujian
                    $waktu_mulai = date('H:i:s', strtotime($lg->log_time));
                    $ip_address = $lg->address ?? '-';
                    $user_agent = $lg->agent . ' (' . ($lg->device ?? 'PC') . ')';
                    if ($lg->reset == 1) {
                        $login_reset_flag = true;
                    }
                } else if ($lg->log_type == '2') { // Selesai
                    $waktu_selesai = date('H:i:s', strtotime($lg->log_time));
                }

                $desc = $lg->log_desc ?? '';

                if (stripos($desc, 'Exambro') !== false) {
                    $detected_exambro_desc = true;
                }

                // Ekstrak nama & model device siswa dari heartbeat Exambro
                if (preg_match('/\[Dev:\s*([^|\]]+?)(?:\s*\|\s*OS:\s*([^\]]+))?\]/i', $desc, $md)) {
                    $exambro_device_name = trim($md[1]);
                    $exambro_os = isset($md[2]) ? trim($md[2]) : null;
                }

                // Ekstrak status Kunci Tombol Home (Screen Pinning)
                if (preg_match('/\[Pin:\s*(AKTIF|BELUM)\]/i', $desc, $mp)) {
                    $is_screen_pinned = (strtoupper($mp[1]) === 'AKTIF');
                }

                // Ekstrak telemetri Exambro (baterai & jaringan) dari heartbeat
                if (preg_match('/\[Bat:\s*(\d+)%\s*\(([^)]+)\)\s*\|\s*Net:\s*([^\]]+)\]/i', $desc, $m)) {
                    $exambro_battery = intval($m[1]);
                    $exambro_charging = (stripos($m[2], 'charging') !== false);
                    $exambro_network = trim($m[3]);
                    $exambro_telemetry = "Bat: {$exambro_battery}% (" . ($exambro_charging ? 'Charging' : 'Baterai') . ") | Net: {$exambro_network}";
                }

                // Hitung log kecurigaan / pindah layar (baik angka di log_desc heartbeat maupun baris log)
                if (preg_match('/\[Pindah Layar\/Switch:\s*(\d+)x\]/i', $desc, $mv)) {
                    $tab_switch_count = max($tab_switch_count, intval($mv[1]));
                } else if (stripos($desc, 'pindah') !== false || stripos($desc, 'switch') !== false) {
                    $tab_switch_count++;
                }
            }

            $is_exambro = $detected_exambro_desc || (($user_agent !== '-') && (stripos($user_agent, 'saduciexambro') !== false || stripos($user_agent, 'exambro') !== false));
            if ($user_agent !== '-') {
                if ($is_exambro) {
                    $count_exambro++;
                    if ($is_screen_pinned === false) {
                        $count_unpinned++;
                    }
                } else {
                    $count_non_exambro++;
                }
            }
            $count_total_violations += $tab_switch_count;
            $snapshot_info = $this->exambro->getSnapshotInfo($id_jadwal, $s->id_siswa);

            if ($dur == null) {
                $status_code = 0;
                $status_label = 'Belum Mulai';
                $status_badge = 'badge-secondary';
                $count_belum++;
            } else {
                if ($dur->selesai != null || $dur->status == 2) {
                    $status_code = 2;
                    $status_label = 'Selesai';
                    $status_badge = 'badge-primary';
                    $waktu_selesai = date('H:i:s', strtotime($dur->selesai));
                    $count_selesai++;

                    // Lama ujian
                    if ($dur->mulai != null && $dur->selesai != null) {
                        $diff_sec = strtotime($dur->selesai) - strtotime($dur->mulai);
                        $m = floor($diff_sec / 60);
                        $s_sec = $diff_sec % 60;
                        $lama_ujian = "{$m}m {$s_sec}d";
                    }
                } else {
                    // Masih aktif mengerjakan
                    $start_time = strtotime($dur->mulai);
                    $now = time();
                    $elapsed_sec = max(0, $now - $start_time);
                    $total_sec = $durasi_menit_jadwal * 60;
                    $remaining_sec = max(0, $total_sec - $elapsed_sec);

                    $m_rem = floor($remaining_sec / 60);
                    $s_rem = $remaining_sec % 60;
                    $sisa_waktu = sprintf("%02d:%02d", $m_rem, $s_rem);
                    $sisa_menit = round($remaining_sec / 60, 1);

                    $m_el = floor($elapsed_sec / 60);
                    $s_el = $elapsed_sec % 60;
                    $lama_ujian = sprintf("%02d:%02d", $m_el, $s_el);

                    // Cek jika siswa butuh reset login (reset_login aktif & reset == 0 di log)
                    if ($info->reset_login == '1' && $dur->reset == 0 && count($s_logs) > 0 && !$login_reset_flag) {
                        $status_code = 3;
                        $status_label = 'Perlu Reset';
                        $status_badge = 'badge-danger';
                        $count_reset++;
                    } else {
                        $status_code = 1;
                        $status_label = 'Mengerjakan';
                        $status_badge = 'badge-success';
                        $count_mengerjakan++;
                    }
                }
            }

            // Progres Soal
            $progress = isset($progress_map[$s->id_siswa]) ? $progress_map[$s->id_siswa] : ['total' => 0, 'terjawab' => 0, 'persen' => 0];

            $student_list[] = [
                'id_siswa' => $s->id_siswa,
                'nama' => $s->nama,
                'nis' => $s->nis,
                'nomor_peserta' => $s->nomor_peserta ?? '-',
                'nama_kelas' => $s->nama_kelas ?? $s->kode_kelas,
                'kode_ruang' => $s->kode_ruang ?? '-',
                'kode_sesi' => $s->kode_sesi ?? '-',
                'status_code' => $status_code,
                'status_label' => $status_label,
                'status_badge' => $status_badge,
                'waktu_mulai' => $waktu_mulai,
                'waktu_selesai' => $waktu_selesai,
                'lama_ujian' => $lama_ujian,
                'sisa_waktu' => $sisa_waktu,
                'sisa_menit' => $sisa_menit,
                'progress' => $progress,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'is_exambro' => $is_exambro,
                'is_screen_pinned' => $is_screen_pinned,
                'exambro_device_name' => $exambro_device_name,
                'exambro_os' => $exambro_os,
                'exambro_battery' => $exambro_battery,
                'exambro_charging' => $exambro_charging,
                'exambro_network' => $exambro_network,
                'exambro_telemetry' => $exambro_telemetry,
                'snapshot' => $snapshot_info,
                'tab_switch_count' => $tab_switch_count,
            ];
        }

        $response = [
            'status' => true,
            'info_jadwal' => [
                'id_jadwal' => $info->id_jadwal,
                'bank_kode' => $info->bank_kode,
                'nama_mapel' => $info->nama_mapel ?? '-',
                'durasi' => $info->durasi_ujian . ' Menit',
                'token' => $info->token,
                'tgl_mulai' => date('d-m-Y H:i', strtotime($info->tgl_mulai)),
                'tgl_selesai' => date('d-m-Y H:i', strtotime($info->tgl_selesai)),
                'reset_login' => $info->reset_login == '1',
            ],
            'pengawas' => $pengawas_list,
            'summary' => [
                'total' => $count_total,
                'mengerjakan' => $count_mengerjakan,
                'selesai' => $count_selesai,
                'belum_mulai' => $count_belum,
                'perlu_reset' => $count_reset,
                'using_exambro' => $count_exambro,
                'non_exambro' => $count_non_exambro,
                'unpinned_exambro' => $count_unpinned,
                'total_violations' => $count_total_violations,
            ],
            'exambro_security' => [
                'daily_exit_code' => 'SDC' . date('dm'),
                'master_emergency_code' => 'SADUCI-EMERGENCY-2026',
                'numeric_emergency_pin' => '992811',
            ],
            'students' => $student_list,
            'server_time' => date('d-m-Y H:i:s'),
        ];

        $this->output_json($response);
    }

    /**
     * Mengambil statistik resource server secara real-time
     */
    public function get_system_stats() {
        // 1. CPU Load
        $load = [0, 0, 0];
        $cpu_count = 1;
        $cpu_percent = 0;
        if (function_exists('sys_getloadavg')) {
            $sys_load = sys_getloadavg();
            if (is_array($sys_load)) {
                $load = [round($sys_load[0], 2), round($sys_load[1], 2), round($sys_load[2], 2)];
            }
        }
        if (@is_readable('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo');
            $cpu_count = substr_count($cpuinfo, 'processor');
            if ($cpu_count < 1) $cpu_count = 1;
        }
        $cpu_percent = min(100, round(($load[0] / $cpu_count) * 100));

        // 2. RAM Usage
        $ram_total = 0;
        $ram_free = 0;
        $ram_used = 0;
        $ram_percent = 0;
        if (@is_readable('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)\s+kB/i', $meminfo, $matches_total);
            preg_match('/MemAvailable:\s+(\d+)\s+kB/i', $meminfo, $matches_avail);
            preg_match('/MemFree:\s+(\d+)\s+kB/i', $meminfo, $matches_free);

            $total_kb = isset($matches_total[1]) ? intval($matches_total[1]) : 0;
            $avail_kb = isset($matches_avail[1]) ? intval($matches_avail[1]) : (isset($matches_free[1]) ? intval($matches_free[1]) : 0);

            if ($total_kb > 0) {
                $used_kb = $total_kb - $avail_kb;
                $ram_total = round($total_kb / 1024 / 1024, 2); // GB
                $ram_used = round($used_kb / 1024 / 1024, 2);   // GB
                $ram_free = round($avail_kb / 1024 / 1024, 2);   // GB
                $ram_percent = round(($used_kb / $total_kb) * 100);
            }
        } else {
            // Fallback environment
            $mem_used = memory_get_usage(true);
            $ram_used = round($mem_used / 1024 / 1024, 1);
            $ram_total = round(($mem_used * 2) / 1024 / 1024, 1);
            $ram_free = max(0, $ram_total - $ram_used);
            $ram_percent = $ram_total > 0 ? round(($ram_used / $ram_total) * 100) : 25;
        }

        // 3. Disk Usage
        $disk_total = @disk_total_space('/') ?: (50 * 1024 * 1024 * 1024);
        $disk_free = @disk_free_space('/') ?: (35 * 1024 * 1024 * 1024);
        $disk_used = $disk_total - $disk_free;
        $disk_percent = $disk_total > 0 ? round(($disk_used / $disk_total) * 100) : 30;
        $disk_total_gb = round($disk_total / 1024 / 1024 / 1024, 1);
        $disk_free_gb = round($disk_free / 1024 / 1024 / 1024, 1);
        $disk_used_gb = round($disk_used / 1024 / 1024 / 1024, 1);

        // 4. MariaDB / MySQL Active Connections
        $threads_connected = 1;
        $max_connections = 151;
        try {
            $q_threads = $this->db->query("SHOW GLOBAL STATUS LIKE 'Threads_connected'")->row();
            if ($q_threads) $threads_connected = intval($q_threads->Value);
            $q_max = $this->db->query("SHOW VARIABLES LIKE 'max_connections'")->row();
            if ($q_max) $max_connections = intval($q_max->Value);
        } catch (Exception $e) {}

        // 5. Live Active Students Concurrency
        $active_students = $this->db->where('status', 1)
            ->where('selesai IS NULL')
            ->count_all_results('cbt_durasi_siswa');

        // 6. Uptime
        $uptime_str = '-';
        if (@is_readable('/proc/uptime')) {
            $uptime = intval(@file_get_contents('/proc/uptime'));
            $days = floor($uptime / 86400);
            $hours = floor(($uptime % 86400) / 3600);
            $minutes = floor(($uptime % 3600) / 60);
            $uptime_str = ($days > 0 ? "{$days}h " : "") . "{$hours}j {$minutes}m";
        }

        $response = [
            'status' => true,
            'server_time' => date('d-m-Y H:i:s'),
            'cpu' => [
                'load' => $load,
                'cores' => $cpu_count,
                'percent' => $cpu_percent,
            ],
            'ram' => [
                'total' => $ram_total,
                'used' => $ram_used,
                'free' => $ram_free,
                'percent' => $ram_percent,
            ],
            'disk' => [
                'total_gb' => $disk_total_gb,
                'used_gb' => $disk_used_gb,
                'free_gb' => $disk_free_gb,
                'percent' => $disk_percent,
            ],
            'database' => [
                'threads' => $threads_connected,
                'max_connections' => $max_connections,
            ],
            'live_students' => $active_students,
            'uptime' => $uptime_str,
        ];

        // Cek & kirim notifikasi WA otomatis jika CPU/RAM VPS melebihi ambang batas (cooldown 15 menit)
        $this->exambro->maybeAlertServerLoad($cpu_percent, $ram_percent, $active_students);

        $this->output_json($response);
    }

    /**
     * 1-Click Action: Reset login session siswa
     */
    public function reset_login() {
        if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru')) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak']);
            return;
        }

        $id_siswa = $this->input->post('id_siswa', true);
        $id_jadwal = $this->input->post('id_jadwal', true);

        if (!$id_siswa || !$id_jadwal) {
            $this->output_json(['status' => false, 'message' => 'Parameter tidak lengkap']);
            return;
        }

        $this->db->trans_start();

        // 1. Reset pada log_ujian
        $this->db->set('reset', 1);
        $this->db->where('id_siswa', $id_siswa);
        $this->db->where('id_jadwal', $id_jadwal);
        $this->db->update('log_ujian');

        // 2. Reset pada cbt_durasi_siswa
        $this->db->set('reset', 1);
        $this->db->where('id_siswa', $id_siswa);
        $this->db->where('id_jadwal', $id_jadwal);
        $this->db->update('cbt_durasi_siswa');

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->output_json(['status' => false, 'message' => 'Gagal me-reset login siswa']);
        } else {
            $siswa = $this->db->select('nama, nis')->where('id_siswa', $id_siswa)->get('master_siswa')->row();
            $targetLbl = $siswa ? ($siswa->nama . ' (' . $siswa->nis . ')') : ('Siswa #' . $id_siswa);
            $this->exambro->logProctorAudit('RESET_LOGIN', $targetLbl, 'Reset login siswa pada Jadwal #' . $id_jadwal);
            $this->output_json(['status' => true, 'message' => 'Login siswa berhasil di-reset. Siswa dapat login kembali.']);
        }
    }

    /**
     * 1-Click Action: Paksa selesaikan ujian siswa
     */
    public function force_finish() {
        if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru')) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak']);
            return;
        }

        $id_siswa = $this->input->post('id_siswa', true);
        $id_jadwal = $this->input->post('id_jadwal', true);

        if (!$id_siswa || !$id_jadwal) {
            $this->output_json(['status' => false, 'message' => 'Parameter tidak lengkap']);
            return;
        }

        $this->db->trans_start();

        // Catat log
        $this->cbt->saveLog($id_siswa, $id_jadwal, 2, 'Menyelesaikan Ujian (Dipaksa Selesai oleh Pengawas/Admin)');

        // Update status durasi menjadi selesai
        $this->db->set('selesai', date('Y-m-d H:i:s'));
        $this->db->set('status', 2);
        $this->db->set('reset', 3);
        $this->db->where('id_siswa', $id_siswa);
        $this->db->where('id_jadwal', $id_jadwal);
        $this->db->update('cbt_durasi_siswa');

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->output_json(['status' => false, 'message' => 'Gagal menyelesaikan ujian siswa']);
        } else {
            $siswa = $this->db->select('nama, nis')->where('id_siswa', $id_siswa)->get('master_siswa')->row();
            $targetLbl = $siswa ? ($siswa->nama . ' (' . $siswa->nis . ')') : ('Siswa #' . $id_siswa);
            $this->exambro->logProctorAudit('FORCE_FINISH', $targetLbl, 'Paksa selesai ujian siswa pada Jadwal #' . $id_jadwal);
            $this->output_json(['status' => true, 'message' => 'Ujian siswa berhasil diselesaikan']);
        }
    }

    /**
     * Bulk Action: Reset Login atau Paksa Selesai untuk banyak siswa sekaligus
     */
    public function bulk_action() {
        if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru')) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak']);
            return;
        }

        $action = $this->input->post('action', true);
        $ids = $this->input->post('ids', true);
        $id_jadwal = $this->input->post('id_jadwal', true);

        if (empty($ids) || !is_array($ids)) {
            $this->output_json(['status' => false, 'message' => 'Tidak ada siswa yang dipilih']);
            return;
        }

        $this->db->trans_start();

        if ($action === 'reset') {
            $this->db->set('reset', 1);
            $this->db->where_in('id_siswa', $ids);
            $this->db->where('id_jadwal', $id_jadwal);
            $this->db->update('log_ujian');

            $this->db->set('reset', 1);
            $this->db->where_in('id_siswa', $ids);
            $this->db->where('id_jadwal', $id_jadwal);
            $this->db->update('cbt_durasi_siswa');

            $msg = 'Reset login untuk ' . count($ids) . ' siswa berhasil dilakukan.';
        } else if ($action === 'finish') {
            foreach ($ids as $id_s) {
                $this->cbt->saveLog($id_s, $id_jadwal, 2, 'Menyelesaikan Ujian (Dipaksa Selesai Massal oleh Pengawas/Admin)');
            }

            $this->db->set('selesai', date('Y-m-d H:i:s'));
            $this->db->set('status', 2);
            $this->db->set('reset', 3);
            $this->db->where_in('id_siswa', $ids);
            $this->db->where('id_jadwal', $id_jadwal);
            $this->db->update('cbt_durasi_siswa');

            $msg = 'Paksa selesai untuk ' . count($ids) . ' siswa berhasil dilakukan.';
        } else {
            $this->output_json(['status' => false, 'message' => 'Aksi tidak dikenal']);
            return;
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->output_json(['status' => false, 'message' => 'Terjadi kesalahan saat memproses aksi']);
        } else {
            $auditAct = ($action === 'reset') ? 'BULK_RESET' : 'BULK_FINISH';
            $this->exambro->logProctorAudit($auditAct, count($ids) . ' Siswa', $msg . ' (Jadwal #' . $id_jadwal . ')');
            $this->output_json(['status' => true, 'message' => $msg]);
        }
    }

    /**
     * Widget Cek Kesiapan Ujian (Pre-Flight Check) sebelum bel ujian berbunyi
     * GET /cbtlive/preflight_check
     */
    public function preflight_check() {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $today = date('Y-m-d');
        $checks = [];

        // 1. Cek Jadwal Aktif Hari Ini
        $this->db->select('a.id_jadwal, a.id_bank, a.tgl_mulai, a.tgl_selesai, b.bank_kode, d.nama_mapel');
        $this->db->from('cbt_jadwal a');
        $this->db->join('cbt_bank_soal b', 'b.id_bank=a.id_bank', 'left');
        $this->db->join('master_mapel d', 'd.id_mapel=b.bank_mapel_id', 'left');
        if ($tp) $this->db->where('a.id_tp', $tp->id_tp);
        if ($smt) $this->db->where('a.id_smt', $smt->id_smt);
        $this->db->where('a.status', '1');
        $active_jadwals = $this->db->get()->result();

        $today_list = [];
        foreach ($active_jadwals as $j) {
            $mulai = date('Y-m-d', strtotime($j->tgl_mulai));
            $selesai = date('Y-m-d', strtotime($j->tgl_selesai));
            if ($today >= $mulai && $today <= $selesai) {
                $today_list[] = ($j->nama_mapel ?: $j->bank_kode) . ' (' . $j->bank_kode . ')';
            }
        }
        $checks[] = [
            'key' => 'jadwal_today',
            'title' => 'Jadwal Ujian Aktif Hari Ini',
            'status' => count($today_list) > 0 ? 'ok' : 'warn',
            'summary' => count($today_list) > 0
                ? count($today_list) . ' jadwal aktif hari ini (' . count($active_jadwals) . ' total jadwal aktif).'
                : 'Tidak ada jadwal yang jatuh pada tanggal hari ini (' . date('d/m/Y') . ').',
            'details' => !empty($today_list) ? implode(', ', array_slice($today_list, 0, 5)) : 'Periksa tanggal mulai/selesai di menu Jadwal.',
            'action_url' => base_url('cbtjadwal'),
            'action_label' => 'Atur Jadwal',
        ];

        // 2. Cek Kelengkapan Soal pada Bank Soal Aktif
        $empty_banks = [];
        $checked_banks = [];
        foreach ($active_jadwals as $j) {
            if (isset($checked_banks[$j->id_bank])) continue;
            $checked_banks[$j->id_bank] = true;
            $cnt = $this->db->where('bank_id', $j->id_bank)->count_all_results('cbt_soal');
            if ($cnt <= 0) {
                $empty_banks[] = ($j->nama_mapel ?: 'Bank') . ' (' . ($j->bank_kode ?: '#' . $j->id_bank) . ')';
            }
        }
        $checks[] = [
            'key' => 'bank_soal',
            'title' => 'Kelengkapan Isi Bank Soal Aktif',
            'status' => count($empty_banks) === 0 ? 'ok' : 'error',
            'summary' => count($empty_banks) === 0
                ? 'Semua ' . count($checked_banks) . ' bank soal jadwal aktif sudah terisi soal.'
                : count($empty_banks) . ' bank soal masih KOSONG (0 butir soal)!',
            'details' => count($empty_banks) > 0 ? 'Kosong: ' . implode(', ', $empty_banks) : 'Siap dikerjakan siswa.',
            'action_url' => base_url('cbtbanksoal'),
            'action_label' => 'Cek Bank Soal',
        ];

        // 3. Cek Pemetaan Ruang & Sesi Siswa
        $total_siswa = 0;
        $mapped_sesi = 0;
        $mapped_nomor = 0;
        if ($tp && $smt) {
            $total_siswa = $this->db
                ->where('id_tp', $tp->id_tp)
                ->where('id_smt', $smt->id_smt)
                ->count_all_results('kelas_siswa');

            $mapped_sesi = $this->db
                ->from('kelas_siswa ks')
                ->join('cbt_sesi_siswa ss', 'ss.siswa_id=ks.id_siswa AND ss.tp_id=ks.id_tp AND ss.smt_id=ks.id_smt')
                ->where('ks.id_tp', $tp->id_tp)
                ->where('ks.id_smt', $smt->id_smt)
                ->where('ss.ruang_id >', 0)
                ->where('ss.sesi_id >', 0)
                ->count_all_results();

            $mapped_nomor = $this->db
                ->from('kelas_siswa ks')
                ->join('cbt_nomor_peserta np', 'np.id_siswa=ks.id_siswa AND np.id_tp=ks.id_tp')
                ->where('ks.id_tp', $tp->id_tp)
                ->where('ks.id_smt', $smt->id_smt)
                ->where('np.nomor_peserta !=', '')
                ->count_all_results();
        }
        $unmapped_sesi = max(0, $total_siswa - $mapped_sesi);
        $checks[] = [
            'key' => 'ruang_sesi',
            'title' => 'Pemetaan Ruang & Sesi Siswa',
            'status' => ($total_siswa > 0 && $unmapped_sesi === 0) ? 'ok' : ($unmapped_sesi > 0 ? 'error' : 'warn'),
            'summary' => $total_siswa > 0
                ? "{$mapped_sesi} dari {$total_siswa} siswa sudah terpetakan ke ruang & sesi."
                : 'Belum ada siswa terdaftar di kelas pada TP/SMT aktif.',
            'details' => $unmapped_sesi > 0 ? "{$unmapped_sesi} siswa belum mendapat ruang/sesi ujian!" : 'Semua siswa sudah punya ruang & sesi.',
            'action_url' => base_url('cbtsesisiswa'),
            'action_label' => 'Atur Ruang/Sesi',
        ];

        // 4. Cek Nomor Peserta Ujian
        $unmapped_nomor = max(0, $total_siswa - $mapped_nomor);
        $checks[] = [
            'key' => 'nomor_peserta',
            'title' => 'Nomor Peserta Ujian Siswa',
            'status' => ($total_siswa > 0 && $unmapped_nomor === 0) ? 'ok' : 'warn',
            'summary' => "{$mapped_nomor} dari {$total_siswa} siswa memiliki Nomor Peserta.",
            'details' => $unmapped_nomor > 0 ? "{$unmapped_nomor} siswa belum memiliki nomor peserta." : 'Nomor peserta lengkap.',
            'action_url' => base_url('cbtnomorpeserta'),
            'action_label' => 'Atur Nomor Peserta',
        ];

        // 5. Cek Keamanan & Pengaturan Exambro
        $ex = $this->exambro->getSetting();
        $checks[] = [
            'key' => 'exambro_policy',
            'title' => 'Kebijakan Saduci Exambro & Snapshot',
            'status' => !empty($ex->require_exambro) ? 'ok' : 'warn',
            'summary' => 'Wajib Exambro: ' . (!empty($ex->require_exambro) ? 'ON' : 'OFF')
                . ' | Snapshot: ' . (!empty($ex->snapshot_enabled) ? 'ON (' . intval($ex->snapshot_interval_min) . 'm)' : 'OFF')
                . ' | Alarm: ' . (!empty($ex->alarm_enabled) ? 'ON' : 'OFF'),
            'details' => 'Kode Keluar Aktif: ' . $this->exambro->getActiveExitCode() . ' | Min. APK: v' . ($ex->min_version ?? '1.0.0'),
            'action_url' => '#collapse-exambro-settings',
            'action_label' => 'Atur Kontrol',
        ];

        // 6. Cek Folder Writable & Sisa Disk VPS
        $this->exambro->getSnapshotDir();
        $dirsToCheck = [
            'uploads' => FCPATH . 'uploads',
            'exambro_snapshots' => FCPATH . 'uploads/exambro_snapshots',
            'cache' => APPPATH . 'cache',
            'logs' => APPPATH . 'logs',
        ];
        $unwritable = [];
        foreach ($dirsToCheck as $label => $path) {
            if (!is_dir($path) || !is_writable($path)) {
                $unwritable[] = $label;
            }
        }
        $freeGb = @disk_free_space(FCPATH) ? round(@disk_free_space(FCPATH) / (1024 * 1024 * 1024), 2) : 0;
        $diskStatus = 'ok';
        if (!empty($unwritable) || $freeGb < 1) {
            $diskStatus = 'error';
        } elseif ($freeGb < 3) {
            $diskStatus = 'warn';
        }
        $checks[] = [
            'key' => 'storage_perm',
            'title' => 'Izin Folder Upload & Sisa Disk VPS',
            'status' => $diskStatus,
            'summary' => "Sisa Disk VPS: {$freeGb} GB | Folder upload & snapshot: " . (empty($unwritable) ? 'Writable (Siap)' : 'Bermasalah'),
            'details' => !empty($unwritable) ? 'Folder tidak bisa ditulis: ' . implode(', ', $unwritable) : 'Siap menyimpan jawaban & bukti foto ujian.',
            'action_url' => base_url('cbtfoto'),
            'action_label' => 'Cek Bukti Foto',
        ];

        // 7. Cek Koneksi WhatsApp Bot (Fonnte)
        $waOk = false;
        $waSummary = 'Token WA Fonnte belum diatur.';
        if ($this->db->table_exists('cbt_wa_setting')) {
            $waRow = $this->db->get('cbt_wa_setting')->row();
            if ($waRow && !empty($waRow->status) && !empty($waRow->api_token)) {
                $waOk = true;
                $waSummary = 'WhatsApp Bot Fonnte aktif' . (!empty($waRow->target_group) ? ' (Grup terhubung).' : ' (Target grup belum diisi).');
            }
        }
        $checks[] = [
            'key' => 'wa_gateway',
            'title' => 'Integrasi Notifikasi WhatsApp (Fonnte)',
            'status' => $waOk ? 'ok' : 'warn',
            'summary' => $waSummary,
            'details' => !empty($ex->wa_alert_enabled) ? 'Alert pelanggaran 3x & beban server > ' . intval($ex->wa_cpu_threshold ?: 85) . '% aktif.' : 'Alert otomatis WA sedang OFF.',
            'action_url' => base_url('cbtwa'),
            'action_label' => 'Atur WA Bot',
        ];

        $okCount = 0;
        $errCount = 0;
        foreach ($checks as $c) {
            if ($c['status'] === 'ok') $okCount++;
            if ($c['status'] === 'error') $errCount++;
        }

        $this->output_json([
            'status' => true,
            'checked_at' => date('d/m/Y H:i:s'),
            'ok_count' => $okCount,
            'error_count' => $errCount,
            'total_checks' => count($checks),
            'checks' => $checks,
        ]);
    }

    /**
     * Ambil Log Riwayat Aktivitas Pengawas (Audit Trail)
     * GET /cbtlive/get_audit_logs?action=...&limit=50
     */
    public function get_audit_logs() {
        $action = trim((string) $this->input->get('action', true));
        $limit = intval($this->input->get('limit') ?: 50);
        $logs = $this->exambro->getProctorAudits($limit, $action);

        $this->output_json([
            'status' => true,
            'total' => count($logs),
            'logs' => $logs,
        ]);
    }
}

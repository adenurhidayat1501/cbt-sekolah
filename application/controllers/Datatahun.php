<?php
/**
 * Created by IntelliJ IDEA.
 * User: AbangAzmi
 * Date: 12/06/2020
 * Time: 10.27
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Datatahun extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } else if (!$this->ion_auth->is_admin()) {
            show_error('Hanya Administrator yang diberi hak untuk mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }
        $this->load->library(['datatables', 'form_validation']); // Load Library Ignited-Datatables
        $this->load->model('Master_model', 'master');
        $this->load->model('Dashboard_model', 'dashboard');
		$this->load->model('Log_model', 'logging');
        $this->form_validation->set_error_delimiters('', '');
    }

    public function output_json($data, $encode = true)
    {
        if ($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    public function index()
    {
		$user = $this->ion_auth->user()->row();
		$data = [
			'user' => $user,
            'judul'	=> 'Tahun Pelajaran dan Semester',
            'subjudul' => 'Atur Tahun Pelajaran dan Semester',
			'profile'		=> $this->dashboard->getProfileAdmin($user->id),
			'setting'		=> $this->dashboard->getSetting()
        ];

        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        $data['tp'] = $this->dashboard->getTahun();
        $data['tp_active'] = $tp;
        $data['smt'] = $this->dashboard->getSemester();
        $data['smt_active'] = $smt;

        $id_tp = (is_object($tp) && isset($tp->id_tp)) ? $tp->id_tp : '';
        $id_smt = (is_object($smt) && isset($smt->id_smt)) ? $smt->id_smt : '';
        $jml = (!empty($id_tp) && !empty($id_smt)) ? $this->master->getJmlHariEfektif($id_tp.$id_smt) : null;
        $data['jml_hari'] = $jml == null ? '0' : $jml->jml_hari_efektif;

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('master/tahun/data');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function data() {
        $this->output_json($this->dashboard->getDataTahun(), false);
    }

	public function gantiTahun() {
		$aktif = $this->input->post('active', true);
		if (!empty($aktif)) {
            // Non-aktifkan semua tahun pelajaran terlebih dahulu
            $this->db->update('master_tp', ['active' => 0]);
            // Aktifkan tahun pelajaran yang dipilih
            $this->db->where('id_tp', $aktif)->update('master_tp', ['active' => 1]);

            $tp = $this->db->get_where('master_tp', ['id_tp' => $aktif])->row();
            $namaTahun = $tp ? $tp->tahun : $aktif;

            $this->logging->saveLog(4, 'mengganti tahun ajaran aktif ke ' . $namaTahun);
            $data = [
                'status' => true,
                'msg'    => 'Berhasil mengaktifkan Tahun Pelajaran ' . $namaTahun
            ];
        } else {
            $data = [
                'status' => false,
                'msg'    => 'ID Tahun Pelajaran tidak valid'
            ];
        }
		$this->output_json($data);
	}

	public function gantiSemester() {
		$aktif = $this->input->post('active', true);
		if (!empty($aktif)) {
            // Non-aktifkan semua semester terlebih dahulu
            $this->db->update('master_smt', ['active' => 0]);
            // Aktifkan semester yang dipilih
            $this->db->where('id_smt', $aktif)->update('master_smt', ['active' => 1]);

            $smt = $this->db->get_where('master_smt', ['id_smt' => $aktif])->row();
            $namaSmt = $smt ? $smt->smt : $aktif;

            $this->logging->saveLog(4, 'mengganti semester aktif ke ' . $namaSmt);
            $data = [
                'status' => true,
                'msg'    => 'Berhasil mengaktifkan Semester ' . $namaSmt
            ];
        } else {
            $data = [
                'status' => false,
                'msg'    => 'ID Semester tidak valid'
            ];
        }
		$this->output_json($data);
	}

    public function add() {
    	$method = $this->input->post('method', true);
		$tahun = trim($this->input->post('tahun', true) ?? '');

		if (empty($tahun)) {
			$this->output_json(['status' => false, 'message' => 'Tahun pelajaran tidak boleh kosong']);
			return;
		}

        // Normalisasi format (mengubah tanda hubung menjadi garis miring, misal: 2024-2025 -> 2024/2025)
        $tahun = str_replace('-', '/', $tahun);

        if ($method === 'add') {
            // Cek duplikasi tahun
            $exists = $this->db->get_where('master_tp', ['tahun' => $tahun])->row();
            if ($exists) {
                $this->output_json(['status' => false, 'message' => 'Tahun Pelajaran ' . $tahun . ' sudah terdaftar!']);
                return;
            }

            // Jika belum ada tahun aktif sama sekali, jadikan tahun baru ini aktif
            $hasActive = $this->db->get_where('master_tp', ['active' => 1])->num_rows();
			$insert = [
				"tahun"  => $tahun,
				"active" => $hasActive > 0 ? 0 : 1
			];
			$data = $this->master->create('master_tp', $insert);
			$this->logging->saveLog(3, 'menambah tahun pelajaran: ' . $tahun);
            $this->output_json([
                'status'  => (bool)$data,
                'message' => $data ? 'Berhasil menambah Tahun Pelajaran ' . $tahun : 'Gagal menambah Tahun Pelajaran'
            ]);
		} else {
        	$id = $this->input->post('id_tahun', true);
            if (empty($id)) {
                $this->output_json(['status' => false, 'message' => 'ID Tahun Pelajaran tidak valid']);
                return;
            }

            // Cek duplikasi dengan data lain
            $exists = $this->db->where('tahun', $tahun)->where('id_tp !=', $id)->get('master_tp')->row();
            if ($exists) {
                $this->output_json(['status' => false, 'message' => 'Tahun Pelajaran ' . $tahun . ' sudah digunakan oleh data lain!']);
                return;
            }

			$update = array(
				'id_tp'	=> $id,
				'tahun' => $tahun
			);
			$data = $this->master->update('master_tp', $update, 'id_tp', $id);
			$this->logging->saveLog(4, 'mengedit tahun pelajaran: ' . $tahun);
            $this->output_json([
                'status'  => (bool)$data,
                'message' => $data ? 'Berhasil memperbarui Tahun Pelajaran ' . $tahun : 'Gagal memperbarui Tahun Pelajaran'
            ]);
		}
    }

    public function saveHariEfektif() {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        if (!$tp || !$smt) {
            $this->output_json(['status' => false, 'msg' => 'Tahun Pelajaran atau Semester belum diatur']);
            return;
        }
        $input = [
            'id_hari_efektif' => $tp->id_tp.$smt->id_smt,
            'jml_hari_efektif' => $this->input->post('jml_hari', true),
        ];
        $update = $this->db->replace('master_hari_efektif', $input);
        $data['status'] = (bool)$update;
        $this->output_json($data);
    }

	public function hapusTahun() {
		$id = $this->input->post('hapus', true);
        if (empty($id)) {
            $this->output_json(['status' => false, 'msg' => 'ID Tahun Pelajaran tidak valid']);
            return;
        }

        $tp = $this->db->get_where('master_tp', ['id_tp' => $id])->row();
        if (!$tp) {
            $this->output_json(['status' => false, 'msg' => 'Data Tahun Pelajaran tidak ditemukan']);
            return;
        }

        // Keamanan: cegah penghapusan tahun pelajaran yang sedang aktif
        if ($tp->active == 1) {
            $this->output_json([
                'status' => false,
                'msg'    => 'Tahun Pelajaran ' . $tp->tahun . ' sedang AKTIF! Silakan aktifkan tahun pelajaran lain terlebih dahulu sebelum menghapus.'
            ]);
            return;
        }

        // Integritas data: cek apakah ada data kelas atau jadwal ujian yang terikat
        $kelasCount = $this->db->where('id_tp', $id)->count_all_results('master_kelas');
        $jadwalCount = $this->db->where('id_tp', $id)->count_all_results('cbt_jadwal');
        if ($kelasCount > 0 || $jadwalCount > 0) {
            $this->output_json([
                'status' => false,
                'msg'    => "Tahun Pelajaran {$tp->tahun} masih memiliki data terkait ({$kelasCount} rombel kelas, {$jadwalCount} jadwal ujian). Hapus atau arsipkan data tersebut terlebih dahulu."
            ]);
            return;
        }

		if ($this->dashboard->hapus('master_tp', $id, 'id_tp')) {
			$this->logging->saveLog(5, 'menghapus tahun pelajaran: ' . $tp->tahun);
			$data['status'] = true;
            $data['msg']    = 'Berhasil menghapus Tahun Pelajaran ' . $tp->tahun;
		} else {
			$data['status'] = false;
            $data['msg']    = 'Gagal menghapus Tahun Pelajaran ' . $tp->tahun;
		}
		$this->output_json($data);
	}

    public function hapus() {
        $chk = $this->input->post('checked', true);
        if (!$chk || !is_array($chk)) {
            $this->output_json(['status' => false, 'msg' => 'Tidak ada data yang dipilih']);
            return;
        }

        // Cegah penghapusan tahun aktif pada bulk delete
        $activeTp = $this->db->get_where('master_tp', ['active' => 1])->row();
        if ($activeTp && in_array($activeTp->id_tp, $chk)) {
            $this->output_json([
                'status' => false,
                'msg'    => 'Tahun Pelajaran yang sedang aktif tidak boleh dihapus!'
            ]);
            return;
        }

        if ($this->dashboard->hapus('master_tp', $chk, 'id_tp')) {
            $this->logging->saveLog(5, 'menghapus batch tahun pelajaran');
            $this->output_json(['status' => true, 'total' => count($chk), 'msg' => 'Berhasil menghapus data']);
        } else {
            $this->output_json(['status' => false, 'msg' => 'Gagal menghapus data']);
        }
    }
}

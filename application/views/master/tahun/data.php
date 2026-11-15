<?php
/**
 * Created by IntelliJ IDEA.
 * User: AbangAzmi
 * Date: 12/06/2020
 * Time: 10.26
 */
?>

<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <!--
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">DataTables</li>
                    </ol>
                </div>
                -->
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <div class="card-title"><?= $subjudul ?></div>
                    <div class="card-tools">
                        <button type="button" onclick="reload_ajax()" class="btn btn-sm btn-default">
                            <i class="fa fa-sync"></i> <span class="d-none d-sm-inline-block ml-1">Reload</span>
                        </button>
                        <button type="button" data-from="add" data-toggle="modal" data-target="#createTahunModal"
                                class="btn btn-sm bg-gradient-primary"><i
                                    class="fas fa-plus"></i><span class="d-none d-sm-inline-block ml-1">Tambah Tahun Pelajaran</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-7 mb-4 table-responsive">
                            <label class="font-weight-bold">Tahun Pelajaran</label>
                            <?= form_open('', array('id' => 'edittp')) ?>
                            <table id="tahun" class="table table-sm table-striped table-bordered table-hover">
                                <thead class="bg-light">
                                <tr>
                                    <th class="d-none">id</th>
                                    <th height="40" class="text-center p-0 align-middle" style="width: 45px;">No.</th>
                                    <th class="text-center p-0 align-middle">Tahun Pelajaran</th>
                                    <th class="text-center p-0 align-middle" style="width: 120px;">Status</th>
                                    <th class="text-center p-0 align-middle" style="width: 130px;">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($tp)) : ?>
                                    <?php foreach ($tp as $key => $value) : ?>
                                        <tr>
                                            <td class="d-none"><?= $value->id_tp ?></td>
                                            <td class="text-center align-middle"><?= ($key + 1) ?></td>
                                            <td class="text-center align-middle font-weight-bold"><?= $value->tahun ?></td>
                                            <td class="text-center align-middle">
                                                <?php if ($value->active) : ?>
                                                    <span class="badge badge-success px-2 py-1"><i class="fa fa-check mr-1"></i>AKTIF</span>
                                                <?php else : ?>
                                                    <button type="button" data-id="<?= $value->id_tp ?>"
                                                            class="btn btn-xs btn-outline-primary btn-aktif font-weight-bold">
                                                        <i class="fa fa-toggle-on mr-1"></i>AKTIFKAN
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" data-id="<?= $value->id_tp ?>"
                                                            data-tahun="<?= $value->tahun ?>" data-from="edit"
                                                            data-toggle="modal" data-target="#createTahunModal"
                                                            class="btn btn-xs btn-warning btn-edit text-white" title="Edit Tahun Pelajaran">
                                                        <i class="fa fa-pencil-alt"></i> Edit
                                                    </button>
                                                    <?php if ($value->active) : ?>
                                                        <button type="button" class="btn btn-xs btn-secondary" disabled title="Tahun aktif tidak dapat dihapus">
                                                            <i class="fa fa-lock"></i> Hapus
                                                        </button>
                                                    <?php else : ?>
                                                        <button type="button" data-id="<?= $value->id_tp ?>"
                                                                data-tahun="<?= $value->tahun ?>"
                                                                class="btn btn-xs btn-danger btn-hapus" title="Hapus Tahun Pelajaran">
                                                            <i class="fa fa-trash"></i> Hapus
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada data Tahun Pelajaran. Klik tombol "Tambah Tahun Pelajaran" di atas.</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                            <?= form_close() ?>
                        </div>
                        <div class="col-md-5">
                            <label class="font-weight-bold">Semester</label>
                            <?= form_open('', array('id' => 'editsmt')) ?>
                            <table id="semester" class="table table-sm table-striped table-bordered table-hover">
                                <thead class="bg-light">
                                <tr>
                                    <th class="d-none">id</th>
                                    <th height="40" class="text-center p-0 align-middle" style="width: 45px;">No.</th>
                                    <th class="text-center p-0 align-middle">Semester</th>
                                    <th class="text-center p-0 align-middle" style="width: 120px;">Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($smt)) : ?>
                                    <?php foreach ($smt as $key => $value) : ?>
                                        <tr>
                                            <td class="d-none"><?= $value->id_smt ?></td>
                                            <td class="text-center align-middle"><?= ($key + 1) ?></td>
                                            <td class="text-center align-middle font-weight-bold"><?= isset($value->nama_smt) ? $value->nama_smt : 'Semester ' . $value->smt ?></td>
                                            <td class="text-center align-middle">
                                                <?php if ($value->active) : ?>
                                                    <span class="badge badge-success px-2 py-1"><i class="fa fa-check mr-1"></i>AKTIF</span>
                                                <?php else : ?>
                                                    <button type="button" data-id="<?= $value->id_smt ?>"
                                                            class="btn btn-xs btn-outline-primary btn-aktif-smt font-weight-bold">
                                                        <i class="fa fa-toggle-on mr-1"></i>AKTIFKAN
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                            <?= form_close() ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?= form_open('datatahun/add', array('id' => 'create')) ?>
<div class="modal fade" id="createTahunModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="createModalLabel">Tambah Tahun Pelajaran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group row">
                    <label class="col-md-3 col-form-label font-weight-bold">Tahun*</label>
                    <div class="col-md-9">
                        <input type="text" id="createtahun" name="tahun" class="form-control" placeholder="Contoh: 2024/2025" required>
                        <small class="text-muted">Gunakan format TAHUN/TAHUN (misal: 2024/2025 atau 2025/2026)</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="editIdTahun" name="id_tahun" value="">
                <input type="hidden" id="method" name="method" value="add">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save mr-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<script>
    $(document).ready(function () {
        // Aktifkan Tahun Pelajaran
        $("#tahun").on("click", ".btn-aktif", function () {
            let id = $(this).data("id");
            swal.fire({
                title: "Mengaktifkan Tahun...",
                text: "Silakan tunggu sejenak",
                allowEscapeKey: false,
                allowOutsideClick: false,
                onOpen: () => {
                    swal.showLoading();
                }
            });
            $.ajax({
                url: base_url + "datatahun/gantitahun",
                data: $('#edittp').serialize() + "&active=" + id,
                type: "POST",
                dataType: "JSON",
                success: function (response) {
                    var isSuccess = response && response.status;
                    swal.fire({
                        title: isSuccess ? "Berhasil" : "Gagal",
                        text: response.msg || (isSuccess ? "Tahun Pelajaran berhasil diaktifkan" : "Gagal mengaktifkan tahun pelajaran"),
                        icon: isSuccess ? "success" : "error"
                    }).then(() => {
                        if (isSuccess) {
                            window.location.reload();
                        }
                    });
                }, 
                error: function (xhr, status, error) {
                    swal.fire({
                        title: "Error",
                        text: "Terjadi kesalahan pada server saat mengaktifkan tahun pelajaran",
                        icon: "error"
                    });
                }
            });
        });

        // Aktifkan Semester
        $("#semester").on("click", ".btn-aktif-smt", function () {
            let id = $(this).data("id");
            swal.fire({
                title: "Mengaktifkan Semester...",
                text: "Silakan tunggu sejenak",
                allowEscapeKey: false,
                allowOutsideClick: false,
                onOpen: () => {
                    swal.showLoading();
                }
            });
            $.ajax({
                url: base_url + "datatahun/gantisemester",
                data: $('#editsmt').serialize() + "&active=" + id,
                type: "POST",
                dataType: "JSON",
                success: function (response) {
                    var isSuccess = response && response.status;
                    swal.fire({
                        title: isSuccess ? "Berhasil" : "Gagal",
                        text: response.msg || (isSuccess ? "Semester berhasil diaktifkan" : "Gagal mengaktifkan semester"),
                        icon: isSuccess ? "success" : "error"
                    }).then(() => {
                        if (isSuccess) {
                            window.location.reload();
                        }
                    });
                }, 
                error: function (xhr, status, error) {
                    swal.fire({
                        title: "Error",
                        text: "Terjadi kesalahan pada server saat mengaktifkan semester",
                        icon: "error"
                    });
                }
            });
        });

        // Modal Tambah / Edit Tahun
        $('#createTahunModal').on('show.bs.modal', function (e) {
            var button = $(e.relatedTarget);
            var method = button.data('from');
            $('#method').val(method);

            if (method === 'edit') {
                $('#createModalLabel').text('Edit Tahun Pelajaran');
                var id = button.data('id');
                var tahun = button.data('tahun');

                $('#editIdTahun').val(id);
                $('#createtahun').val(tahun);
            } else {
                $('#createModalLabel').text('Tambah Tahun Pelajaran');
                $('#editIdTahun').val('');
                $('#createtahun').val('');
            }
        });

        // Hapus Tahun Pelajaran
        $("#tahun").on("click", ".btn-hapus", function () {
            let id = $(this).data("id");
            let namaTahun = $(this).data("tahun") || "";
            swal.fire({
                title: 'Hapus Tahun Pelajaran?',
                text: 'Anda yakin akan menghapus Tahun Pelajaran ' + namaTahun + '? Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.value) {
                    swal.fire({
                        title: "Menghapus...",
                        text: "Silakan tunggu",
                        allowEscapeKey: false,
                        allowOutsideClick: false,
                        onOpen: () => {
                            swal.showLoading();
                        }
                    });
                    $.ajax({
                        url: base_url + "datatahun/hapustahun",
                        data: $('#edittp').serialize() + "&hapus=" + id,
                        type: "POST",
                        dataType: "JSON",
                        success: function (response) {
                            var isSuccess = response && response.status;
                            swal.fire({
                                title: isSuccess ? "Berhasil" : "Gagal",
                                text: response.msg || (isSuccess ? "Berhasil menghapus tahun pelajaran" : "Gagal menghapus tahun pelajaran"),
                                icon: isSuccess ? "success" : "error"
                            }).then(() => {
                                if (isSuccess) {
                                    window.location.reload();
                                }
                            });
                        }, 
                        error: function (xhr, status, error) {
                            swal.fire({
                                title: "Error",
                                text: "Terjadi kesalahan saat menghapus tahun pelajaran",
                                icon: "error"
                            });
                        }
                    });
                }
            });
        });

        // Submit Form Tambah / Edit
        $('#create').on('submit', function (e) {
            e.preventDefault();
            var tahunVal = $.trim($('#createtahun').val());
            if (!tahunVal) {
                showDangerToast('Tahun Pelajaran tidak boleh kosong');
                return false;
            }

            $.ajax({
                url: base_url + "datatahun/add",
                type: "POST",
                dataType: "JSON",
                data: $(this).serialize(),
                success: function (data) {
                    if (data && data.status) {
                        $('#createTahunModal').modal('hide');
                        showSuccessToast(data.message || 'Berhasil menyimpan Tahun Pelajaran');
                        setTimeout(function () {
                            window.location.reload();
                        }, 600);
                    } else {
                        showDangerToast(data && data.message ? data.message : 'Gagal menyimpan Tahun Pelajaran');
                    }
                }, 
                error: function (xhr, status, error) {
                    showDangerToast('Terjadi kesalahan koneksi ke server');
                }
            });
            return false;
        });
    });
</script>

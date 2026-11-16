<?php

class Database {

    function create_database($data) {
        $port = isset($data['port']) && !empty($data['port']) ? (int)$data['port'] : 3306;
        $mysqli = new mysqli($data['hostname'], $data['username'], $data['password'], '', $port);
        if (mysqli_connect_errno()) return false;

        $mysqli->query("CREATE DATABASE IF NOT EXISTS `" . $mysqli->real_escape_string($data['database']) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        while ($mysqli->more_results() && $mysqli->next_result());
        $mysqli->close();
        return true;
    }

    function create_tables($data) {
        $port = isset($data['port']) && !empty($data['port']) ? (int)$data['port'] : 3306;
        $mysqli = new mysqli($data['hostname'], $data['username'], $data['password'], $data['database'], $port);
        if (mysqli_connect_errno()) return false;

        $check = $mysqli->query("SHOW TABLES LIKE 'users'");
        if ($check && $check->num_rows > 0) {
            $check->free();
            $mysqli->close();
            return true;
        } else {
            $query = file_get_contents('../assets/app/db/master.sql');
            $mysqli->multi_query($query);
            while ($mysqli->more_results() && $mysqli->next_result());

            // Initialize default admin user and school settings if newly created
            $admin_hash = password_hash('admin', PASSWORD_BCRYPT);
            $mysqli->query("INSERT INTO `setting` (`id_setting`, `kode_sekolah`, `sekolah`, `nama_aplikasi`, `alamat`, `desa`, `kecamatan`, `kota`, `provinsi`, `logo_kanan`, `logo_kiri`, `versi`) VALUES (1, 'SADUCI', 'SMP NEGERI SATU ATAP 2 CIPATUJAH', 'SADUCI CBT', 'Cipatujah', 'Cipatujah', 'Cipatujah', 'Kab. Tasikmalaya', 'Jawa Barat', 'assets/img/saduci_logo.png', 'assets/img/saduci_logo.png', '1.5.3') ON DUPLICATE KEY UPDATE `nama_aplikasi` = 'SADUCI CBT', `sekolah` = 'SMP NEGERI SATU ATAP 2 CIPATUJAH';");
            $mysqli->query("INSERT INTO `users` (`id`, `ip_address`, `username`, `password`, `email`, `created_on`, `active`, `first_name`, `last_name`) VALUES (1, '127.0.0.1', 'admin', '{$admin_hash}', 'admin@saduci.sch.id', UNIX_TIMESTAMP(), 1, 'Administrator', 'SADUCI') ON DUPLICATE KEY UPDATE `id` = 1;");
            $mysqli->query("INSERT INTO `users_groups` (`id`, `user_id`, `group_id`) VALUES (1, 1, 1) ON DUPLICATE KEY UPDATE `group_id` = 1;");
            $mysqli->query("ALTER TABLE `master_tp` MODIFY `active` int(11) NOT NULL DEFAULT 0;");
            $mysqli->query("ALTER TABLE `master_kelas` MODIFY `jurusan_id` int(11) NOT NULL DEFAULT 0;");

            $mysqli->close();
            return true;
        }
    }
}

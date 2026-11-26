<?php

class Core {
    function checkEmpty($data) {
        if (!empty($data['hostname']) && !empty($data['username']) && !empty($data['database'])) {
            return true;
        } else {
            return false;
        }
    }

    function show_message($type, $message) {
        return $message;
    }

    function getAllData($data) {
        return $data;
    }

    function write_db_config($data) {
        $template_path = '../assets/app/db/database.php';
        $output_path = '../application/config/database.php';
        $env_path = '../.env';

        $database_file = file_get_contents($template_path);

        $new = str_replace("%HOSTNAME%", $data['hostname'], $database_file);
        $new = str_replace("%USERNAME%", $data['username'], $new);
        $new = str_replace("%PASSWORD%", $data['password'], $new);
        $new = str_replace("%DATABASE%", $data['database'], $new);

        $handle = fopen($output_path, 'w+');
        @chmod($output_path, 0777);

        if (is_writable(dirname($output_path))) {
            $written = fwrite($handle, $new);
            fclose($handle);

            // Also write or update .env for seamless database access
            $env_content = "CI_ENV=production\nDB_HOST={$data['hostname']}\nDB_USER={$data['username']}\nDB_PASS={$data['password']}\nDB_NAME={$data['database']}\n";
            @file_put_contents($env_path, $env_content);
            @chmod($env_path, 0644);

            return $written !== false;
        } else {
            return false;
        }
    }

    function checkFile() {
        $output_path = '../application/config/database.php';

        if (file_exists($output_path)) {
            return true;
        } else {
            return false;
        }
    }
}

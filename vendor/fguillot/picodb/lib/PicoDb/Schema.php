<?php

namespace PicoDb;

use PDOException;

class Schema
{
    protected $db = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function check($last_version = 1)
    {
        $current_version = $this->db->getConnection()->getSchemaVersion();

        if ($current_version < $last_version) {
            return $this->migrateTo($current_version, $last_version);
        }

        return true;
    }

    public function migrateTo($current_version, $next_version)
    {
        try {

            $this->db->startTransaction();

            for ($i = $current_version + 1; $i <= $next_version; $i++) {

                $function_name = '\Schema\version_'.$i;

                if (function_exists($function_name)) {
                    call_user_func($function_name, $this->db->getConnection());
                    $this->db->getConnection()->setSchemaVersion($i);
                }
            }

            $this->db->closeTransaction();
        }
        catch (PDOException $e) {
            $this->db->setLogMessage($function_name.' => '.$e->getMessage());
            $this->db->cancelTransaction();
            return false;
        }

        return true;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: Double-jin
 * Date: 2019/6/19
 * Email: 605932013@qq.com
 */

namespace App\Model;


class UserModel extends Base
{
    public $tableName = "user";

    public function getUserByUsername($username) {

        if(empty($username)) {
            return [];
        }

        $this->db->where ("username", $username);
        $result = $this->db->getOne($this->tableName);
        return $result ?? [];
    }


    public function insertUser($insert) {

        $result = $this->db->insert($this->tableName,$insert);
        return $result ? $this->db->getInsertId() : null;
    }

}
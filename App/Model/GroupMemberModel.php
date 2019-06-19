<?php
/**
 * Created by PhpStorm.
 * User: Double-jin
 * Date: 2019/6/19
 * Email: 605932013@qq.com
 */

namespace App\Model;

class GroupMemberModel extends Base
{
    public $tableName = "group_member";

    public function insertGroupMember($insert) {
        $result = $this->db->insert($this->tableName,$insert);
        return $result ? $this->db->getInsertId() : null;
    }

}
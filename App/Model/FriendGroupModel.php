<?php
/**
 * Created by PhpStorm.
 * User: Double-jin
 * Date: 2019/6/19
 * Email: 605932013@qq.com
 */

namespace App\Model;

class FriendGroupModel extends Base
{
    public $tableName = "friend_group";

    public function insertFriendGroup($insert)
    {
        return $this->data($insert)->save();
    }

}

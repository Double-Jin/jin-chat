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

    public function getUserByUsername($username)
    {
        $result = $this->where("username", $username)->get();
        return $result ? $result->toArray() : [];
    }


    public function insertUser($insert)
    {
        return $this->data($insert)->save();
    }

}

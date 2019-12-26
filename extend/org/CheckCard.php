<?php

namespace org;

// 计算身份证校验码，根据国家标准GB 11643-1999
class CheckCard
{
    //18位身份证校验码有效性检查
    public function idcard_checksum($idcard)
    {
        if(strlen($idcard) == 15) {
            $idcard = $this->idcard_15to18($idcard);
        } else if(strlen($idcard) != 18) {
            return false;
        }

        $aCity = array(11 => "北京", 12 => "天津", 13 => "河北", 14 => "山西", 15 => "内蒙古",
            21 => "辽宁", 22 => "吉林", 23 => "黑龙江",
            31 => "上海", 32 => "江苏", 33 => "浙江", 34 => "安徽", 35 => "福建", 36 => "江西", 37 => "山东",
            41 => "河南", 42 => "湖北", 43 => "湖南", 44 => "广东", 45 => "广西", 46 => "海南",
            50 => "重庆", 51 => "四川", 52 => "贵州", 53 => "云南", 54 => "西藏",
            61 => "陕西", 62 => "甘肃", 63 => "青海", 64 => "宁夏", 65 => "新疆",
            71 => "台湾", 81 => "香港", 82 => "澳门",
            91 => "国外");
        //非法地区
        if(!array_key_exists(substr($idcard, 0, 2), $aCity)) {
            return false;
        }
        //验证生日
        if(!checkdate(substr($idcard, 10, 2), substr($idcard, 12, 2), substr($idcard, 6, 4))) {
            return false;
        }
        $idcard_base = substr($idcard, 0, 17);

        $validStr = $this->idcard_verify_number($idcard_base);
        if($validStr != strtoupper(substr($idcard, 17, 1))) {
            return false;
        } else {
            return true;
        }
    }

    private function idcard_verify_number($idcard_base)
    {
        if(strlen($idcard_base) != 17) {
            return false;
        }
        // 加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        // 校验码对应值
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');

        $checksum = 0;
        for($i = 0; $i < strlen($idcard_base); $i++) {
            $checksum += substr($idcard_base, $i, 1) * $factor[$i];
        }

        $mod = strtoupper($checksum % 11);
        $verify_number = $verify_number_list[$mod];

        return $verify_number;
    }

    // 将15位身份证升级到18位
    private function idcard_15to18($idcard)
    {
        if(strlen($idcard) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if(array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false) {
                $idcard = substr($idcard, 0, 6).'18'.substr($idcard, 6, 9);
            } else {
                $idcard = substr($idcard, 0, 6).'19'.substr($idcard, 6, 9);
            }
        }
        $newStr = $this->idcard_verify_number($idcard);
        $idcard = $idcard.$newStr;

        return $idcard;
    }

    //身份证生成
    public function Get_ID()
    {
        //$content = '';
        //身份证起止年月 eg：1990年12月31日 mktime(0,0,0,12,31,1990)
        $Year_start = mktime(0, 0, 0, 1, 1, 1950);
        $Year_end = mktime(0, 0, 0, 12, 31, 1992);
        //全国区域代码 共3131
        $Region = require('NationalCode.php');
        $seed = mt_rand(0, 3130); //total of region code
        $Birth = mt_rand($Year_start, $Year_end);
        $Birth_format = date('Ymd', $Birth);
        $suffix_a = mt_rand(0, 9);
        $suffix_b = mt_rand(0, 9);
        $suffix_c = mt_rand(0, 9); //male or female
        // if (!$base or mb_strlen($base)<>6)
        $base = $Region[$seed].$Birth_format.$suffix_a.$suffix_b.$suffix_c;
        if(strlen($base) != 17) {
            die('Invalid Length');
        }
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        $sums = 0;
        for($i = 0; $i < 17; $i++) {

            $sums += substr($base, $i, 1) * $factor[$i];
        }

        $mods = $sums % 11; //10X98765432

        switch($mods) {
            case 0:
                return $base.'1';
                break;
            case 1:
                return $base.'0';
                break;
            case 2:
                return $base.'x';
                break;
            case 3:
                return $base.'9';
                break;
            case 4:
                return $base.'8';
                break;
            case 5:
                return $base.'7';
                break;
            case 6:
                return $base.'6';
                break;
            case 7:
                return $base.'5';
                break;
            case 8:
                return $base.'4';
                break;
            case 9:
                return $base.'3';
                break;
            case 10:
                return $base.'2';
                break;
        }
    }
}
?>
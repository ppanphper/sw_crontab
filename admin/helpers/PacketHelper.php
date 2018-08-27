<?php

namespace app\helpers;

use app\config\Constants;

class PacketHelper
{

    public static function packFormat($msg = "OK", $code = Constants::STATUS_CODE_SUCCESS, $data = [])
    {
        $pack = array(
            "code" => $code,
            "msg"  => $msg,
            "data" => $data,
        );

        return $pack;
    }

    public static function packEncode($data)
    {
        $sendStr = serialize($data);

        if (Constants::SW_DATASIGEN_FLAG == true) {
            $crc32 = intval(sprintf('%u', crc32($sendStr . Constants::SW_DATASIGEN_SALT)));
            $signedCode = pack('N', $crc32);
            $sendStr = pack('N', strlen($sendStr) + 4) . $signedCode . $sendStr;
        } else {
            $sendStr = pack('N', strlen($sendStr)) . $sendStr;
        }

        return $sendStr;
    }

    public static function packDecode($str)
    {
        $header = substr($str, 0, 4);
        $len = unpack("Nlen", $header);
        $len = $len["len"];

        if (Constants::SW_DATASIGEN_FLAG == true) {

            $signedCode = substr($str, 4, 4);
            $result = substr($str, 8);

            //check signed
            $crc32 = intval(sprintf('%u', crc32($result . Constants::SW_DATASIGEN_SALT)));
            if (pack("N", $crc32) != $signedCode) {
                return self::packFormat("Signed check error!", Constants::STATUS_CODE_SIGNATURE_ERROR);
            }

            $len = $len - 4;

        } else {
            $result = substr($str, 4);
        }
        if ($len != strlen($result)) {
            //结果长度不对
            log_message('warning', 'packDecode解包时, 结果长度错误');

            return self::packFormat("packet length invalid 包长度非法", Constants::STATUS_CODE_RECEIVE_PACKET_LENGTH_WRONG);
        }
        $originalValue = $result;
        $result = unserialize($result);
        if ($result === false) {
            log_message('Warning', ' 反序列化失败 = ' . var_export($originalValue, true));
        }
        return self::packFormat("OK", Constants::STATUS_CODE_SUCCESS, $result);
    }
}
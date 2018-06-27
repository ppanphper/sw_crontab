<?php

namespace app\helpers;

/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/5/7
 * Time: 下午5:12
 */
class StringHelper
{

    /**
     * 超过长度截取中间字符替换为$etc
     *
     * @param $string
     * @param int $limitLen
     * @param string $charset
     * @param bool $containerEtcLen 是否包含etc长度
     * @param string $etc
     *
     * @return string
     */
    public static function cutSubstring($string, $limitLen = 100, $containerEtcLen = false, $etc = '...', $charset = 'utf-8')
    {
        $strLen = mb_strlen($string, $charset);
        $etcLen = mb_strlen($etc, $charset);
        $boolean = $strLen > ($containerEtcLen ? ($limitLen + $etcLen) : $limitLen);
        // 如果超出了限制长度，从字符串中间截取一定的内容，满足长度限制
        if ($boolean) {
            // 计算出截取的开始位置
            $leftEnd = floor($limitLen / 2);

            // 起始位置大于等于0，符合截取条件
            if ($leftEnd >= 0) {
                $exceedLen = 0;
                if ($containerEtcLen) {
                    $leftEnd -= floor($etcLen / 2);
                    $exceedLen += $etcLen;
                }
                // 需要截取的长度右边开始位置
                $exceedLen += $strLen - $limitLen + $leftEnd;
                $stringLeft = mb_substr($string, 0, $leftEnd, $charset);
                $stringRight = mb_substr($string, $exceedLen, $strLen, $charset);
                $string = $stringLeft . $etc . $stringRight;
            }
        }
        return $string;
    }

    /**
     * 字符串截取
     *
     * @param string $string
     * @param int $limit
     * @param string $etc
     * @param string $charset
     *
     * @return string
     */
    public static function cutSub($string, $limit = 100, $etc = '...', $charset = 'utf-8')
    {
        if (mb_strlen($string, $charset) > $limit) {
            $string = mb_substr($string, 0, $limit, $charset) . $etc;
        }
        return $string;
    }

    /**
     * 判断数据是否是序列化字符串
     *
     * @param string $data
     *
     * @return bool
     */
    public static function is_serialized($data)
    {
        $data = trim($data);
        if ('N;' == $data)
            return true;
        if (!preg_match('/^([adObis]):/', $data, $badions))
            return false;
        switch ($badions[1]) {
            case 'a' :
            case 'O' :
            case 's' :
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data))
                    return true;
                break;
        }
        return false;
    }
}
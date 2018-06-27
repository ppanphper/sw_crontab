<?php
namespace app\widgets;

/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/1/18
 * Time: 下午2:03
 */

class ActiveField extends \yii\widgets\ActiveField {
	public $template = '<div class="col-sm-2">{label}</div><div class="col-sm-10">{input}{hint}{error}</div>';
}
<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/5/28
 * Time: 上午9:52
 */

use app\helpers\TimeHelper;
use app\models\Agents;
?>
<style type="text/css">
    body {
        color: #333;
        font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif;
        font-size: 12px;
    }

    table {
        margin-top: 10px;
        border-top: #B0CBE2 solid 2px;
        border-bottom: #B0CBE2 solid 1px;
        border-collapse: collapse;
    }

    td {
        font-size: 12px;
        line-height: 20px;
        text-align: center;
    }

    tr th {
        font-size: 12px;
        line-height: 22px;
        height: 26px;
        text-align: center;
    }

    th {
        text-align: left;
        border-bottom: #B0CBE2 solid 1px;
        background-color: #D5E7F3;
    }

    td, th {
        padding: 5px;
        border-left: #B0CBE2 solid 1px;
        border-right: #B0CBE2 solid 1px;
    }

    td {
        border-bottom: #B0CBE2 solid 1px;
        background-position: bottom;
        background-repeat: repeat-x;

    }

    img.chart {
        border: 0;
        margin-top: 10px;
        width: 400px;
    }

    p {
        margin: 0
    }

    #report_title {
        font-size: 20px;
        font-family: "黑体";
        padding-top: 20px;
    }

    div.title {
        font-size: 18px;
        font-family: "黑体";
        padding: 12px 0 0 0;
        width: 920px;
        margin: 15px 0 0 10px;
        text-align: left;
    }

    div.sub_title {
        font-size: 16px;
        font-family: "黑体";
        padding-top: 12px;
        width: 900px;
        margin: 0 0 0 35px;
        text-align: left;
    }

    td.fail {
        margin: 0;
        padding: 0;
        width: 50%;
    }

    td.fail table.tb_fail {
        margin: 0 auto;
        width: 90%;
    }

    td.fail table.tb_fail tr td {
        width: 25%
    }

    td.fail table.tb_fail tr td.title {
        width: 12%
    }

    td.pub_img {
        width: 80px;
        padding: 0;
    }

    td.pub_img img {
        margin: 0 auto;
    }

    table.trend tr td {
        width: 33.3%
    }

    table.delay tr td {
        width: 20%
    }

    table.trend, table.delay {
        margin-top: 0
    }

    table.tb_qualitiy td {
        width: 33.3%
    }

    table.tb_docnum td {
        width: 20%
    }

    .news-time {
        background-color: #638ee6;
        border-radius: 2px;
        color: #fff;
        display: inline-block;
        height: 40px;
        margin-top: 5px;
        padding: 5px 0;
        text-align: center;
        width: 86px;
    }

    .news-time .month {
        display: block;
        font-size: 10px;
    }

    .news-time .date {
        display: block;
        font-size: 18px;
        font-weight: bold;
    }

    em {
        color: #FF0000;
        font-weight: bold
    }
</style>
<table class="table table-hover" id="use-rate-table">
    <thead>
    <tr>
        <th><?= Yii::t('app', 'Agents');?></th>
        <th><?= Yii::t('app', 'Final heartbeat time');?></th>
        <th><?= Yii::t('app', 'The offline time');?></th>
        <th><?= Yii::t('app', 'Agent Status');?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($data as $item) {
        ?>
        <tr>
            <td><?= $item['ip'].':'.$item['port'] ?></td>
            <td><?= $item['last_report_time'] ? Yii::$app->formatter->asDatetime($item['last_report_time']) : ''; ?></td>
            <td><?= TimeHelper::msTimeFormat($item['offlineTime']) ?></td>
            <td><?= Yii::t('app', ($item['agent_status'] == Agents::AGENT_STATUS_OFFLINE ? 'Offline' : 'No heartbeat, but the nodes are normal')); ?></td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>
<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/5/17
 * Time: 下午4:38
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <title><?= $title ?></title>
</head>
<body>
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
        line-height: 20px;
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
        <th colspan="2" style="height:26px;text-align:center;line-height:22px;"><?= $title ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($data as $field => $val) {
        ?>
        <tr>
            <td><?= $field ?></td>
            <td><?= $val ?></td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>
</body>
</html>

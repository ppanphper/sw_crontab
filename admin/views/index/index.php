<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2017/12/18
 * Time: 下午4:41
 */
$this->title = Yii::t('app', Yii::$app->name);
?>
<div class="site-index">

    <div class="">
        <h3>更新公告</h3>
        <div>
            <div><h4>2019-05-12</h4></div>
            <div>修复</div>
            <ul>
                <li>兼容linux shell脚本语法</li>
                <li>修复MacOS无法启动agent的问题</li>
                <li>修复yii2-admin里的Configs类初始化记录error日志的问题</li>
                <li>修复缺少agent vendor/envms/fluentpdo组件的无法启动agent问题</li>
                <li>修复其他一些问题</li>
            </ul>
        </div>
        <div>
            <div><h4>2019-01-22</h4></div>
            <div>新增</div>
            <ul>
                <li>定时任务支持按运行Id生成日志文件并记录脚本输出内容，日志路径存放在agent节点下的log_path/date(Ymd)/定时任务Id/运行Id.log</li>
            </ul>
        </div>
        <div>
            <div><h4>2018-07-13</h4></div>
            <div>新增</div>
            <ul>
                <li>可以在后台<a href="/agent/index">节点列表</a>查看节点状态(正常、离线、没有心跳但节点正常)及最后心跳时间</li>
                <li>在定时任务列表查询单个节点，左侧有多出一栏展示节点的最近一次上报的信息(任务数、CPU、内存等信息)</li>
                <li>支持重试及重试间隔</li>
                <li>任务增加描述字段</li>
                <li>支持任务"不在这些节点上运行"</li>
                <li>支持任务执行超时强杀</li>
                <li>日志整合，多条日志合并成一条</li>
                <li>节点、任务、分类的删除功能都变更为逻辑删除</li>
            </ul>
            <div>修复</div>
            <ul>
                <li>查询单个节点下的任务列表不正确的问题</li>
                <li>查询任务负责人不正确的问题</li>
            </ul>
        </div>
        <div>
            <div><h4>2018-06-26</h4></div>
            <ul>
                <li>新增拷贝定时任务快速新增功能</li>
            </ul>
        </div>
    </div>
</div>

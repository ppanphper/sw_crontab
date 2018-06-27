/**
 * Created by liuzhiming on 16-10-26.
 */
var Crontab = {
    ruleObj:null,

    init: function (ruleObj) {
        this.ruleObj = ruleObj;
        var rule = ruleObj.val();
        rule = explode(" ", rule);
        if (rule.length < 5) return;
        var keyMaps = ['v_min', 'v_hour', 'v_day', 'v_mon', 'v_week'];
        if(rule.length === 6) {
            keyMaps.unshift('v_second');
        }
        for(var i=0; i<rule.length; i++) {
            this.cron_parse(rule[i], keyMaps[i]);
        }
        // //解析秒
        // this.cron_parse(rule[0], "v_second");
        // this.cron_parse(rule[1], "v_min");
        // this.cron_parse(rule[2], "v_hour");
        // this.cron_parse(rule[3], "v_day");
        // this.cron_parse(rule[4], "v_mon");
        // this.cron_parse(rule[5], "v_week");
    },
    bind: function () {
        var list = ["v_second", "v_min", "v_hour", "v_day", "v_mon", "v_week"];
        var selector = null;
        for (var i = 0; i < list.length; i++) {
            $("." + list[i]).on('ifChecked', function(event) {
                Crontab.cron_write();
            });
            $("." + list[i] + "Checkbox").on('ifToggled', function () {
                selector = $(this).data("for");
                if ($("." + selector + ":checked").val() !== 4) {
                    $("." + selector + '[value=4]').iCheck('check');
                }
                Crontab.cron_write();
            });
        }
        var spinnerList = {
            v_secondStart_0: {
                min: 0,
                max: 58
            },
            v_secondEnd_0: {
                min: 1,
                max: 59
            },
            v_secondStart_1: {
                min: 0,
                max: 58
            },
            v_secondEnd_1: {
                min: 2,
                max: 59
            },
            v_secondLoop_1: {
                min: 1,
                max: 30
            },
            v_minStart_0: {
                min: 0,
                max: 58
            },
            v_minEnd_0: {
                min: 1,
                max: 59
            },
            v_minStart_1: {
                min: 0,
                max: 58
            },
            v_minEnd_1: {
                min: 1,
                max: 29
            },
            v_minLoop_1: {
                min: 1,
                max: 30
            },
            v_hourStart_0: {
                min: 0,
                max: 58
            },
            v_hourEnd_0: {
                min: 1,
                max: 59
            },
            v_hourStart_1: {
                min: 0,
                max: 24
            },
            v_hourEnd_1: {
                min: 1,
                max: 24
            },
            v_hourLoop_1: {
                min: 1,
                max: 12
            },
            v_dayStart_0: {
                min: 1,
                max: 30
            },
            v_dayEnd_0: {
                min: 2,
                max: 31
            },
            v_dayStart_1: {
                min: 1,
                max: 30
            },
            v_dayEnd_1: {
                min: 1,
                max: 31
            },
            v_dayLoop_1: {
                min: 1,
                max: 15
            },
            v_monStart_0: {
                min: 1,
                max: 11
            },
            v_monEnd_0: {
                min: 2,
                max: 12
            },
            v_monStart_1: {
                min: 1,
                max: 11
            },
            v_monEnd_1: {
                min: 2,
                max: 12
            },
            v_monLoop_1: {
                min: 1,
                max: 6
            },
            v_weekStart_0: {
                min: 1,
                max: 6
            },
            v_weekEnd_0: {
                min: 2,
                max: 7
            }

        };
        for (var spinner  in spinnerList) {
            $("#" + spinner).spinner(array_merge(spinnerList[spinner], {
                numberFormat: "n",
                change: function (event, ui) {
                    var val = $(this).val();
                    var max = $(this).spinner("option", "max");
                    var min = $(this).spinner("option", "min");
                    if (val < min) $(this).val(min);
                    if (val > max) $(this).val(max);
                    Crontab.cron_write();
                }
            }));
        }
    },
    cron_write: function () {
        var second = Crontab.cron_create("v_second");
        var min = Crontab.cron_create("v_min");
        var hour = Crontab.cron_create("v_hour");
        var day = Crontab.cron_create("v_day");
        var mon = Crontab.cron_create("v_mon");
        var week = Crontab.cron_create("v_week");
        if (second == undefined) second = '';
        if (min == undefined) min = '*';
        if (hour == undefined) hour = '*';
        if (day == undefined) day = '*';
        if (mon == undefined) mon = '*';
        if (week == undefined) week = '*';
        var cron = min + " " + hour + " " + day + " " + mon + " " + week;
        if(second) {
            cron = second + " " + cron;
        }
        this.ruleObj.val(cron);
    },
    cron_parse: function (strVal, strid) {
        var ary = null;
        var end;
        var objRadio = $("." + strid);
        if (strVal == '*') {
            objRadio.eq(0).prop("checked", true);
        }
        else {
            var strValSplitRail = strVal.split('-');
            var strValSplitSlash = strVal.split('/');
            var strValSplitComma = strVal.split(',');
            // rule: 1-2
            if (strValSplitRail.length > 1 && strValSplitSlash.length <= 1) {
                ary = strValSplitRail;
                objRadio.eq(1).prop("checked", true);
                $("#" + strid + "Start_0").val(ary[0]);
                $("#" + strid + "X_0").text(ary[0]);
                $("#" + strid + "End_0").val(ary[1]);
                $("#" + strid + "Y_0").text(ary[1]);
            }
            // rule: 1-59/1
            else if (strValSplitSlash.length > 1 && strValSplitRail.length > 1) {
                ary = strValSplitSlash;
                objRadio.eq(2).prop("checked", true);
                if (ary[0] == '*') {
                    ary[0] = 1;
                    switch (strid) {
                        case "v_second":
                        case "v_min":
                            ary[0] = 0;
                            end = 59;
                            break;
                        case "v_hour":
                            end = 23;
                            break;
                        case "v_day":
                            end = 31;
                            break;
                        case "v_mon":
                            end = 12;
                            break;

                    }
                }
                else if (ary[0].split("-").length > 1) {
                    var ary1 = ary[0].split("-");
                    ary[0] = ary1[0];
                    end = ary1[1];
                }
                $("#" + strid + "Start_1").val(ary[0]);
                $("#" + strid + "X_1").text(ary[0]);
                $("#" + strid + "End_1").val(end);
                $("#" + strid + "Y_1").text(end);
                $("#" + strid + "Loop_1").val(ary[1]);
                $("#" + strid + "Z_1").text(ary[1]);

            }
            // rule: 1,2,3,4,5,6,7,8
            else if(strValSplitComma.length > 1) {
                // 兼容week只有三个选项的问题
                objRadio.filter(function(index) {
                    return $(this).val() == 4;
                }).prop("checked", true);
                if (strVal != "?") {
                    if(strValSplitSlash.length > 1) {
                        ary = strValSplitSlash[0].split(",");
                    }
                    else {
                        ary = strValSplitComma;
                    }
                    $.each($("." + strid + "Checkbox"), function () {
                        if (in_array($(this).val(), ary)) {
                            $(this).prop("checked", true);
                        } else {
                            $(this).prop("checked", false);
                        }
                    });
                }
            }
            // rule: 10/1
            else if (strValSplitSlash.length > 1 && strValSplitRail.length <= 1) {
                ary = strValSplitSlash;
                objRadio.eq(2).prop("checked", true);
                if (ary[0] == '*') {
                    ary[0] = 1;
                    switch (strid) {
                        case "v_second":
                        case "v_min":
                            ary[0] = 0;
                            end = 59;
                            break;
                        case "v_hour":
                            end = 23;
                            break;
                        case "v_day":
                            end = 31;
                            break;
                        case "v_mon":
                            end = 12;
                            break;
                    }
                }
                $("#" + strid + "Start_1").val(ary[0]);
                $("#" + strid + "X_1").text(ary[0]);
                $("#" + strid + "End_1").val(end);
                $("#" + strid + "Y_1").text(end);
                $("#" + strid + "Loop_1").val(ary[1]);
                $("#" + strid + "Z_1").text(ary[1]);
            }
        }
    },
    cron_create: function (strid) {
        var objRadio = $("." + strid + ":checked");
        var type = objRadio.val();
        if (type == 1) {
            return "*";
        }
        var n1, n2, n3;
        if (type == 2) {
            n1 = $("#" + strid + "Start_0").val();
            n2 = $("#" + strid + "End_0").val();
            return n1 + "-" + n2;
        }
        if (type == 3) {
            n1 = $("#" + strid + "Start_1").val();
            n2 = $("#" + strid + "End_1").val();
            n3 = $("#" + strid + "Loop_1").val();
            return n1 + "-" + n2 + "/" + n3;
        }
        if (type == 4) {
            var arr = [];
            $.each($("." + strid + "Checkbox:checked"), function () {
                arr.push($(this).val());
            });
            if (arr.length === 0) {
                return "*";
            }
            return arr.join(",");
        }
    }
};
$(function(){
    Crontab.bind();
    Crontab.init($("#crontab-rule"));
    //iCheck for checkbox and radio inputs
    $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass   : 'iradio_minimal-blue'
    });
});
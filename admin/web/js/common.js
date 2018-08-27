if ( $.uiBackCompat !== false ) {
    $.widget( "ui.spinner", $.ui.spinner, {
        _uiSpinnerHtml: function() {
            return "<span class='ui-spinner ui-widget ui-widget-content ui-corner-all'></span>";
        },

        _buttonHtml: function() {
            return "" +
                "<a class='ui-spinner-button ui-spinner-up ui-corner-tr'>" +
                "<span class='ui-icon " + this.options.icons.up + "'>&#9650;</span>" +
                "</a>" +
                "<a class='ui-spinner-button ui-spinner-down ui-corner-br'>" +
                "<span class='ui-icon " + this.options.icons.down + "'>&#9660;</span>" +
                "</a>";
        }
    });
}
$(function(){
    // 后退
    $('.button-back').on('click', function(){
        window.history.go(-1);
    });

    // 变更状态
    $('.crontab-btn-status').on('click', function(){
        var id = $(this).parent().parent('tr').data('key');
        var value = $(this).data('value');
        var self = $(this);
        $.ajax({
            url: '/crontab/change-status',
            type: 'POST',
            data: {
                id: id,
                status: value
            },
            success: function(response){
                if(response.status === 0) {
                    alert(response.msg);
                }
                // 变更成功
                else {
                    var className = 'label-danger';
                    // 如果新的状态是启用
                    if(response.data.status) {
                        className = 'label-success';
                    }
                    self.removeClass('label-danger label-success');
                    self.addClass(className);
                    self.data('value', response.data.status);
                    self.text(response.data.label);
                }
            }
        });
    });

    // 列表页搜索新增按钮阻止事件冒泡，防止触发展开事件
    $('.search-create-btn').on('click', function(event){
        event.stopPropagation();
    });

    // 列表搜索重置按钮
    $(".search-reset-btn").on('click', function(){
        $('#search-form .form-control:not(\'select\')').each(function(k, v){
            $(v).attr('value', '');
        });
        $('.search-condition select').each(function(k, v){
            // selectize控件
            if($(v).hasClass('selectized')) {
                var $eleSelectize = $(v).selectize();
                $eleSelectize[0].selectize.clear();
            }
            else {
                $(this).find('option:selected').attr('selected', false);
                $(this).find("option:eq(0)").attr("selected", true);
            }
        });
        $('#search-form').submit();
    });
});
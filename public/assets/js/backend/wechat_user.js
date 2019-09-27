define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'wechat_user/index' + location.search,
                    add_url: 'wechat_user/add',
                    edit_url: 'wechat_user/edit',
                    del_url: 'wechat_user/del',
                    multi_url: 'wechat_user/multi',
                    table: 'wechat_user',
                }
            });

            var table = $("#table");

            $.fn.bootstrapTable.locales[Table.defaults.locale]['formatSearch'] = function(){return "openid/unionid";};
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                pageSize:30,
                columns: [
                    [
                        {field: 'id', title: __('Id')},
                        {field: 'nickname', title: '昵称'},
                        {field: 'openid', title: __('Openid')},
                        {field: 'unionid', title: __('Unionid')},
                        {field: 'subscribe', title: '是否订阅', searchList:{"0":"否", "1":"是"}, formatter: Table.api.formatter.normal},
                        {field: 'city', title: '城市'},
                        {field: 'headimgurl', title:'头像', formatter: Table.api.formatter.url},
                        {field: 'subscribe_time', title: '关注时间', operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'subscribe_scene', title: __('Subscribe_scene')},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
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

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'subscribe', title: __('Subscribe')},
                        {field: 'openid', title: __('Openid')},
                        {field: 'nickname', title: __('Nickname')},
                        {field: 'sex', title: __('Sex')},
                        {field: 'language', title: __('Language')},
                        {field: 'city', title: __('City')},
                        {field: 'province', title: __('Province')},
                        {field: 'country', title: __('Country')},
                        {field: 'headimgurl', title: __('Headimgurl'), formatter: Table.api.formatter.url},
                        {field: 'subscribe_time', title: __('Subscribe_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'unionid', title: __('Unionid')},
                        {field: 'remark', title: __('Remark')},
                        {field: 'groupid', title: __('Groupid')},
                        {field: 'tagid_list', title: __('Tagid_list')},
                        {field: 'subscribe_scene', title: __('Subscribe_scene')},
                        {field: 'qr_scene', title: __('Qr_scene')},
                        {field: 'qr_scene_str', title: __('Qr_scene_str')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
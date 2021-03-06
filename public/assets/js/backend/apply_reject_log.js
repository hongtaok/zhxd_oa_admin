define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'apply_reject_log/index' + location.search,
                    add_url: 'apply_reject_log/add',
                    edit_url: 'apply_reject_log/edit',
                    del_url: 'apply_reject_log/del',
                    multi_url: 'apply_reject_log/multi',
                    table: 'apply_reject_log',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search: false,
                showToggle: false,
                pageSize: 30,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'apply_id', title: '贷款申请id'},
                        {field: 'apply.user.username', title: '申请人'},
                        {field: 'apply.admin.username', title: '客户经理'},
                        {field: 'admin.username', title: '操作人'},
                        {field: 'reject_reason', title: __('Reject_reason')},
                        {field: 'reject_time', title: __('Reject_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'employee/index' + location.search,
                    add_url: 'employee/add',
                    edit_url: 'employee/edit',
                    del_url: 'employee/del',
                    multi_url: 'employee/multi',
                    table: 'employee',
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
                        {field: 'name', title: __('Name')},
                        {field: 'password', title: __('Password')},
                        {field: 'sex', title: __('Sex'), searchList: {"1":__('Sex 1'),"2":__('Sex 2')}, formatter: Table.api.formatter.normal},
                        {field: 'phone', title: __('Phone')},
                        {field: 'email', title: __('Email')},
                        {field: 'department_names', title: __('部门')},
                        {field: 'created_at', title: __('Created_at'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'updated_at', title: __('Updated_at'), operate:'RANGE', addclass:'datetimerange'},
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
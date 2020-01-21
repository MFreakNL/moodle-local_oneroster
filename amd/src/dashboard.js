define(['jquery', 'core/ajax', 'core/notification', 'core/templates', 'core/str'], function($, Ajax, Notification, Templates, Str) {
    return {
        init: function() {
            $(document).ready(function() {
                // Reset all checkboxes.
                $('input:checkbox').prop('checked', false);

                // Show schools.
                $('#multiselect-districs').change(function() {
                    console.log('#multiselect-districs is changed!');
                });

                // Show classes.
                $('#multiselect-schools').change(function() {
                    var schoolselect = $(this);
                    var schoolid = schoolselect.val();
                    var wrapperElement = $('#tbl-classes');
                    var selectedoption = schoolselect.find('option:selected');
                    var categoryid = parseInt(selectedoption.attr('data-categoryid'));

                    $('input:checkbox').prop('checked', false);

                    if (categoryid > 0) {
                        $('.btn-action').prop('disabled', false);
                    } else {
                        $('.btn-action').prop('disabled', true);
                    }

                    schoolselect.css('background', 'url(' +
                        M.util.image_url('i/loading', 'moodle') + ') no-repeat center center');

                    // Call the web service.
                    Ajax.call([{
                        methodname: 'local_oneroster_get_external_classes',
                        args: {
                            schoolid: schoolid
                        },
                        done: function(context) {
                            Templates.render('local_oneroster/classes', context)
                                .done(function(html) {
                                    schoolselect.css('background', '');
                                    wrapperElement.empty();
                                    wrapperElement.append(html);
                                    wrapperElement.css('background', '');
                                })
                                .fail(Notification.exception);
                        },
                        fail: Notification.exception,
                    }]);
                });

                // Create school.
                $('#btn-create-school').click(function() {
                    var schoolselect = $('#multiselect-schools');
                    var schoolid = schoolselect.val();
                    var selectedoption = schoolselect.find('option:selected');
                    var categoryid = parseInt(selectedoption.attr('data-categoryid'));

                    if (categoryid === 0 && schoolid > 0) {
                        Str.get_strings([
                            {key: 'confirm', component: 'moodle'},
                            {key: 'createschoolconfirm', component: 'local_oneroster'},
                            {key: 'yes', component: 'moodle'},
                            {key: 'cancel', component: 'moodle'},
                            {key: 'schoolcreatedsuccessfully', component: 'local_oneroster'}
                        ]).done(function(strings) {
                            Notification.confirm(
                                strings[0], // Confirm.
                                strings[1], // Unlink the competency X from the course?
                                strings[2], // Yes.
                                strings[3], // Cancel.
                                function() {
                                    Ajax.call([{
                                        methodname: 'local_oneroster_create_school',
                                        args: {
                                            schoolid: schoolid
                                        },
                                        done: function(data) {
                                            if (data.categoryid > 0) {
                                                selectedoption.attr('data-categoryid', data.categoryid);
                                                $('.btn-action').prop('disabled', false);
                                                Notification.alert(null, strings[4]);
                                            } else {
                                                Notification.alert(null, data.message);
                                            }
                                        },
                                        fail: Notification.exception,
                                    }]);
                                }
                            );
                        }).fail(Notification.exception);
                    }
                });

                // Enroll/Unenroll classes.
                $('.btn-enrol').click(function() {
                    var schoolid = $('#multiselect-schools').val();
                    var wrapperElement = $('#tbl-classes');
                    var unenroll = parseInt($(this).attr('data-unenrol'));

                    var levels = [];
                    $("input[name='level[]']:checked").each(function() {
                        levels.push($(this).val());
                    });

                    var classes = [];
                    $("input[name='classes[]']:checked").each(function() {
                        classes.push($(this).val());
                    });


                    if (classes.length > 0 && levels.length > 0) {
                        Str.get_strings([
                            {key: 'confirm', component: 'moodle'},
                            {key: 'enrollclassesconfirm', component: 'local_oneroster',
                                param: ((unenroll > 0) ? 'unenroll' : 'enroll')},
                            {key: 'yes', component: 'moodle'},
                            {key: 'cancel', component: 'moodle'},
                            {key: 'taskscheduled', component: 'local_oneroster'},
                            {key: 'failed', component: 'local_oneroster'}
                        ]).done(function(strings) {
                            Notification.confirm(
                                strings[0], // Confirm.
                                strings[1], // Unlink the competency X from the course?
                                strings[2], // Yes.
                                strings[3], // Cancel.
                                function() {
                                    Ajax.call([{
                                        methodname: 'local_oneroster_create_level',
                                        args: {
                                            classids: classes,
                                            levels: levels,
                                            unenroll: unenroll
                                        },
                                        done: function(data) {
                                            if (data) {
                                                Notification.alert(null, strings[4]);
                                                // Call the web service.
                                                Ajax.call([{
                                                    methodname: 'local_oneroster_get_external_classes',
                                                    args: {
                                                        schoolid: schoolid
                                                    },
                                                    done: function(context) {
                                                        Templates.render('local_oneroster/classes', context)
                                                            .done(function(html) {
                                                                wrapperElement.empty();
                                                                wrapperElement.append(html);
                                                            })
                                                            .fail(Notification.exception);
                                                    },
                                                    fail: Notification.exception,
                                                }]);
                                            } else {
                                                Notification.alert(null, strings[5]);
                                            }
                                            $('input:checkbox').prop('checked', false);
                                        },
                                        fail: Notification.exception,
                                    }]);
                                }
                            );
                        }).fail(Notification.exception);
                    } else {
                        Str.get_strings([
                            {key: 'chooseclassandlevel', component: 'local_oneroster'}
                        ]).done(function(strings) {
                            Notification.alert(null, strings[0]);
                        });
                    }
                });

                // Sync Members.
                $('#btn-sync-members').click(function() {
                    var schoolid = $('#multiselect-schools').val();
                    var wrapperElement = $('#tbl-classes');

                    if (schoolid > 0) {
                        Str.get_strings([
                            {key: 'confirm', component: 'moodle'},
                            {key: 'syncmembersconfirm', component: 'local_oneroster'},
                            {key: 'yes', component: 'moodle'},
                            {key: 'cancel', component: 'moodle'},
                            {key: 'synced', component: 'local_oneroster'},
                            {key: 'failed', component: 'local_oneroster'}
                        ]).done(function(strings) {
                            Notification.confirm(
                                strings[0], // Confirm.
                                strings[1], // Unlink the competency X from the course?
                                strings[2], // Yes.
                                strings[3], // Cancel.
                                function() {
                                    Ajax.call([{
                                        methodname: 'local_oneroster_sync_members',
                                        args: {
                                            schoolid: schoolid
                                        },
                                        done: function(data) {
                                            if (data) {
                                                Notification.alert(null, strings[4]);
                                                // Call the web service.
                                                Ajax.call([{
                                                    methodname: 'local_oneroster_sync_members',
                                                    args: {
                                                        schoolid: schoolid
                                                    },
                                                    done: function(data) {
                                                        if (data) {
                                                            Notification.alert(null, strings[4]);
                                                        } else {
                                                            Notification.alert(null, strings[5]);
                                                        }
                                                    },
                                                    fail: Notification.exception,
                                                }]);
                                            } else {
                                                Notification.alert(null, strings[5]);
                                            }
                                            $('input:checkbox').prop('checked', false);
                                        },
                                        fail: Notification.exception,
                                    }]);
                                }
                            );
                        }).fail(Notification.exception);
                    } else {
                        Str.get_strings([
                            {key: 'chooseclassandlevel', component: 'local_oneroster'}
                        ]).done(function(strings) {
                            Notification.alert(null, strings[0]);
                        });
                    }
                });

            });
        }
    };
});
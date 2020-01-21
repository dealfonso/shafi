<?php 
      include('templates/part/modal-confirm.php');
?>
<div class="col-md-12">
    <div class="container text-center">
        <h3><?php _e('Available files') ?></h3>
        <?php
        global $current_user;
        if ($show_all_users) {
            // This is the unique moment in which a file in 'd' state can be shown
            $files = SHAFile::search([], 0, 0, false, 'AND', 'time');
        } else {
            $files = SHAFile::search(['owner' => $current_user->get_username(), '!state' => 'd' ],
                0, 0, false, 'AND', 'time');
        }

        $fields = array(
            "id" => [
                'function' => function ($o) { 
                    if ($o->is_active())
                        return '<a href="' . add_query_var(['op' => 'edit', 'id' => $o->get_id() ], get_root_url()) . '">' . $o->get_id() . '<i class="openext far fa-edit"></i></a>';
                    else
                        return $o->get_id();
                },
                'title' => 'ID'
            ],
            "creacion" => array (
                'title' => __('Creation date'), 
                'function' => function($o) { $t=$o->get_field('time'); return SCPM_datetime_to_string($t); }, 
                'value' => function($o) { $date = $o->get_field('time'); return $date===null?'':$date->getTimestamp(); } 
            ),
            "name" => [
                'title' => __('Name'),
                'function' => function($o) { $n = $o->get_field('name'); return strlen($n)>24?substr($n, 0, 24)."...":$n; }
            ],
            "estado" => array (
                'title' => __('Status'), 
                'function' => function($o) { /* we need to force the change of the state if it was processing */ $o->is_processing(); return __STATE[$o->get_field('state')]; }, 
            )
        );
        if ($show_all_users) {
            $fields = $fields + [ 'owner' => __('Owner') ];
        }
        $list = new DDN_List(
            $files, $fields + array(
                "_buttons" => function($o) {
                    $id = $o->get_id();

                    if ($o->is_active())
                        return '<a href="javascript:showmodal_cancelfile(\'' . htmlspecialchars($o->get_field('name')) . '\', \'' . $id .'\', \'' . add_query_var(['op' => 'del', 'id' => $id ], __ADMIN_URL) . '\')"><i class="fas fa-times"></i></a>';
                }                            
                )
        );
        echo $list->render('sortable');
        ?>
    </div>
</div>

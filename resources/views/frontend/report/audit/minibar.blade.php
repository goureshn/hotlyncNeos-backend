<div style="margin-top: 5px">
    <table class="grid" style="width : 100%">
        <thead >
        <tr style="background-color: #c2dbec;">
            <th  width="5%"><b>No</b></th>
            <th  width="20%"><b>Item Name</b></th>
            <th  width="20%"><b>Description</b></th>
            <th  width="10%"><b>Charge</b></th>
            <th  width="10%"><b>PMS Code</b></th>
            <th  width="10%"><b>IVR Code</b></th>
            <th  width="10%"><b>Max Qty</b></th>
            <th  width="15%"><b>Status</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['minibar'] as  $key => $row)
            <tr class="">
                <td>{{$row->id}}</td>
                <td>{{$row->item_name}}</td>
                <td>{{$row->desc}}</td>
                <td>{{$row->charge}}</td>
                <td>{{$row->pms_code}}</td>
                <td>{{$row->ivr_code}}</td>
                <td>{{$row->max_qty}}</td>
                <td>
                    <?php

                    if($row->active_status == 1) {
                    ?>
                        Active
                    <?php
                    }else {
                    ?>
                     Inactive
                    <?php } ?>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

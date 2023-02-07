<style>
    .row {
        display: flex;
        padding-top: 6px;
    }
    td {
        font-size: 8px !important;
    }
</style>
<?php
$path = $_SERVER['DOCUMENT_ROOT'] . $logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);
?>
<div style="padding:20px 15px 15px 15px">
    <div align="left" id="bloc1">
        <img src="<?php echo $logo_image_data?>" alt=""  width = 70>
    </div>
    <table>
        <tr style="padding-top: 6px">
            <td style="width: 30%">
                Date & Time
            </td>
            <td>
                {{$info['start_time']}}
            </td>
        </tr>
        <tr style="padding-top: 6px">
            <td style="width: 16.67%">
                Guest Number
            </td>
            <td>
                {{$info['mobile_number']}}
            </td>
        </tr>
        <tr style="padding-top: 6px">
            <td style="width: 16.67%">
                Guest Name
            </td>
            <td>
                {{$info['guest_name']}}
            </td>
        </tr>
        <tr style="padding-top: 6px">
            <td style="width: 16.67%">
                Chat Duration
            </td>
            <td>
                {{$info['chat_duration']}}
            </td>
        </tr>
        <tr style="padding-top: 6px">
            <td style="width: 16.67%">
                Wait Time
            </td>
            <td>
                {{$info['wait_time']}}
            </td>
        </tr>
        <tr style="padding-top: 6px">
            <td style="width: 16.67%">
                Agent
            </td>
            <td>
                {{$info['agent_name']}}
            </td>
        </tr>
    </table>
    <br/><br/>
    <div class="row">
        <div class="col-lg-12">
            Chat conversation details are as follows:
        </div>
    </div>
    <br/>
    <table>
        @foreach($info['chat_histories'] as $history)
            <tr style="padding-top: 6px">
                <td style="width: 40%">
                    [{{date('D d M h:i:s A', strtotime($history->created_at))}}]&nbsp;
                    @if($history->direction == 1)
                        <span style="font-weight: bolder">{{$info['agent_name']}}: </span>
                    @else
                        <span style="font-weight: bolder">{{$info['mobile_number']}}/{{$info['guest_name']}}: </span>
                    @endif
                </td>
                <td>
                    {{$history->text}}
                </td>
            </tr>
        @endforeach
    </table>
</div>





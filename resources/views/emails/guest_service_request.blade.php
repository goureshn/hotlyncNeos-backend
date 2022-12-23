<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width" />
    <title>HotLync Engineering Requests</title>


    <style type="text/css">
        /*////// RESET STYLES //////*/
        body, #bodyTable, .bodyCell {
            height: 100% !important;
            margin: 0;
            padding: 0;
            width: 100% !important;
        }

        .bodyCell {
            padding: 10px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .chart-table td {
            height: 18px;
        }

        .chart-table td:first-child {
            border-radius: 8px 0 0 8px;
        }
        .chart-table td:last-child {
            border-radius: 0 8px 8px 0;
        }

        
        h1, h2, h3, h4, h5, h6 {
            margin: 0;
            padding: 0;
        }

        .main-title-wrapper td {
            text-align: center;
        }

        .container {
            background-color: #ffffff;
            border: 1px solid #DDDDDD;
            max-width: 900px;
            min-width: 640px;
            margin:auto;
            margin-top: 40px;
            padding: 10px;
        }

        .count-item .count {
            font-size: 36px;
        }

        .instruction-wrapper .item-wrapper {
            width: 50%;
            float: left;
            align-items: center;
            padding-top: 8px;
            display: flex;
        }

        .instruction-color-show {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            border: 1px #0b1014;
        }

    </style>
</head>

<body style="padding-top: 100px">
<center>
    <table class="container" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
        <tr>
            <td align="center" valign="top" class="bodyCell">
                <table>
                    <tr>
                        <td>
                            <img style="height: 100%" src="https://ennovatech.com/assets/images/company-logo/hotlync.svg">
                        </td>
                        <td align="right">
                            <h3>
                                {{$info['property_name']}}
                            </h3>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="height: 2px;">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="height: 1px; background-color: #0b1014;">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="center" valign="top" class="bodyCell">
                <table>
                    <tr>
                        <td colspan="3">
                            <h2 style="margin: 0">{{$info['mainTitle']}}</h2>
                        </td>
                        <td colspan="9" align="right">
                            <h3>
                                {{$info['timeInfo']}}
                            </h3>
                        </td>
                    </tr>
                    <tr class="main-title-wrapper">
                        @php
                            $onTimeCount = isset($info['totalInfo']) ? $info['totalInfo']['ontime'] : 0;
                            $escalatedCount = isset($info['totalInfo']) ? $info['totalInfo']['escalated'] : 0;
                            $timeoutCount = isset($info['totalInfo']) ? $info['totalInfo']['timeout'] : 0;
                            $holdCount = isset($info['totalInfo']) ? $info['totalInfo']['hold'] : 0;

                            $totalCount = $onTimeCount + $escalatedCount + $timeoutCount + $holdCount;
                        @endphp
                        <td style="width: 20%">
                            <span style="font-family: fantasy;color: #2196f3; font-size: 36px; margin: 0">{{$totalCount}}</span>
                            <br/>
                            <label>Total Request</label>
                        </td>
                        <td style="width: 20%">
                            <span style="font-family: fantasy;color: #00a14d; font-size: 36px; margin: 0">{{$onTimeCount}}</span>
                            <br/>
                            <label>On Time</label>
                        </td>
                        <td style="width: 20%">
                            <span style="font-family: fantasy;color: #bb5f28; font-size: 36px; margin: 0">{{$escalatedCount}}</span>
                            <br/>
                            <label>Escalated</label>
                        </td>
                        <td style="width: 20%">
                            <span style="font-family: fantasy;color: #ee6757; font-size: 36px; margin: 0">{{$timeoutCount}}</span>
                            <br/>
                            <label>Time Out</label>
                        </td>
                        <td style="width: 20%">
                            <span style="font-family: fantasy;color: #d4e157; font-size: 36px; margin: 0">{{$holdCount}}</span>
                            <br/>
                            <label>On Hold</label>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="bodyCell">
                <h3>
                    @if ($info['jobRole'] == 'all')
                        By Status
                    @elseif($info['jobRole'] == 'individual')
                        By Staff
                    @endif
                </h3>
            </td>
        </tr>
        @foreach($info['otherInfo'] as $key => $otherItem)
            @php
                $count = count($otherItem);
                $tCount = 0;

                foreach ($otherItem as $item) {

                    $tCount += $item['count'];
                }

            @endphp
            @if ($tCount > 0)
                <tr>
                    <td class="bodyCell">
                        <table>
                            @php
                                $color = "";
                                $name = "";
                                if ($key === 'ontime') {
                                    $name = "On time";
                                    $color = '#00a14d';
                                } else if($key === 'escalated') {
                                    $name = "Escalated";
                                    $color = '#fb8c00';
                                } else if($key === 'timeout') {
                                    $name = 'Timeout';
                                    $color = '#ee6757';
                                } else {
                                    $name = 'Hold';
                                    $color = '#d4e157';
                                }
                            @endphp
                            <tr>
                                <td>

                                    <h3 style="margin: 0; color: {{$color}}">
                                        {{$name}}
                                    </h3>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table class="chart-table">
                                        <tr>
                                            @foreach($otherItem as $chartIndex => $row)
                                                <td style="background-color: {{$color}}; opacity: {{1 - $chartIndex / count($otherItem)}}; width: {{100 * $row['count'] / $tCount . '%'}}" title="{{$row['label']}} - {{$row['count']}}"></td>
                                            @endforeach
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 10px">
                                    <table>
                                        @foreach($otherItem as $otherItemIndex => $row)
                                            @if ($otherItemIndex % 2 == 0)
                                                <tr>
                                                    @endif
                                                    <td>
                                                        <table cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td style="width: 20px; height: 20px;padding-top: 4px; padding-bottom: 4px">
                                                                    <div class="instruction-color-show" style="background-color: {{$color}}; opacity: {{1 - $otherItemIndex / count($otherItem)}};">&nbsp;</div>
                                                                </td>
                                                                <td style="padding-left: 20px">
                                                                    {{$row['label'] . ' (' . $row['count'] . ')'}}
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    @if ($otherItemIndex % 2 == 1)
                                                </tr>
                                            @endif
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            @endif
        @endforeach
        <tr>
            <td class="bodyCell">
                <h3>By Item</h3>
            </td>
        </tr>
        @php
            $tItemCount = 0;
            $tItemColor = '#ff00ff';
            foreach ($info['itemInfo'] as $itemInfoKey => $itemInfo) {
                $tItemCount += $itemInfo['count'];
            }
        @endphp
        <tr>
            <td class="bodyCell">
                <table class="chart-table">
                    <tr>
                        @foreach($info['itemInfo'] as $itemInfoKey => $itemInfo)
                            <td style="background-color: {{$tItemColor}}; opacity: {{1 - $itemInfoKey / count($info['itemInfo'])}}; width: {{100 * $itemInfo['count'] / $tItemCount . '%'}}" title="{{$itemInfo['name']}} - {{$itemInfo['count']}}"></td>
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px">
                <table>
                    @foreach($info['itemInfo'] as $itemInfoKey => $itemInfo)
                        @if ($itemInfoKey % 2 == 0)
                            <tr>
                                @endif
                                <td>
                                    <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="width: 20px; height: 20px;padding-top: 4px; padding-bottom: 4px">
                                                <div class="instruction-color-show" style="background-color: {{$tItemColor}}; opacity: {{1 - $itemInfoKey / count($info['itemInfo'])}};">&nbsp;</div>
                                            </td>
                                            <td style="padding-left: 20px">
                                                {{$itemInfo['name'] . ' (' . $itemInfo['count'] . ')'}}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                @if ($itemInfoKey % 2 == 1)
                            </tr>
                        @endif
                    @endforeach
                </table>
            </td>
        </tr>
        <tr>
            <td class="bodyCell">
                <h3>By Location</h3>
            </td>
        </tr>
        @php
            $tLocationCount = 0;
            $tLocationColor = '#00ffff';
            foreach ($info['locationInfo'] as $locationInfoKey => $locationInfo) {
                $tLocationCount += $locationInfo['count'];
            }
        @endphp
        <tr>
            <td class="bodyCell">
                <table class="chart-table">
                    <tr>
                        @foreach($info['locationInfo'] as $locationInfoKey => $locationInfo)
                            <td style="background-color: {{$tLocationColor}}; opacity: {{1 - $locationInfoKey / count($info['locationInfo'])}}; width: {{100 * $locationInfo['count'] / $tLocationCount . '%'}}" title="{{$locationInfo['name']}} - {{$locationInfo['count']}}"></td>
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px">
                <table>
                    @foreach($info['locationInfo'] as $locationInfoKey => $locationInfo)
                        @if ($locationInfoKey % 2 == 0)
                            <tr>
                                @endif
                                <td>
                                    <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="width: 20px; height: 20px;padding-top: 4px; padding-bottom: 4px">
                                                <div class="instruction-color-show" style="background-color: {{$tLocationColor}}; opacity: {{1 - $locationInfoKey / count($info['locationInfo'])}};">&nbsp;</div>
                                            </td>
                                            <td style="padding-left: 20px">
                                                {{$locationInfo['name'] . ' (' . $locationInfo['count'] . ')'}}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                @if ($locationInfoKey % 2 == 1)
                            </tr>
                        @endif
                    @endforeach
                </table>
            </td>
        </tr>
    </table>
</center>
</body>

</html>

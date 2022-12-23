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

        img, a img {
            border: 0;
            outline: none;
            text-decoration: none;
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
                            <img style="height: 100%" src="http://www.ennovatech.ae/wp-content/uploads/2015/12/hotlync_c-300x49.png">
                        </td>
                        <td align="right">
                            <h3>
                                {{$info['timeInfo']}}
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
                        <td colspan="5">
                            <h2 style="margin: 0">{{$info['mainTitle']}}</h2>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
{{--        by status part --}}
        @if (!empty($info['statusInfo']))
            <tr>
                <td class="bodyCell">
                    <h3>By Status</h3>
                </td>
            </tr>
        @endif

        @php
            $tItemCount = 0;
            $tItemColor = '#7e70ef';
            foreach ($info['statusInfo'] as $itemInfoKey => $itemInfo) {
                $tItemCount += $itemInfo['count'];
            }
        @endphp
        <tr>
            <td class="bodyCell">
                <table class="chart-table">
                    <tr>
                        @foreach($info['statusInfo'] as $itemInfoKey => $itemInfo)
                            <td style="background-color: {{$tItemColor}}; opacity: {{1 - $itemInfoKey / count($info['statusInfo'])}}; width: {{100 * $itemInfo['count'] / $tItemCount . '%'}}" title="{{$itemInfo['name']}} - {{$itemInfo['count']}}"></td>
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px">
                <table>
                    @foreach($info['statusInfo'] as $itemInfoKey => $itemInfo)
                        @if ($itemInfoKey % 2 == 0)
                            <tr>
                                @endif
                                <td>
                                    <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="width: 20px; height: 20px;padding-top: 4px; padding-bottom: 4px">
                                                <div class="instruction-color-show" style="background-color: {{$tItemColor}}; opacity: {{1 - $itemInfoKey / count($info['statusInfo'])}};">&nbsp;</div>
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

{{--        end of status part --}}
{{--        by service recovery part --}}
        @if (!empty($info['serviceInfo']))
            <tr>
                <td class="bodyCell">
                    <h3>By Service Recovery</h3>
                </td>
            </tr>
        @endif
        @php
            $tItemCount = 0;
            $tItemColor = '#f13eb1';
            foreach ($info['serviceInfo'] as $itemInfoKey => $itemInfo) {
                $tItemCount += $itemInfo['count'];
            }
        @endphp
        <tr>
            <td class="bodyCell">
                <table class="chart-table">
                    <tr>
                        @foreach($info['serviceInfo'] as $itemInfoKey => $itemInfo)
                            <td style="background-color: {{$tItemColor}}; opacity: {{1 - $itemInfoKey / count($info['serviceInfo'])}}; width: {{100 * $itemInfo['count'] / $tItemCount . '%'}}" title="{{$itemInfo['name']}} - {{$itemInfo['count']}}"></td>
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px">
                <table>
                    @foreach($info['serviceInfo'] as $itemInfoKey => $itemInfo)
                        @if ($itemInfoKey % 2 == 0)
                            <tr>
                                @endif
                                <td>
                                    <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="width: 20px; height: 20px;padding-top: 4px; padding-bottom: 4px">
                                                <div class="instruction-color-show" style="background-color: {{$tItemColor}}; opacity: {{1 - $itemInfoKey / count($info['serviceInfo'])}};">&nbsp;</div>
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
        {{--        end of service recovery part --}}
        {{--        by category part --}}
        @if (!empty($info['categoryInfo']))
            <tr>
                <td class="bodyCell">
                    <h3>By Category</h3>
                </td>
            </tr>
        @endif
        @php
            $tItemCount = 0;
            $tItemColor = '#34d1f3';
            foreach ($info['categoryInfo'] as $itemInfoKey => $itemInfo) {
                $tItemCount += $itemInfo['count'];
            }
        @endphp
        <tr>
            <td class="bodyCell">
                <table class="chart-table">
                    <tr>
                        @foreach($info['categoryInfo'] as $itemInfoKey => $itemInfo)
                            <td style="background-color: {{$tItemColor}}; opacity: {{1 - $itemInfoKey / count($info['categoryInfo'])}}; width: {{100 * $itemInfo['count'] / $tItemCount . '%'}}" title="{{$itemInfo['name']}} - {{$itemInfo['count']}}"></td>
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px">
                <table>
                    @foreach($info['categoryInfo'] as $itemInfoKey => $itemInfo)
                        @if ($itemInfoKey % 2 == 0)
                            <tr>
                                @endif
                                <td>
                                    <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="width: 20px; height: 20px;padding-top: 4px; padding-bottom: 4px">
                                                <div class="instruction-color-show" style="background-color: {{$tItemColor}}; opacity: {{1 - $itemInfoKey / count($info['categoryInfo'])}};">&nbsp;</div>
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
        {{--        end of service recovery part --}}

        {{--        by Nationality --}}
        @if(!empty($info['nationalityInfo']))
            <tr>
                <td class="bodyCell">
                    <h3>By Nationality</h3>
                </td>
            </tr>
        @endif
        @php
            $tItemCount = 0;
            $tItemColor = '#34fd29';
            foreach ($info['nationalityInfo'] as $itemInfoKey => $itemInfo) {
                $tItemCount += $itemInfo['count'];
            }
        @endphp
        <tr>
            <td class="bodyCell">
                <table class="chart-table">
                    <tr>
                        @foreach($info['nationalityInfo'] as $itemInfoKey => $itemInfo)
                            <td style="background-color: {{$tItemColor}}; opacity: {{1 - $itemInfoKey / count($info['nationalityInfo'])}}; width: {{100 * $itemInfo['count'] / $tItemCount . '%'}}" title="{{$itemInfo['name']}} - {{$itemInfo['count']}}"></td>
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px">
                <table>
                    @foreach($info['nationalityInfo'] as $itemInfoKey => $itemInfo)
                        @if ($itemInfoKey % 2 == 0)
                            <tr>
                                @endif
                                <td>
                                    <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="width: 20px; height: 20px;padding-top: 4px; padding-bottom: 4px">
                                                <div class="instruction-color-show" style="background-color: {{$tItemColor}}; opacity: {{1 - $itemInfoKey / count($info['nationalityInfo'])}};">&nbsp;</div>
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
        {{--        end of service recovery part --}}

    </table>
</center>
</body>

</html>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

@include('emails.guestrequest_header')

<body> 
    <center>
        <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
            <tr>
                <td align="center" valign="top" id="bodyCell">                
                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="emailBody">                    
                        <tr>
                            <td align="center" valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td valign="top" class="textContent">
                                            <br /> <p>Dear {{$info['wholename']}}</p>    
                                            <p>New Lost&Found Items are posted.</p>                                        
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" align="Left" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Type</b>: {{$info['lnf']->lnf_type}}</p>
                                            <p><b>Found By</b>: {{$info['lnf']->found_by_name}}</p>
                                            <p><b>Location</b>: {{$info['lnf']->lgm_name}}</p>                                                                                                                                                                      
                                        </td>
                                    </tr>                                    
                                </table>
                                <table border="0" align="Right" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Date Found</b>: {{date('d M Y', strtotime($info['lnf']->lnf_time))}}</p>
                                            <p><b>Received By</b>: {{$info['lnf']->received_by_name}}</p>                                            
                                        </td>
                                    </tr>                                    
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell" style="padding-bottom: 10px;" >
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%" class="flexibleContainer">
                                    <tr class="table-data">
                                        <td class="table-header" style="width: 25%">Qty</td>
                                        <td class="table-header" style="width: 25%">Item Tyep</td>
                                        <td class="table-header" style="width: 25%">Category</td>
                                        <td class="table-header" style="width: 25%">Brand</td>
                                    </tr>
                                    @foreach($info['item_list'] as $row)
                                        <tr class="table-data">                                            
                                            <td style="font-size:14px;">{{$row->quantity}}</td>
                                            <td style="font-size:14px;">{{$row->item_type}}</td>
                                            <td style="font-size:14px;">{{$row->category}}</td>
                                            <td style="font-size:14px;">{{$row->brand}}</td>
                                        </tr>
                                    @endforeach    

                                </table>
                            </td>
                        </tr>
                    </table>    
                </td>
            </tr>
        </table>
        @include('emails.guestrequest_footer')
    </center>
</body>

</html>
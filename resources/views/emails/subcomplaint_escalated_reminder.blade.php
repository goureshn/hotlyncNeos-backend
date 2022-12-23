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
                                            <br /> <p>Dear {{$info['first_name']}} {{$info['last_name']}},</p>                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td valign="top">
                                            <p>{{$info['ticket_id']}} has been escalated. Please see below details</p>    
                                            <p>Age: {{$info['sub']->age_days}} days ago</p>                                                  
                                        </td>
                                    </tr>                                    
                                </table>
                            </td>
                        </tr>
                        
                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell">
                                GUEST DETAILS
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" align="Left" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">                          
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Guest Name</b>: {{$info['sub']->guest_name}}</p>   
                                            <p><b>Location</b>: {{$info['sub']->location_name}} - {{$info['sub']->location_type}}</p>
                                            <p><b>Nationality</b>: {{$info['sub']->nationality_name}}</p>   
                                            <p><b>Arrival Date</b>: {{$info['sub']->arrival}}</p>   
                                        </td>
                                    </tr>                                    
                                </table>
                                <table border="0" align="Right" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Guest Type</b>: {{$info['sub']->guest_type}}</p>   
                                            <p><b>Source</b>: {{$info['sub']->source}}</p>
                                            <p><b>Type</b>: {{$info['sub']->feedback_type}}</p>   
                                            <p><b>Arrival Date</b>: {{$info['sub']->departure}}</p>   
                                        </td>
                                    </tr>                                    
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell">
                                FEEDBACK DETAILS
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" align="Left" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">                          
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Department</b>: {{$info['sub']->department}}</p>   
                                            <p><b>Category</b>: {{$info['sub']->category_name}}</p>
                                            <p><b>Created at</b>: {{$info['sub']->created_at}}</p>   
                                        </td>
                                    </tr>                                    
                                </table>
                                <table border="0" align="Right" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Severity</b>: {{$info['sub']->severity_name}}</p>   
                                            <p><b>Sub-category</b>: {{$info['sub']->subcategory_name}}</p>
                                            <p><b>Created by</b>: {{$info['sub']->created_by}}</p>      
                                        </td>
                                    </tr>                                    
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td align="center" valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td valign="top">
                                            <br /> <p>Comment: {!! nl2br(e($info['sub']->comment)) !!}</p>                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td valign="top">
                                            <br /> <p>Initial Response: {!! nl2br(e($info['sub']->init_response)) !!}</p>                                            
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>    

                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell">
                                 COMPENSATION
                            </td>
                        </tr>

                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell" style="padding-bottom: 10px;" >
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%" class="flexibleContainer">
                                    <tr class="table-data">
                                        <td class="table-header" style="width: 10%">No</td>
                                        <td class="table-header" style="width: 20%">Compensation</td>
                                        <td class="table-header" style="width: 15%">Cost</td>
                                        <td class="table-header" style="width: 25%">Provided By</td>
                                        <td class="table-header" style="width: 25%">Date & Time</td>
                                    </tr>
                                    @foreach($info['compensation_list'] as $row)
                                        <tr class="table-data">
                                            <td style="font-size:14px;">{{$row->no}}</td>
                                            <td style="font-size:14px;">{{$row->compensation}}</td>
                                            <td style="font-size:14px;">{{$row->cost}}</td>
                                            <td style="font-size:14px;">{{$row->wholename}} - {{$row->department}}</td>
                                            <td style="font-size:14px;">{{$row->created_at}}</td>                                            
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
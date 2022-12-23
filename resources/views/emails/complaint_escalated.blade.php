<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<?php
function isCheckNull($val) {
  if($val == null) {
      return "";
  }else {
      return $val;
  }
}

function convertDateTime($val) {
  $date_val = date_format(new DateTime($val),'d-M-Y H:i:s');
  return  $date_val;   
}

function converDate($val) {
  $date_val = date_format(new DateTime($val),'d-M-Y');
  return  $date_val;
}
?>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width" />
  <title>HotLync Complaints</title>

  

  <style type="text/css">
    /*////// RESET STYLES //////*/
    body, #bodyTable, #bodyCell {
      height: 100% !important;
      margin: 0;
      padding: 0;
      width: 100% !important;
    }
    table {
      border-collapse: collapse;
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
    p {
      margin: 1em 0;
    }
    
    /*////// CLIENT-SPECIFIC STYLES //////*/
    .ReadMsgBody {
      width: 100%;
    }
    .ExternalClass {
      width: 100%;
    } /* Force Hotmail/Outlook.com to display emails at full width. */
    .ExternalClass,
    .ExternalClass p,
    .ExternalClass span,
    .ExternalClass font,
    .ExternalClass td,
    .ExternalClass div {
      line-height: 100%;
    } /* Force Hotmail/Outlook.com to display line heights normally. */
    table, td {
      mso-table-lspace: 0pt;
      mso-table-rspace: 0pt;
    } /* Remove spacing between tables in Outlook 2007 and up. */
    #outlook a {
      padding: 0;
    } /* Force Outlook 2007 and up to provide a "view in browser" message. */
    img {
      -ms-interpolation-mode: bicubic;
    } /* Force IE to smoothly render resized images. */
    body, table, td, p, a, li, blockquote {
      -ms-text-size-adjust: 100%;
      -webkit-text-size-adjust: 100%;
    } /* Prevent Windows- and Webkit-based mobile platforms from changing declared text sizes. */
    
    /*////// FRAMEWORK STYLES //////*/
    .flexibleContainerCell {
      padding-top: 5px;
      padding-Right: 10px;
      padding-Left: 10px;
    }
    .flexibleImage {
      height: auto;
    }
    .bottomShim {
      padding-bottom: 20px;
    }
    .imageContent, .imageContentLast {
      padding-bottom: 20px;
    }
    .nestedContainerCell {
      padding-top: 20px;
      padding-Right: 20px;
      padding-Left: 20px;
    }
    
    /*////// GENERAL STYLES //////*/
    body, #bodyTable {
      background-color: #F5F5F5;
    }
    #bodyCell {
      padding-top: 40px;
      padding-bottom: 40px;
    }
    #emailBody {
      background-color: #FFFFFF;
      border: 1px solid #DDDDDD;
      border-collapse: separate;
      border-radius: 4px;
    }
    h1, h2, h3, h4, h5, h6 {
      color: #202020;
      font-family: Helvetica;
      font-size: 16px;
      line-height: 125%;
      text-align: Left;
    }
    .textContent, .textContentLast {
      color: #404040;
      font-family: Helvetica;
      font-size: 14px;
      line-height: 125%;
      text-align: Left;
      padding-bottom: 20px;
    }
    .textContent a, .textContentLast a {
      color: #2C9AB7;
      text-decoration: underline;
    }
    .nestedContainer {
      background-color: #E5E5E5;
      border: 1px solid #CCCCCC;
    }
    .emailButton {
      background-color: #5cb85c;
      border-collapse: separate;
      border-radius: 4px;
    }
	.emailButtonrej {
      background-color: #d9534f;
      border-collapse: separate;
      border-radius: 4px;
    }
    .buttonContent {
      color: #FFFFFF;
      font-family: Helvetica;
      font-size: 18px;
      font-weight: bold;
      line-height: 100%;
      padding: 15px;
      text-align: center;
    }
    .buttonContent a {
      color: #FFFFFF;
      display: block;
      text-decoration: none;
    }
    .emailCalendar {
      background-color: #FFFFFF;
      border: 1px solid #CCCCCC;
    }
    .emailCalendarMonth {
      background-color: #2C9AB7;
      color: #FFFFFF;
      font-family: Helvetica, Arial, sans-serif;
      font-size: 16px;
      font-weight: bold;
      padding-top: 10px;
      padding-bottom: 10px;
      text-align: center;
    }
    .emailCalendarDay {
      color: #2C9AB7;
      font-family: Helvetica, Arial, sans-serif;
      font-size: 60px;
      font-weight: bold;
      line-height: 100%;
      padding-top: 20px;
      padding-bottom: 20px;
      text-align: center;
    }
    
    /*////// MOBILE STYLES //////*/
    @media only screen and (max-width: 480px) {
      /*////// CLIENT-SPECIFIC STYLES //////*/
      body {
        width: 100% !important;
        min-width: 100% !important;
      } /* Force iOS Mail to render the email at full width. */
    
      /*////// FRAMEWORK STYLES //////*/
      /*
    					CSS selectors are written in attribute
    					selector format to prevent Yahoo Mail
    					from rendering media query styles on
    					desktop.
    				*/
      table[id="emailBody"], table[class="flexibleContainer"] {
        width: 100% !important;
      }
    
      /*
    					The following style rule makes any
    					image classed with 'flexibleImage'
    					fluid when the query activates.
    					Make sure you add an inline max-width
    					to those images to prevent them
    					from blowing out. 
    				*/
      img[class="flexibleImage"] {
        height: auto !important;
        width: 100% !important;
      }
    
      /*
    					Make buttons in the email span the
    					full width of their container, allowing
    					for left- or right-handed ease of use.
    				*/
      table[class="emailButton"] {
        width: 100% !important;
      }
      td[class="buttonContent"] {
        padding: 0 !important;
      }
      td[class="buttonContent"] a {
        padding: 15px !important;
      }
    
      td[class="textContentLast"], td[class="imageContentLast"] {
        padding-top: 20px !important;
      }
    
      /*////// GENERAL STYLES //////*/
      td[id="bodyCell"] {
        padding-top: 10px !important;
        padding-Right: 10px !important;
        padding-Left: 10px !important;
      }
    }
  </style>
</head>

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
                                  <br /> <p>Dear {{$info['wholename']}},</p>
                                  <p>A Feedback <b>{{$info['id']}}</b> in <b>{{$info['cat_name']}}</b> has been escalated.</p>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      
            
                         <tr>
                          <td valign="top" width="600" class="flexibleContainerCell">
						  <table align="Left" border="0" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                              <tr>
                                <td valign="top" class="textContent">
                                  <p><b>Complaint ID</b>: {{$info['id']}}</p>
                                  <p><b>Type</b>: {{$info['type']}}</p>
                                  <p><b>Incident Location</b>: {{$info['location']}}</p>
                                  <p><b>Guest Name</b>: {{$info['guest_name']}}</p>
                                  <p><b>Room</b>: {{$info['room']}}</p>
                                  <p><b>Category</b>: {{$info['category']}}</p>
                                  <p><b>Severity</b>: {{$info['severity']}}</p>
                                  <p><b>Created Date</b>: {{convertDateTime($info['created_at'])}}</p>
                                  
                                </td>
                              </tr>
                            </table>
                            <table align="Right" border="0" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                              <tr>
                                <td valign="top" class="textContentLast">
                                  <p><b>Status</b>: {{$info['status']}}</p>
                                  <p><b>Source</b>: {{$info['source']}}</p>
                                  <p><b>Incident Time</b>: {{convertDateTime($info['incident_time'])}}</p>
                                  <p><b>Guest Type</b>: {{$info['guest_type']}}</p>
                                  @if($info['guest_type']=="In-House" || $info['guest_type']=="Checkout") 
                                  <p><b>Stay</b>: {{converDate($info['arrival']) }} to {{converDate($info['departure'])}}</p>
                                  @endif
                                  <p><b>Sub-Category</b>: {{$info['subcategory']}}</p>
                                  <p><b>Raised By</b>: {{$info['raised_by']}}</p>
                                </td>
                              </tr>
                            </table></td>
                        </tr>
                      
			
             
                        <tr>
                          <td align="center" valign="top" width="600" class="flexibleContainerCell">
             <table border="0" cellpadding="0" cellspacing="0" width="100%">
                              <tr>
                                <td valign="top" class="textContent">
                                @if(!empty($info['department_tags']))
                                <p><b>Tagged Department</b>: {{$info['department_tags']}}</p>
                                @endif
                               <p><b>Guest Feedback</b>: {{$info['comment']}}</p>
                               <p><b>Intitial Response</b>: {{$info['intial_response']}}</p>
                               @if(!empty($info['solution']))
                               <p><b>Resolution</b>: {{$info['solution']}}</p>
                               @endif
                               @if(!empty($info['closed_comment']))
                               <p><b>Investigation</b>: {{$info['closed_comment']}}</p>
                               @endif

                        @if(!empty($info['comment_list']))
                        
                        <br><span style="font-size: 12px"><b>COMMENTS</b></span><br>
                        <table border="1%" style="width: 100%;">
                            <thead style="background-color:#ffffff">
                                <tr class = "plain">
                                    <th class="subtitle"><b>No</b></th> 
                                    <th class="subtitle"><b>Date</b></th>                           
                                    <th class="subtitle"><b>Comments</b></th>                            
                                    <th class="subtitle"><b>User</b></th> 
                                   
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $no = 1;
                                ?>
                                @foreach ($info['comment_list'] as $row1 )
                                <tr>
                                    <td align="center"><span></span>{{$no}}</td>
                                    <td align="center"><span>{{isCheckNull($row1->created_at)}}</span></td>
                                    <td align="center"><span>{{isCheckNull($row1->comment)}}</span></td>                            
                               
                                    <td align="center"><span>{{isCheckNull($row1->commented_by)}}</span></td>
                                    
                                </tr>
                                <?php
                                    $no++;  
                                ?>
                                @endforeach
                            </tbody>
                        </table>
                        @endif


                       


                @if(!empty($info['comp_list']))
                <br><span style="font-size: 12px"><b>SERVICE RECOVERY</b></span><br>
                <table border="1%" style="width: 100%;">
                    <thead style="background-color:#ffffff">
                        <tr class = "plain">
                            <th class="subtitle"><b>No</b></th> 
                            <th class="subtitle"><b>Date</b></th>                           
                            <th class="subtitle"><b>Compensation</b></th>                            
                       
                            <th class="subtitle"><b>Provided By</b></th> 
                            <th class="subtitle"><b>Amount({{$info['currency']}})</b></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $no = 1;
                            $total = 0;
                        ?>
                        @foreach ($info['comp_list'] as $row2 )
                        <tr>
                            <td align="center"><span></span>{{$no}}</td>
                            <td align="center"><span>{{isCheckNull($row2->created_at)}}</span></td>
                            <td align="center"><span>{{isCheckNull($row2->item_name)}}</span></td>                            
                       
                            <td align="center"><span>{{isCheckNull($row2->provider)}}</span></td>
                            <td align="right"><span>{{isCheckNull($row2->cost)}}</span></td>
                        </tr>
                        <?php
                            $no++;
                            $total = $total + $row2->cost;
                        ?>
                        @endforeach

                        
                        <tr>
                       
                            <td align="right" colspan = '4'><span><b>Total</span></b></td>
                       
                            <td align="right"><span><b> {{$info['currency']}} {{isCheckNull($total)}}</b></span></td>
                        </tr>
                    </tbody>
                </table>
                @endif




							   </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      
          
                     
            <tr>
                          <td align="center" valign="top" width="600" class="flexibleContainerCell">
             <table border="0" cellpadding="0" cellspacing="0" width="100%">
                              <tr>
                                <td valign="top" class="textContent">
                                  <br />
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      
        </td>
      </tr>
    </table>
	<div class="footer">
              <table border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td class="content-block">
                    <span class="apple-link">Ennovatech Solutions FZ LLC, Dubai UAE</span>
                 </td>
                </tr>
                </table>
            </div>
  </center>
</body>

</html>
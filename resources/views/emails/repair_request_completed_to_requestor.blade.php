<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

@include('emails.guestrequest_header')


  

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


<body>
    <center>
        <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
            <tr>
                <td align="center" valign="top" id="bodyCell">                
                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="emailBody">   
                    <div class="table-header" width="100%">
                        <div style="text-align:left;margin-top = '10px'; margin-bottom = '10px'; background-color: #FF8C00;" ><img src="https://ennovatech.com/assets/images/company-logo/hotlync.svg" width="200" height = "30"><br/>
                    </div>                   
                        <tr>
                            <td align="center" valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td valign="top" class="textContent">
                                            <br /> <p>Dear {{$info['name']}},</p>                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td valign="top" class="textContent">
                                        <br /> <p>Work Request on the {{$info['equip_name']}} has been completed.</p>                                            
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" align="Left" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                                    <td>
                                                        <p><b>Summary</b>: {{$info['summary']}}</p>
                                                    </td>    
                                                   
                                    <tr>
                                       <td valign="top" class="textContent">
                                       <p><b>Location</b>: {{$info['location']}}</p>
                                       <p><b>Priority</b>: {{$info['priority']}}</p>  
                                       <p><b>Category</b>: {{$info['category_name']}}</p>  
                                       <p><b>Sub Category</b>: {{$info['subcategory_name']}}</p>   
                                       <p><b>Completed By</b>: {{$info['assignee_name']}}</p>
                                                                        
                                        </td>
                                    </tr>                                    
                                </table>
                                <table border="0" align="Right" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                       <p><b>Equipment</b>: {{$info['equip_name']}}</p>
                                       <p><b>Scheduled Date</b>: {{date('d M Y H:i', strtotime($info['scheduled_date']))}}</p>
                                       <p><b>Start Date</b>: {{date('d M Y H:i', strtotime($info['start_date']))}}</p>
                                       <p><b>End Date</b>: {{date('d M Y H:i', strtotime($info['end_date']))}}</p>
                                       <p><b>Complete Comments</b>: {{$info['complete_comment']}}</p>
                                        </td>
                                    </tr>                                    
                                </table>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                           
                                       <p><b>Description</b>: {{$info['description']}}</p>
                                        </td>
                                    </tr>                                    
                                </table>
                            </td>
                        </tr>

<!-- // Start TABLE -->


                        <tr>
                          <td align="center" valign="top" width="600" class="flexibleContainerCell">
             <table border="0" cellpadding="0" cellspacing="0" width="100%">
                              <tr>
                                <td valign="top" class="textContent">
                              <p>If you want to close this request, please click the Close button. If you want to reopen it, please click the Reopen button.</p>
							   </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      
          
              <tr>
                          <td valign="top" width="600" class="flexibleContainerCell">
                            <table align="Left" border="0" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                             <tr>
                          <td align="center" valign="top" width="600" class="flexibleContainerCell bottomShim">

                            <table border="0" cellpadding="0" cellspacing="0" width="150" class="emailButton">
                              <tr>
                                <td align="center" valign="middle" class="buttonContent">
                                 <a style="display: block;color: #ffffff;font-size: 12px;text-decoration: none;text-transform: uppercase;" href="{{$info['ip']}}eng/sendmailrequest?property_id={{$info['property_id']}}&status=1&&id={{$info['id']}}" >
                                      Close
                                  </a> 
                                </td>
                              </tr>
                            </table>
                            <!-- // CONTENT TABLE -->


                          </td>
                        </tr>
                            </table>
                            <!-- // CONTENT TABLE -->


                            <!-- CONTENT TABLE // -->
                            <table align="Right" border="0" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                             <tr>
                          <td align="center" valign="top" width="600" class="flexibleContainerCell bottomShim">

                            <table border="0" cellpadding="0" cellspacing="0" width="150" class="emailButtonrej">
                              <tr>
                                <td align="center" valign="middle" class="buttonContent">
                                  <a style="display: block;color: #ffffff;font-size: 12px;text-decoration: none;text-transform: uppercase;" href="{{$info['ip']}}eng/sendmailrequest?property_id={{$info['property_id']}}&status=2&&id={{$info['id']}}">
                                      Reopen
                                  </a> 
                                
                                </td>
                              </tr>
                            </table>
                            <!-- // CONTENT TABLE -->


                          </td>
                        </tr>
                            </table>
                            <!-- // CONTENT TABLE -->


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
<!-- // END TABLE -->

                    </table>    
                </td>
            </tr>
        </table>
        
        @include('emails.guestrequest_footer')

    </center>
</body>

</html>
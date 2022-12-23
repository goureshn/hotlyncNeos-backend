<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width" />
  <title>HotLync Engineering Requests</title>

  

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
                                  <p>You have successfully created an Engineering Request on the DTDU eHelpdesk System with the following details :</p>
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
                                  <p>Subject: <b>{{$info['subject']}}</b></p>
                                  <p>Category: <b>{{$info['category']}}</b></p>
				   <p>Location: <b>{{$info['location']}}</b></p>


                                </td>
                              </tr>
                            </table>
                            <table align="Right" border="0" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                              <tr>
                                <td valign="top" class="textContentLast">
								                <p>Severity: <b>{{$info['severity']}}</b></p>
                                <p>Sub-Category: <b>{{$info['sub_cat']}}</b></p>
                                @if( !empty($info['assignee_name']) )
                                  <p>Assignee: <b>{{$info['assignee_name']}}</b></p>                                 
                                @endif                                                                  
                                </td>
                              </tr>
                            </table></td>
                        </tr>
                           <tr>
                          <td align="center" valign="top" width="600" class="flexibleContainerCell">
             <table border="0" cellpadding="0" cellspacing="0" width="100%">
                              <tr>
                                <td valign="top" class="textContent">
                              <p>Request: <b>{{$info['comment']}}</b></p>
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

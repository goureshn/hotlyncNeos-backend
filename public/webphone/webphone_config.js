/** 
* The WebPhone configuration settings can be specified below.
* The parameters can be also set at runtime by using the setparameter() function or by URL query parameters (prefixed with wp_ such as wp_username=xy).
* The serveraddress parameter is mandatory! If username/password is not set, then the user will have to enter them. Other parameters are optional.
* See the documentation "Parameters" chapter for the details regarding the available parameters: https://www.mizu-voip.com/Portals/0/Files/Webphone_Documentation.pdf
*/

webphone_api.parameters = {
    //serveraddress: '',  //!!!! MUST BE SET to your SIP server IP address, domain name or SRV DNS record (also set the :port if that is not the default 5060). Example: sip.myserveraddress.com:5070
	//proxyaddress: '', //Optional SIP proxy address. Specify only if you must use an outbound SIP proxy. 
	//transport: -1, //Optional SIP transport protocol. -1: auto detect, 0: UDP, 1: TCP, 2: TLS (usually doesn't have to be set)
    //username: '',  //Optional SIP account username (usually entered by the user, but for some special use cases or quick tests you might preset it here)
    //password: '',  //Optional SIP account password (usually entered by the user, but for some special use cases or quick tests you might preset it here)
    //Other parameters are optional. See the documentation for the complete list of available settings ("Parameters" chapter)
    loglevel: 1, //5 means detailed logs. It might be set it to 1 in production. The logs can be viewed in the browser console.
    //Note: comma is NOT needed after the last parameter
    autostart: 0
};

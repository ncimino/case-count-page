<?php

//$firstname is the first name of target
//$lastname is the last name of target
//$email is the targets email address
//$meeting_date is straight from a DATETIME mysql field and assumes UTC.
//$meeting_name is the name of your meeting
//$meeting_duretion is the duration of your meeting in seconds (3600 = 1 hour)


$meeting_duration = 24 * 3600;

if (sendIcalEmail("Nik C.","nikc@xilinx.com","2010-01-28 13:30:00pm MST","Phone Shift",$meeting_duration))
echo "mail sent";
else
echo "mail not sent";

function sendIcalEmail($firstname,$email,$meeting_date,$meeting_name,$meeting_duration) {

	$from_name = "My Name";
	$from_address = "myname@mydomain.com";
	$subject = "Meeting Booking"; //Doubles as email subject and meeting subject in calendar
	$meeting_description = "Here is a brief description of my meeting\n\n";
	$meeting_location = "My Office"; //Where will your meeting take place
	
	
	//Convert MYSQL datetime and construct iCal start, end and issue dates
	$meetingstamp = strtotime($meeting_date . " UTC");    
	//$dtstart= gmdate("Ymd\THis\Z",$meetingstamp);
	$dtstart= gmdate("Ymd",$meetingstamp);
	//$dtend= gmdate("Ymd\THis\Z",$meetingstamp+$meeting_duration);
	$dtend= gmdate("Ymd",$meetingstamp+$meeting_duration);
	$todaystamp = gmdate("Ymd\THis\Z");
	
	//Create unique identifier
	$cal_uid = date('Ymd').'T'.date('His')."-".rand()."@mydomain.com";
	
	//Create Mime Boundry
	$mime_boundary = "----Meeting Booking----".md5(time());
		
	//Create Email Headers
	$headers = "From: ".$from_name." <".$from_address.">\n";
	$headers .= "Reply-To: ".$from_name." <".$from_address.">\n";
	
	$headers .= "MIME-Version: 1.0\n";
	$headers .= 'Content-Type: text/calendar;name="meeting.ics";method=REQUEST\n';
//	$headers .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
	//$headers .= "Content-class: urn:content-classes:calendarmessage\n";
	
	//Create Email Body (HTML)
	$message .= "--$mime_boundary\n";
	$message .= "Content-Type: text/html; charset=UTF-8\n";
	$message .= "Content-Transfer-Encoding: 8bit\n\n";
	
	$message .= "<html>\n";
	$message .= "<body>\n";
	$message .= '<p>Dear '.$firstname.',</p>';
	$message .= '<p>Here is my HTML Email / Used for Meeting Description</p>';
	$message .= '<p><table><tr><td>table cell1</td><td></td><td>cell2</td></tr>';
	$message .= '<tr><td></td><td>this should be cell 3</td><td></td></tr></table></p>';    
	$message .= "</body>\n";
	$message .= "</html>\n";
	$message .= "--$mime_boundary\n";
	
	//Create ICAL Content (Google rfc 2445 for details and examples of usage) 
	$ical =    'BEGIN:VCALENDAR
PRODID:-//Microsoft Corporation//Outlook 11.0 MIMEDIR//EN
VERSION:2.0
METHOD:REQUEST
BEGIN:VEVENT
ORGANIZER:MAILTO:'.$from_address.'
DTSTART:'.$dtstart.'
DTEND:'.$dtend.'
LOCATION:'.$meeting_location.'
TRANSP:TRANSPARENT
SEQUENCE:0
UID:'.$cal_uid.'
DTSTAMP:'.$todaystamp.'
DESCRIPTION:'.$meeting_description.'
SUMMARY:'.$subject.'
PRIORITY:5
CLASS:PUBLIC
END:VEVENT
END:VCALENDAR';   
	
//	$ical = '
//BEGIN:VCALENDAR
//METHOD:REQUEST
//PRODID:Microsoft CDO for Microsoft Exchange
//VERSION:2.0
//BEGIN:VTIMEZONE
//TZID:(GMT-07.00) Mountain Time (US & Canada)
//X-MICROSOFT-CDO-TZID:12
//BEGIN:STANDARD
//DTSTART:16010101T020000
//TZOFFSETFROM:-0600
//TZOFFSETTO:-0700
//RRULE:FREQ=3DYEARLY;WKST=3DMO;INTERVAL=3D1;BYMONTH=3D11;BYDAY=3D1SU
//END:STANDARD
//BEGIN:DAYLIGHT
//DTSTART:16010101T020000
//TZOFFSETFROM:-0700
//TZOFFSETTO:-0600
//RRULE:FREQ=3DYEARLY;WKST=3DMO;INTERVAL=3D1;BYMONTH=3D3;BYDAY=3D2SU
//END:DAYLIGHT
//END:VTIMEZONE
//BEGIN:VEVENT
//DTSTAMP:20100128T063019Z
//DTSTART;TZID=3D"(GMT-07.00) Mountain Time (US & Canada)":20100127T000000
//SUMMARY:FW: Meeting Booking
//UID:20100127T233019-325123@mydomain.com
//ATTENDEE;ROLE=3DREQ-PARTICIPANT;PARTSTAT=3DNEEDS-ACTION;RSVP=3DFALSE;CN=3D"=
//\'nik.cim
// ino@gmail.com\'":MAILTO:nik.cimino@gmail.com
//ORGANIZER;X-SENTBYCN=3D"Nik Cimino";SENT-BY=3D"MAILTO:nikc@xilinx.com";CN=
//=3D"myna
// me@mydomain.com":MAILTO:myname@mydomain.com
//LOCATION:My Office
//DTEND;TZID=3D"(GMT-07.00) Mountain Time (US & Canada)":20100128T000000
//DESCRIPTION:\N\N-----Original Appointment-----\NFrom: myname@mydomain.com \=
//
// NSent: Wednesday\, January 27\, 2010 11:30 PM\NTo: myname@mydomain.com\; N=
//
// ik Cimino\NSubject: Meeting Booking\NWhen: Wednesday\, January 27\, 2010 1=
//
// 2:00 AM to Thursday\, January 28\, 2010 12:00 AM (GMT-07:00) Mountain Time=
//
//  (US & Canada).\NWhere: My Office\N\N\N12  23  34  45  \N23  34  45  56  \N\N
//SEQUENCE:0
//PRIORITY:5
//CLASS:
//CREATED:20100128T063414Z
//LAST-MODIFIED:20100128T063415Z
//STATUS:CONFIRMED
//TRANSP:OPAQUE
//X-MICROSOFT-CDO-BUSYSTATUS:BUSY
//X-MICROSOFT-CDO-INSTTYPE:0
//X-MICROSOFT-CDO-REPLYTIME:20100128T063000Z
//X-MICROSOFT-CDO-INTENDEDSTATUS:BUSY
//X-MICROSOFT-CDO-ALLDAYEVENT:FALSE
//X-MICROSOFT-CDO-IMPORTANCE:1
//X-MICROSOFT-CDO-OWNERAPPTID:-1
//X-MICROSOFT-CDO-APPT-SEQUENCE:0
//X-MICROSOFT-CDO-ATTENDEE-CRITICAL-CHANGE:20100128T063404Z
//X-MICROSOFT-CDO-OWNER-CRITICAL-CHANGE:20100128T063019Z
//END:VEVENT
//END:VCALENDAR';
	
//  $ical = 'BEGIN:VCALENDAR
//METHOD:REQUEST
//PRODID:Microsoft CDO for Microsoft Exchange
//VERSION:2.0
//BEGIN:VTIMEZONE
//TZID:(GMT-07.00) Mountain Time (US & Canada)
//X-MICROSOFT-CDO-TZID:12
//BEGIN:STANDARD
//DTSTART:16010101T020000
//TZOFFSETFROM:-0600
//TZOFFSETTO:-0700
//RRULE:FREQ=3DYEARLY;WKST=3DMO;INTERVAL=3D1;BYMONTH=3D11;BYDAY=3D1SU
//END:STANDARD
//BEGIN:DAYLIGHT
//DTSTART:16010101T020000
//TZOFFSETFROM:-0700
//TZOFFSETTO:-0600
//RRULE:FREQ=3DYEARLY;WKST=3DMO;INTERVAL=3D1;BYMONTH=3D3;BYDAY=3D2SU
//END:DAYLIGHT
//END:VTIMEZONE
//BEGIN:VEVENT
//DTSTAMP:20100128T050740Z
//DTSTART;TZID=3D"(GMT-07.00) Mountain Time (US & Canada)":20100127T000000
//SUMMARY:subject
//UID:'.rand().'
//ATTENDEE;ROLE=3DREQ-PARTICIPANT;PARTSTAT=3DNEEDS-ACTION;RSVP=3DTRUE;CN=3D"\'=
//nik.cimi
// no@gmail.com\'":MAILTO:nik.cimino@gmail.com
//ORGANIZER;CN=3D"Nik Cimino":MAILTO:nikc@xilinx.com
//LOCATION:loc
//DTEND;TZID=3D"(GMT-07.00) Mountain Time (US & Canada)":20100128T000000
//DESCRIPTION:descr\N
//SEQUENCE:0
//PRIORITY:5
//CLASS:
//CREATED:20100128T050742Z
//LAST-MODIFIED:20100128T050743Z
//STATUS:CONFIRMED
//TRANSP:OPAQUE
//X-MICROSOFT-CDO-BUSYSTATUS:FREE
//X-MICROSOFT-CDO-INSTTYPE:0
//X-MICROSOFT-CDO-INTENDEDSTATUS:FREE
//X-MICROSOFT-CDO-ALLDAYEVENT:TRUE
//X-MICROSOFT-CDO-IMPORTANCE:1
//X-MICROSOFT-CDO-OWNERAPPTID:-1912924198
//X-MICROSOFT-CDO-APPT-SEQUENCE:0
//X-MICROSOFT-CDO-ATTENDEE-CRITICAL-CHANGE:20100128T050740Z
//X-MICROSOFT-CDO-OWNER-CRITICAL-CHANGE:20100128T050740Z
//END:VEVENT
//END:VCALENDAR';
	
	$message .= 'Content-Type: text/calendar;name="meeting.ics";method=REQUEST\n';
	$message .= "Content-Transfer-Encoding: 8bit\n\n";
	$message = $ical;            
	
	//SEND MAIL
	$mail_sent = @mail( $email, $subject, $message, $headers );
	
	if($mail_sent)     {
		return true;
	} else {
		return false;
	}   

}

?>
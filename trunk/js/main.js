function confirmSubmit(msg)
{
var agree=confirm(msg);
if (agree)
	return true ;
else
	return false ;
}

function submitenter(thisfield,thisevent)
{
    var keycode;
    if (window.event) keycode = window.event.keyCode;
    else if (thisevent) keycode = thisevent.which;
    else return true;

    if (keycode == 13)
    {
        thisfield.form.submit();
        return false;
    }
    else
        return true;
}

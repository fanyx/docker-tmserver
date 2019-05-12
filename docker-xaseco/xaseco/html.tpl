<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html><head>
<meta http-equiv = "Content-Type" content = "text/html;charset = UTF-8" />
<title>Race Results for {DATE} - {TIME} on {TRACK}</title>
<style type="text/css">
body {
background:#CCCCCC;
color:##DBE1DD;
margin-left: 10px;
margin-right: 10px;
margin-top:5px;
padding: 1px 1px 1px 1px;
font-family: sans-serif;
font-size: 80%;
}
</style></head><body>
{HEADER}
<p><table>
<tr><td>Track: {TRACK}</td><td>{DATE} - {TIME}</td></tr></table>
<table><tr><th>Rank</th><th>Name</th><th>Time</th><th>Team</th><th>Points</th></tr>
<!-- Player Data Begin -->
<tr><td>{RANK}</td><td>{NICK}</td><td>{TIME}</td><td>{TEAM}</td><td>{POINTS}</td></tr>
<!-- Player Data End -->
</table></p><p><table><tr><th>Team</th><th>Total</th>{MATCHCELL}</tr>
<!-- Team Data Begin -->
<tr><td>{TEAM}</td><td>{POINTS}</td>{MATCHPOINTS}</tr>
<!-- Team Data End -->
</table></p></body></html>

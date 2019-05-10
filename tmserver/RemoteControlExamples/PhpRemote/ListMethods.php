<?php
header('Content-Type: text/html; charset=utf-8');

require('GbxRemote.inc.php');

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">\n";
echo "<HTML>\n<HEAD>\n";
echo "\t<TITLE>Trackmania methods</TITLE>\n";
echo "</HEAD>\n<BODY>\n";

echo "<h1>Available methods:</h1>\n";

$client = new IXR_Client_Gbx;

if (!$client->Init(5000)) {
	die('An error occurred - ' . $client->getErrorCode() . ':' . $client->getErrorMessage());
}

if (!$client->query('system.listMethods')) {
	die('An error occurred - ' . $client->getErrorCode() . ':' . $client->getErrorMessage());
}
$methods = $client->getResponse();

print '<ul>';
foreach ($methods as $m) {
	print '<li><b>' . $m . "</b><br/>\n";
	if ($client->query('system.methodSignature', $m)) {
		$signatures = $client->getResponse();
	} else {
		print "<font color='red'>{error in signature}</font><br/>\n";
		$signatures = array();
	}
	if ($client->query('system.methodHelp', $m)) {
		$help = $client->getResponse();
	} else {
		$help = "<font color='red'>{no help}</font>";
	}

	foreach ($signatures as $sig) {
		$is_retval = 1;
		$is_firstarg = 1;
		foreach ($sig as $argtype) {
			if ($is_retval) {
				print $argtype . ' ' . $m . '(';
				$is_retval = 0;
			} else {
				if (!$is_firstarg) {
					print ', ';
				}
				print $argtype;
				$is_firstarg = 0;
			}
		}
		print ")<br/>\n";
	}
	print "<font color='0x113355'>";
	print $help;
	print "</font></li>\n";
}
print '</ul>';

$client->Terminate();

echo "\n</BODY>\n</HTML>\n";

flush();

?>

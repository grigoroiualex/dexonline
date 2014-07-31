<?php
// index.php



require('config.php');
require('generatestatdata.php');
require('importstatfiledata.php');
require('buildjsonstructure.php');



$generatestatdata = new generatestatdata();
$generatestatdata->execute();
?>
<!DOCTYPE html>
<html>
<head>
	<title>SysStat Graph</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<script type="text/javascript" src="sysstatgraph.js"></script>
	<script type="text/javascript" src="rendergraph.js"></script>
	<script type="text/javascript" src="tocbox.js"></script>
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>

<body>

	<h1>SysStat Graph</h1>

	<div id="content"></div>

	<p>Generated by <a href="http://magnetikonline.com/sysstatgraph/">SysStat Graph Version 0.4</a></p>

	<script type="text/javascript">
	sysstatgraph.statdata = <?php echo((is_file(JSONSTRUCTUREFILENAME)) ? file_get_contents(JSONSTRUCTUREFILENAME) : '{}'); ?>;
	sysstatgraph.init();
	</script>

</body>
</html>

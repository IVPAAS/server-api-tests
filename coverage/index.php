<?php
/**
 * Created by IntelliJ IDEA.
 * User: noam.arad
 * Date: 7/26/2015
 * Time: 10:57 AM
 */
?>

<html>
<head>
	<title>See test coverage</title>
	<script src="http://192.168.193.88/admin_console/js/jquery-1.8.3.js"></script>
	<script type="text/javascript">
		function getAndSetCoverage()
		{
			$.ajax({
				url: "getCoverage.php",
				type: 'POST',
				success: function(res, status, xhrData ) {
					var tableTxt = "";
					for (var currService in res)
					{
						tableTxt += "<tr><td>"+currService+"</td>";
						for (var currAction in res[currService])
						{
							tableTxt += "<td style='background:";
							if (res[currService][currAction]['counter'] > 0)
							{
								tableTxt += "lightgreen";
							}
							else
							{
								tableTxt += "yellow";
							}
							tableTxt += "'>"+currAction+" : "+res[currService][currAction]['counter']+"</td>"
						}
						tableTxt +="</tr>";
					}
					$("#test-coverage-table").html(tableTxt);
				}
			});
			window.setTimeout('getAndSetCoverage()',5000);
		}
	</script>
	<style>
		table{
			border:1px Solid red;
		}
		tr{
			border:1px Solid green;
		}
		td{
			border:1px Solid orange;
		}
	</style>
</head>
<body onLoad="getAndSetCoverage()">
	Hello world!
	<div id="test-coverage-div">
		<table id="test-coverage-table">
			<tr id="test-coverage-first-row">
				<td>Service/Action</td>
			</tr>
		</table>
	</div>
</body>
</html>

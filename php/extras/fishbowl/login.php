<?php
	require_once("applicationTop.php");
	
	if (isset($_POST['username'])) {
    	$fbapi->Login($_POST['username'], $_POST['password']);

		if ($fbapi->statusCode != 1000) {
			echo "Login Failed";
		} else {
	    	$_SESSION['username'] = $_POST['username'];
	    	$_SESSION['password'] = $_POST['password'];
			echo "Login Successful";
		}
    }
?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<table cellspacing="2" cellpadding="3" border="0" class="orange_row">
		<tbody>
			<tr>
				<td class="formTitle">Username</td>
				<td><input type="text" name="username" id="username" size="40" /></td>
			</tr>
			<tr>
				<td class="formTitle">Password</td>
				<td><input type="password" name="password" id="password" size="40" /></td>
			</tr>
			<tr>
				<td colspan="2"><center><input type="submit" name="submit" value="Submit" /></center></td>
			</tr>
		</tbody>
	</table>
</form>
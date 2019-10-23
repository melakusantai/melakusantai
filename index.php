<?php
/*
 * e-academy ELMS Integrated User Verification
 */

session_start();
$_error = '';

if (isset($_POST['submit']) && $_POST['submit'] == 'Log In') 
{
	if (!isset($_POST['ccid']) || $_POST['ccid'] == '' || !isset($_POST['passwd']) || $_POST['passwd'] == '') 
	{
		$_error = '<p><font color="red">Authentication is Required.</font></p>';
	} 
	else 
	{
		$useUnsafeDemoBypass = 0;
		if ($useUnsafeDemoBypass == 1)
		{
			// for testing purposes, hard-code a success in order to test the handshake below
			// in a production implementation, this block should be removed and the following else block
			// should to be modified appropriately according to organization's implementation.

			$_SESSION['auth_ccid'] = $_POST['ccid'];
			$_SESSION['account_number'] = '100102147';
			$_SESSION['auth_email'] = 'indragunawan@hendrikus.sch.id';
			$_SESSION['first_name'] = 'Indra';
			$_SESSION['last_name'] = 'Gunawan';
			$_SESSION['academic_statuses'] = 'faculty';
			$_error = '';

			session_write_close();
		}
		else
		{
			// this block is a SAMPLE of how you may implement in production, although
			// the specific details will vary by implementation and must be determined
			// by the organization owning the verification engine.

			// set some sample but unusable values for demonstration purposes; fill in with real values
			$db_server_name = 'localhost';
			$db_port = '3306';
			$db_name = 'id9578657_dbserver';
			$db_user_name = 'id9578657_admin';
			$db_password = 'admin';

			$conn = new mysqli($db_server_name, $db_user_name, $db_password, $db_name) 
				or die("Connection failed: " . $conn->connect_error);

			$query = "select username,password,account_number,auth_email,last_name,first_name,academic_statuses from user where username = \"" . $_POST['ccid']. "\" and password = \"". $_POST['passwd']."\"";

			$result = $conn->query($query);
			
			$isVerified = 0; // set to 1 on successful verification
			
			if($conn->error){
			    printf("errormessage: %s\n", $conn->error);
			    
			}
			
			$num = $result->num_rows;

			if ($num == 1) 
			{
				$found_person;
				
				while($row = $result->fetch_assoc()) {
					$found_person = $row;
				}
				//$stored_password = $found_person[0];
				//$passwd = stripslashes($passwd);
				//$encrypted = crypt($passwd, substr($stored_password, 0, 2));
				if ($_POST['passwd'] == $found_person['password'])
				{
					$isVerified = 1;
					$_SESSION['auth_ccid'] = $_POST['ccid'];

					// fill the remaining in as required based on your organization's implementation
					$_SESSION['account_number'] = $found_person['account_number'];
					$_SESSION['auth_email'] = $found_person['auth_email'];		
					$_SESSION['last_name'] = $found_person['last_name'];
					$_SESSION['first_name'] = $found_person['first_name'];
					$_SESSION['academic_statuses'] = $found_person['academic_statuses'];
					session_write_close();
				} 
			}

			if ($isVerified == 0)
			{
				$_error = '<p><font color="red">Authentication Failed. Please try again.</font></p>';
			}
			
			$conn->close();
		}
	}
}

// user has been authenticated - handshake
if (isset($_SESSION['auth_ccid'])) 
{
	ob_start();

	$key = 'b1f61a33'; // replace with your webstore key
	$host = 'https://e5.onthehub.com/WebStore/Security/AuthenticateUser.ashx';

	// Build query body
	$data = array(
		'username'			=> $_SESSION['auth_ccid'],
		'account'			=> $_SESSION['account_number'],
		'email'				=> $_SESSION['auth_email'],
		'first_name'		=> $_SESSION['first_name'],
		'last_name'			=> $_SESSION['last_name'],
		'academic_statuses'	=> $_SESSION['academic_statuses'],
	//	'shopper_ip'		=> $_SERVER['REMOTE_ADDR'],
		'key'				=> $key
	);
	$options = array('http' =>
		array(
			'method'	=> 'POST',
			'header'	=> 'Content-type: application/json',
			'content'	=> json_encode($data)
		)
	);

	$context = stream_context_create($options);
	$e5LoginRedirectURL = file_get_contents($host, false, $context);

	// check response status code
	$http_status = $http_response_header[0];
	if (strpos($http_status, '200 OK') === false)	// NOTE $http_status may not always be $http_response_header[0]
	{
		// we have an error
		echo '<p><b>A handshake error has occured</b></p>';
		echo '<p><b>Response received:<br><font color="red">'.$http_status.'</font></b></p>';
	}
	else if (strlen($e5LoginRedirectURL) == 0)
	{
		// HTTP status code was OK but server didn't return a redirect URL; may be some other error, such as incorrectly configured server IP for your server
		echo '<p><b>A handshake error has occured; invalid redirection URL</b></p>';
	}
	else 
	{
		// status code looks good and we have a redirection URL; set redirect location in header
		header('Location: '.$e5LoginRedirectURL);
	}

	// clear session variables and destroy
	$_SESSION=array();
	if(isset($_COOKIE[session_name()])) 
	{
		setcookie(session_name(),'',time()-42000,'/');
	}
	@session_destroy();
	ob_end_flush();
	exit;
}

// unauthenticated user - show login
?>
<html>
<head>
<title>Login</title>
</head>

<div align="center">
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<?php echo $_error; ?><br />
		<p>Username<br />
		<input type="text" name="ccid" size="15" /></p>
		<p>Password:<br />
		<input type="password" name="passwd" size="15" /></p>
		<input type="submit" name="submit" value="Log In" /> 
		<input type="reset" name="reset" value="Clear" />
	</form>
</div>

</div>
</body>
</html>
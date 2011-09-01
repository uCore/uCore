<?php

class tabledef_Session extends uTableDef {
	public function SetupFields() {
		// add all the fields in here
		// AddField($name, $type, $length, $collation='', $attributes='', $null='not null', $default='', $extra='', $comments='')
		// SetPrimaryKey($name);

		$this->AddField('ses_id','varchar',32);
		$this->AddField('ses_time','timestamp',0);
		$this->AddField('ses_start','timestamp',0);
		$this->AddField('ses_value',ftTEXT);
		$this->AddField('remote_ip','varchar',15);
		$this->AddField('current_module','varchar','50');
		$this->AddField('current_page',ftTEXT);
		$this->SetPrimaryKey('ses_id',false);
	}
}

//session_start();

/* Create new object of class */
//$ses_class = new SessionHandler();

/* Change the save_handler to use the class functions */
//session_set_save_handler ('ses_open', 'ses_close', 'ses_read', 'ses_write', 'ses_destroy', 'ses_gc');

/* Start the session */
//session_start();

/* Open session, if you have your own db connection
 code, put it in here! */
function ses_open($path, $name) {
	fdb_sql_connect();
	return TRUE;
}

/* Close session */
function ses_close() {
	/* This is used for a manual call of the
	 session gc function */
	ses_gc();
	return TRUE;
}

/* Read session data from database */
function ses_read($ses_id) {
	$session_sql = "SELECT * FROM internal_sessions"
	. " WHERE ses_id = '$ses_id'";
	$session_res = @sql_query($session_sql);
	if (!$session_res) {
		return '';
	}

	$session_num = @mysql_num_rows ($session_res);
	if ($session_num > 0) {
		$session_row = mysql_fetch_assoc ($session_res);
		$ses_data = $session_row["ses_value"];
		return $ses_data;
	} else {
		return '';
	}
}

/* Write new data to database */
function ses_write($ses_id, $data) {
	$cp_up = ''; $title_up = ''; $title = ''; $current_page = '';
	if (stristr($_SERVER['REQUEST_URI'],'__ajax=') === FALSE && GetCurrentModule() !== '') {
		$current_page = $_SERVER['REQUEST_URI'];
		$obj = utopia::GetInstance(GetCurrentModule());
		$title = $obj->GetTitle();
		$cp_up = ", current_page='$current_page'";
		$title_up = ", current_module='$title'";
	}
	$remoteip = $_SERVER['REMOTE_ADDR'];
	$data = mysql_escape_string($data);


	$session_sql = "SELECT * FROM internal_sessions WHERE ses_id = '$ses_id'";
	$exists = mysql_num_rows(sql_query($session_sql));

	if ($exists) {
		$session_sql = "UPDATE internal_sessions SET ses_time=NOW(), ses_value='$data', remote_ip='$remoteip'$title_up$cp_up WHERE ses_id='$ses_id'";
		$session_res = @sql_query ($session_sql);
	} else {
		$session_sql = "INSERT INTO internal_sessions"
		. " (ses_id, ses_start, ses_time, ses_value, remote_ip, current_module, current_page)"
		. " VALUES ('$ses_id', NOW(), NOW(), '$data', '$remoteip', '$title', '$current_page')";
		$session_res = @sql_query ($session_sql);
	}
	if (!$session_res) return FALSE;

	return TRUE;
}

/* Destroy session record in database */
function ses_destroy($ses_id) {
	$session_sql = "DELETE FROM internal_sessions"
	. " WHERE ses_id = '$ses_id'";
	$session_res = @sql_query ($session_sql);
	if (!$session_res) {
		return FALSE;
	}         else {
		return TRUE;
	}
}

/* Garbage collection, deletes old sessions */
function ses_gc($maxlifetime = NULL) {
	if ($maxlifetime == NULL)
	$ses_life = strtotime('-30 minutes'); // NOTE: On Kev's head.
	else
	$ses_life = time() - $maxlifetime;

	$session_sql = "DELETE FROM internal_sessions"
	. " WHERE UNIX_TIMESTAMP(`ses_time`) < '$ses_life'";
	$session_res = @sql_query ($session_sql);

	sql_query('OPTIMIZE TABLE sessions');

	if (!$session_res) {
		return FALSE;
	}         else {
		return TRUE;
	}
}

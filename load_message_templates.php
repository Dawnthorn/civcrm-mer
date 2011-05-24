<?php

/*
Copyright (C) 2011 Giant Rabbit LLC

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU Affero General Public License as published by the Free
Software Foundation, either version 3 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

chdir("../../../..");
require_once "./includes/bootstrap.inc";
require_once "./includes/database.inc";
require_once "./includes/database.mysql.inc";
require_once "./sites/default/civicrm.settings.php";

function setup_db()
{
  global $active_db;
  $active_db = db_connect(CIVICRM_DSN);
}

function create_workflow()
{
  $query = <<<EOS
	SELECT
	  id
	FROM
	  civicrm_option_value
	WHERE
	  option_group_id = 48
	AND
	  name = 'event_registration_receipt'
EOS;
  $result = db_query($query);
  $workflow_id = db_result($result);
  if ($workflow_id === FALSE)
  {
	$query = <<<EOS
	  SELECT
		MAX(value)
	  FROM
		civicrm_option_value
	  WHERE
		option_group_id = 48
EOS;
	$result = db_query($query);
	$max_value = db_result($result);
	$new_value = $max_value + 1;
	$statement = <<<EOS
	  INSERT INTO
		civicrm_option_value
	  (
		option_group_id,
		label,
		value,
		name,
		is_default,
		weight,
		is_optgroup,
		is_reserved,
		is_active
	  )
	  VALUES
	  (
		48,
		'Events - Registration Receipt',
		$new_value,
		'event_registration_receipt',
		0,
		2,
		0,
		0,
		1
	  )
EOS;
	db_query($statement);
	$workflow_id = db_last_insert_id('civicrm_option_value', 'id');
  }
  return $workflow_id;
}

function generate_template_insert($subject, $text, $html, $workflow_id, $is_default, $is_reserved)
{
  return <<<EOS
	INSERT INTO
	  civicrm_msg_template
	(
	  msg_title,
	  msg_subject,
	  msg_text,
	  msg_html,
	  is_active,
	  workflow_id,
	  is_default,
	  is_reserved
	)
	VALUES
	(
	  'Events - Registration Receipt',
	  '$subject',
	  '$text',
	  '$html',
	  1,
	  $workflow_id,
	  $is_default,
	  $is_reserved 
	)
EOS;
}

function load_message_template($workflow_id)
{
  global $active_db;
  $base_template_path = "sites/all/modules/civicrm_mer/templates/messages";
  $base_template_name = "event_registration_receipt_";
  $statement = <<<EOS
	DELETE FROM civicrm_msg_template WHERE workflow_id = $workflow_id;
EOS;
  db_query($statement);
  $subject = db_escape_string(read_template_file($base_template_path . "/" . $base_template_name . "subject.tpl"));
  $html = db_escape_string(read_template_file($base_template_path . "/" . $base_template_name . "html.tpl"));
  $text = db_escape_string(read_template_file($base_template_path . "/" . $base_template_name . "text.tpl"));
  $statement = generate_template_insert($subject, $text, $html, $workflow_id, 0, 1);
  mysql_query($statement, $active_db);
  $statement = generate_template_insert($subject, $text, $html, $workflow_id, 1, 0);
  mysql_query($statement, $active_db);
}

function read_template_file($file_path)
{
  $file = fopen($file_path, "r");
  $contents = fread($file, filesize($file_path));
  fclose($file);
  return $contents;
}

setup_db();
$workflow_id = create_workflow();
load_message_template($workflow_id);


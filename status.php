<?php
//
// Drupal status check
//
// Original by [Lullabot](http://www.lullabot.com/articles/varnish-multiple-web-servers-drupal)
// Adapted for DrupalCONCEPT by Jochen Lillich <jochen@freistil-consulting.de>

define('DRUPAL_ROOT', getcwd());

// Register our shutdown function so that no other shutdown functions run before this one.
// This shutdown function calls exit(), immediately short-circuiting any other shutdown functions,
// such as those registered by the devel.module for statistics.
register_shutdown_function('status_shutdown');
function status_shutdown() {
  exit();
}

// Drupal bootstrap.
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);

// Build up our list of errors.
$errors = array();

// Check that the main database is active.
$account = db_select('users','u')
        ->fields('u')
        ->condition('uid',1,'=')
        ->execute()
        ->fetchObject();

if (!$account->uid == 1) {
  $errors[] = 'Master database not responding.';
}

// Check that the files directory is operating properly.
if ($test = tempnam(variable_get('file_directory_path', conf_path() . '/files'), 'status_check_')) {
  if (!unlink($test)) {
    $errors[] = 'Could not delete newly create files in the files directory.';
  }
}
else {
  $errors[] = 'Could not create temporary file in the files directory.';
}

// Print all errors.
if ($errors) {
  $errors[] = 'Errors on this server will cause it to be removed from the load balancer.';
  header('HTTP/1.1 500 Internal Server Error');
  print implode("<br />\n", $errors);
}
else {
  // Split up this message, to prevent the remote chance of monitoring software
  // reading the source code if mod_php fails and then matching the string.
  print '200' . ' OK ' . time();
}

// Make sure this response is never cached.
drupal_page_is_cacheable(FALSE);

// Exit immediately, note the shutdown function registered at the top of the file.
exit();

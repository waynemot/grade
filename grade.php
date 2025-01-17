<?php

include_once("GradeInc.php");

$hwNum  = $_POST["hw_num"];

$host   = isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : "";
$ip     = $_SERVER['REMOTE_ADDR'];

$username_for_ticket = $login = getUsername();

$roster          = Roster::createRoster($config['mail_file']);
$student         = findStudentByLogin($roster->getStudents(), $login);
$handinHome      = $config["webhandin_relative_path"];
$gradeDir        = "$handinHome/$hwNum/";
$gradeScript     = $config["script_name"];
$gradeScriptPath = "$gradeDir$gradeScript";
$userDir         = "$handinHome/$hwNum/$login";
$timeout         = $config['global_timeout'];


if ($username_for_ticket === "TIMED_OUT_USER") {
  gradeLog("UNAUTHORIZED ATTEMPT - EXPIRED TICKET: $login $hwNum");
  $result = getBootstrapDiv("Expired Session", "<a onclick=\"window.location.href = window.location.href.split('?')[0].replace(/\/$/, '')\">Click here to reset</a>");
} else if($student === null) {
  //student is not in the roster file
  $result = getBootstrapDiv("User Not Enrolled", "Your username does not appear to be enrolled in this course.");
} else if(!file_exists($gradeScriptPath)) {
  //grade script does not exist
  $result = getBootstrapDiv("Error",
                            "Grade script does not appear to have been setup, your " .
                            "instructor either screwed up or didn't care enough to " .
                            "do so.  Oh well.");
} else if(!file_exists($userDir)) {
  //user handin directory does not exist because they didn't hand anything in
  $result = getBootstrapDiv("Error", "You gotta hand something into the CSE Handin first, noob.");
} else if(!file_exists($gradeDir)) {
  //grade directory does not exist
  $result = getBootstrapDiv("Error", "Internal Error Occurred (grade directory does not exist)");
} else {
  //all is good, go ahead and grade
  gradeLog("GRADE SUBMISSION: $login $hwNum");
  chdir($gradeDir);
  $cmd = "timeout $timeout ./$gradeScript " . escapeshellarg($login) . " 2>&1";
  system($cmd, $exitCode);
  if($exitCode === 124) {
    //by default, timeout results in an exit code of 124, so 
    //we give them a different message:
    $result = getBootstrapDiv("Error", "Program(s) timed out.  You may have an infinite loop or extremely inefficient program.");
  }
  //else, it is the script's responsibility to produce/print
  //output
}
print $result;

?>



<?php

// one of these prefixes must be used in the commit message to describe the nature of the change.
$allowedPrefixes = array('[BUGFIX]', '[FEATURE]', '[TASK]', '[DOC]');
// commit message (subject and body combined) must exceed this length. Prevents nonsense.
define('REQUIRED_LENGTH', 6);
// allows the special admin [TER] prefix but does not process it
define('TER_PREFIX', '[TER]');
// a space character (only spaces, no other whitespace allowed) must follow right after the prefix
define('MUST_USE_SPACE_AFTER_PREFIX', TRUE);
// checks that the subject string (starting after prefix and space) does NOT start with a lowercase alphabetic character
define('MUST_USE_VERSAL_SUBJECT', TRUE);
// check this many recent commits in the history
define('HISTORY_LENGTH', 25);

if (TRUE === isset($argv[1])) {
	$path = realpath($argv[1]);
} else {
	$path = trim(shell_exec('pwd'));
}
$logOutput = trim(shell_exec('cd ' . escapeshellarg($path) .' && git log -n ' . HISTORY_LENGTH . ' --no-merges'));
$commits = explode("\n\ncommit ", $logOutput);
$erroneousCommitsInHistory = FALSE;
foreach ($commits as $commit) {
	$sha1 = substr($commit, 0, 32);
	$body = array_slice(explode("\n", $commit), 3);
	$body = trim(implode("\n", $body));
	// checking consistency
	if (TER_PREFIX === substr($body, 0, strlen(TER_PREFIX))) {
		// commit is a TER release commit, skip it.
		continue;
	}
	if (REQUIRED_LENGTH >= strlen($body)) {
		$erroneousCommitsInHistory = TRUE;
		print "Commit message for '" . $sha1 . "' is too short (less than " . REQUIRED_LENGTH . " characters)\n";
	}
	$hasRequiredPrefix = FALSE;
	foreach ($allowedPrefixes as $prefix) {
		if ($prefix === substr($body, 0, strlen($prefix))) {
			$hasRequiredPrefix = TRUE;
			$trimmed = substr($body, strlen($prefix));
			$firstCharacter = substr($trimmed, 0, 1);
			if (MUST_USE_SPACE_AFTER_PREFIX && ' ' !== $firstCharacter) {
				$erroneousCommitsInHistory = TRUE;
				print "Commit message for '" . $sha1 . "' does not contain a space after the prefix (example: '[TASK] Subject header line')\n";
			} else {
				$firstCharacter = substr($trimmed, 1, 1);
			}
			if (MUST_USE_VERSAL_SUBJECT && strtoupper($firstCharacter) !== $firstCharacter) {
				$erroneousCommitsInHistory = TRUE;
				print "Commit message for '" . $sha1 . "' uses a lowercase starting letter, please use a versal (example: 'This subject is valid')\n";
			}
		}
	}
	if (FALSE === $hasRequiredPrefix) {
		if (TRUE === strpos("\n", $trimmed)) {
			$firstLine = array_shift(explode("\n", $trimmed));
		} else {
			$firstLine = $trimmed;
		}
		$erroneousCommitsInHistory = TRUE;
		print "Commit message for '" . $sha1 . "' does not have a required prefix (expected: " . implode(', ', $allowedPrefixes) . ", actual: " . $firstLine . ")\n";
	}
}

if (TRUE === $erroneousCommitsInHistory) {
	print "We detected problems with your commit message(s) - please check that they conform to the\n";
	print "required standards, which you can at any time read online at:\n";
	print "\n";
	print "http://fedext.net/overview/contributing/contribution-guide.html\n";
	exit(1);
}
exit(0);

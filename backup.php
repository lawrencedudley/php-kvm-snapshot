#!/usr/bin/php
<?php
// This script runs a snapshot of arg1 (arg1 being a VM name listed in 'virsh --list all') and pops the parent snapshot into the backup dir set below (usually /mnt/backups in our case)


// Set backuop directory
$backupDirectory = '/mnt/backups';

// Set timezone for date/timestamps:
date_default_timezone_set('Europe/London');

// Grab the first argument from the command line
$virtualMachine = $argv['1'];

// Check for an argument passed on the commmand line
if (empty($virtualMachine)) {
    echo "You need to specify the name of a virtual machine or this script will not know what you want to backup \n";
    exit();
}

// Let the terminal know which VM we're backing up

echo "\n OK, I'm backing up $virtualMachine for you...\n \n";

// Set our timestamp for the rest of the script
$timeStamp = date('omd-Gis');

$commandOutput = shell_exec("virsh snapshot-create-as $virtualMachine bimg-$timeStamp --no-metadata --disk-only --atomic");

echo "$commandOutput \n";

$commandOutput = shell_exec("virsh -r domblklist $virtualMachine | awk '/^[shv]d[a-z][[:space:]]+/ {print $2}'");

echo "Found the following block devices: \n $commandOutput \n";

// Turn the list of block devices into an array

$blockDevices = explode("\n", $commandOutput);

// Clean up the array and remove any blanks - we don't want these!

$blockDevices = array_filter($blockDevices);

// Remove any lines in the array that are a '-' as these are basically a drive (say a cd-rom) that doesn't have any media in it

$filter = "-";

$blockDevices = array_filter($blockDevices, function ($element) use ($filter) { return ($element != $filter); } ); 

foreach ($blockDevices as $blockDevice) {
	// Get backing file of block device after we've snapshotted it
	$backingFile = shell_exec("qemu-img info $blockDevice | awk '/^backing file: / { print $3 }'");

	// Strip out the new lines in the name - these cause issues with later shell commands
	$backingFile = str_replace(array("\n", "\r"), '', $backingFile);

	// Echo out the name of the backing file. Might wanna do something with it sometime.
	echo "Found backing file: $backingFile \n";

	// Get the basename of the backing file - we use this for our copy to the backup directory
	$baseName = basename("$backingFile");

	// Copy the backing file to the backing directory
	shell_exec("cp $backingFile $backupDirectory/$baseName");

	// Get the name of the parent backing file
	$parentBackingFile = shell_exec("qemu-img info $backupDirectory/$baseName | awk '/^backing file: / { print $3 }'");

	// Check that the snapshot we just backed up actually has a parent backing file - if it doesn't, it's likely to be the first backup in this chain
	if ($parentBackingFile != "")
	{
		$parentBackingFile = str_replace(array("\n", "\r"), '', $parentBackingFile);
		$parentBackingFile = basename($parentBackingFile);
		shell_exec("qemu-img rebase -u -b $parentBackingFile $backupDirectory/$baseName");
		echo "Completed backup and relative rebase of backup images \n";
	}
	else
	{
		echo "No backing file - this must be the first backup in this set \n";
	}
}

?>

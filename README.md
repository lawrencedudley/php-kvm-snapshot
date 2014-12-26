php-kvm-snapshot
================

A script written (mostly) in PHP to take snapshot backups of virtual machines and store the previous snapshot somewhere else

Usage: 

backup.php vm-name

merge.php vm-name

The vm-name is what you'd find in the output of: 

virsh list --all

Backup will take your VM, make a snapshot and write all changes to that snapshot. It will then take the original file (the one you were running from before the snapshot) and write it to your backup directory (specified in the backup.php script).

Merge will take your VM, flatten backing files into the snapshot that's currently in use and delete any now useless snapshot files from the directory that the current one is in.
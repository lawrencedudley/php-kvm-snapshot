php-kvm-snapshot
================

A script written (mostly) in PHP to take snapshot backups of virtual machines and store the previous snapshot somewhere else

Usage: backup.php vm-name

The vm-name is what you'd find in the output of: 

virsh list --all
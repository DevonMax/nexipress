<?php
declare(strict_types=1);

return function (array $argv): int {

	echo "NexiPress CLI. v. 1.0.0\n";
	echo "Usage: php cli/nexi <command> [options]\n\n";
	echo "Commands:\n\n";

	echo "  doctor          Run a full diagnostic check on NexiPress\n";
	echo "                  Usage:\n";
	echo "                    php cli/nexi doctor\n";
	echo "                  What it does:\n";
	echo "                    - Runs ping (CLI responsiveness)\n";
	echo "                    - Runs check (environment & permissions)\n";
	echo "                    - Runs integrity (filesystem structure)\n";
	echo "                    - Prints a final summarized status\n";
	echo "                  Notes:\n";
	echo "                    - No changes are made to the system\n";
	echo "                    - Safe to run multiple times\n\n";

	echo "  envcheck:       Environment checks (php version / extension PHP / storage)\n\n";
	echo "  help:           Show this help\n\n";

	echo "  integrity:      Verify the integrity of the filesystem Nexipress\n";
	echo "                  Check that required files and folders are present\n";
	echo "                  and that the storage is writable. No changes are made.\n\n";

	echo "  logs            Show available log files and their status\n";
	echo "                  Usage:\n";
	echo "                    php cli/nexi logs\n";
	echo "                    php cli/nexi logs <file>\n";
	echo "                    php cli/nexi logs <file> <lines>\n";
	echo "                    php cli/nexi logs <file> --grep=ERROR|WARNING|SUCCESS\n";
	echo "                    php cli/nexi logs <file> --since=1h|30m|2d\n";
	echo "                  Notes:\n";
	echo "                    - Logs are CSV files\n";
	echo "                    - New entries are always appended at the end\n";
	echo "                    - --grep filters by second column (STATUS)\n";
	echo "                    - --since filters by first column (TIMESTAMP)\n";

	echo "  ping:           CLI start and responding test\n\n";

	return 0;
};

<?php
$bashScriptPath = dirname(__FILE__) . "/request_script.sh";
$infoPath = dirname(__FILE__) . "/info.txt"; 

$scriptLines = [
    "#!/bin/bash",
    "COUNT=\$(grep -c 'Успех' '$infoPath' 2>/dev/null || echo 0)",
    "if [ \"\$COUNT\" -lt 10 ]; then",  
    "    curl -s https://relef22.ru > /dev/null", 
    "    echo 'Успех/доне' >> '$infoPath'",
    "fi"
];

$scriptContent = implode("\n", $scriptLines);

file_put_contents($bashScriptPath, $scriptContent);

chmod($bashScriptPath, 0755);
$cron_line = "*/21 * * * * " . escapeshellcmd($bashScriptPath) . "\n";

exec("(crontab -l; echo " . escapeshellarg($cron_line) . ") | crontab -");

echo "Базовый скрипт создан и задача добавлена.\n";
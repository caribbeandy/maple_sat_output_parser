<?php

    // Input/output //
    $options = getopt("ds", ['in:','out:']);

    if ( empty($options['in']) || empty($options['out'])) {
        echo "Enter input and output args" . PHP_EOL;
        echo "e.g. php parser.php --in in.txt --out out.csv" . PHP_EOL;
        exit;
    }

    $dynamicOnly = false;

    if ( isset($options['d']) ) {
        $dynamicOnly = true;
    }

    $enumOut = false;

    if ( isset($options['s']) ) {
        $enumOut = true;
    }

    $fileToParse = $options['in'];
    $outputFile = $options['out'];

    $fileToParseArray = explode('============================[ Problem Statistics ]=============================', file_get_contents($fileToParse));

    /** Regexes */
    $fileNameRegex = '.*\/(.*cnf)';
    $restartRegex = 'restarts.* (\d+)';
    $conflictRegex = 'conflicts.* (\d+)';
    $decisionsRegex = 'decisions.* (\d+)';
    $propagationsRegex = 'propagations.* (\d+)';
    $conflictLiteralsRegex = 'conflict literals.* (\d+)';
    $cpuTimeRegex = 'CPU time.* (\d*\.*\d+)';
    
    $skipped = 0;
    $allProcessed = [];

    for($i=1; $i<count($fileToParseArray);$i+=2) {

        $newInstance = [];

        if ( isset($fileToParseArray[$i+1]) && preg_match ('/INDETERMINATE/', $fileToParseArray[$i]) ) {

            $match = [];

            $matched = preg_match("/$cpuTimeRegex/", $fileToParseArray[$i+1], $match);
            $newInstance['cpuTime'] = $match[1];

            if ( $enumOut ) {

                if ( preg_match ('/(INDETERMINATE|UNSATISFIABLE|SATISFIABLE)/', $fileToParseArray[$i+1], $match) ) {

                    unset($newInstance['cpuTime']);

                    $enumVal = null;

                    if ( $match[1] == "INDETERMINATE" ) {
                        $enumVal = 0;
                    } else if ( $match[1] = "UNSATISFIABLE" ) {
                        $enumVal = 1;
                    } else {
                        $enumVal = 2;
                    }

                    $newInstance['status'] = $enumVal; 
                }
            }

            $matched = preg_match("/$restartRegex/", $fileToParseArray[$i], $match);
            $newInstance['restarts'] = $match[1];

            $matched = preg_match("/$conflictRegex/", $fileToParseArray[$i], $match);
            $newInstance['conflicts'] = $match[1];

            $matched = preg_match("/$decisionsRegex/", $fileToParseArray[$i], $match);
            $newInstance['decisions'] = $match[1];

            $matched = preg_match("/$propagationsRegex/", $fileToParseArray[$i], $match);
            $newInstance['propagations'] = $match[1];

            $matched = preg_match("/$conflictLiteralsRegex/", $fileToParseArray[$i], $match);
            $newInstance['conflictLiterals'] = $match[1];

            if ( !$dynamicOnly ) {
                // Merge with SATZilla features
                $matched = preg_match("/$fileNameRegex/", $fileToParseArray[$i-1], $match);
                $fileName = $match[1];

                $output = shell_exec("cat sat_2016_agile_processed/{$fileName} | tail -2");

                $arr = explode("\n", $output);

                $headers = explode(",",$arr[0]);
                $vals = explode(",",$arr[1]);

                $mapping = [];

                foreach($headers as $key => $val) {
                    $mapping[$val] = $vals[$key];
                }

                $newInstance = array_merge($newInstance, $mapping);
            }

            //print_r($newInstance); exit;
        } else {
            $skipped++;
        }

        if (!empty($newInstance)) {
            //print_r($newInstance);
            $allProcessed[] = $newInstance;
        }
    }

    // Output stuff
    $fp = fopen($outputFile, 'w');
    fputcsv($fp, array_keys($allProcessed[0]));

    foreach ($allProcessed as $fields) {
        fputcsv($fp, $fields);
    }

    fclose($fp);

    // Stats
    echo "Total processed: " . count($allProcessed) . PHP_EOL;
    echo "Skipped: $skipped" . PHP_EOL;

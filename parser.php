<?php

    $fileToParse = file_get_contents("output3.txt");

    $fileToParseArray = explode('============================[ Problem Statistics ]=============================', $fileToParse);

    $fileNameRegex = '.*\/(.*cnf)';
    $restartRegex = 'restarts.* (\d+)';
    $conflictRegex = 'conflicts.* (\d+)';
    $decisionsRegex = 'decisions.* (\d+)';
    $propagationsRegex = 'propagations.* (\d+)';
    $conflictLiteralsRegex = 'conflict literals.* (\d+)';

    $cpuTimeRegex = 'CPU time.* (\d+\.\d+)';
    $skipped = 0;

    $allProcessed = [];

    for($i=1; $i<count($fileToParseArray);) {

        $newInstance = [];

        if ( preg_match ('/INDETERMINATE/', $fileToParseArray[$i]) ) {

            $match = [];

            $matched = preg_match("/$cpuTimeRegex/", $fileToParseArray[$i+1], $match);
            $newInstance['cpuTime'] = $match[1];

            if ( $i%2==1 ) {
                $matched = preg_match("/$fileNameRegex/", $fileToParseArray[$i-1], $match);
                $newInstance['fileName'] = $match[1];
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


            // Next index
            // ============= 
            /*
            if ( preg_match ('/(INDETERMINATE|UNSATISFIABLE|SATISFIABLE)/', $fileToParseArray[$i+1], $match) ) {
                $newInstance['status'] = $match[1]; 
            }
            */
        } else {
            $skipped++;
        }

        $i +=2;

        //print_r($newInstance);
        if (!empty($newInstance)) {
            $allProcessed[] = $newInstance;
        }
    //    exit;
    }

    $fp = fopen("output2.csv", 'w');
    fputcsv($fp, array_keys($allProcessed[0]));

    foreach ($allProcessed as $fields) {
        fputcsv($fp, $fields);
    }

    fclose($fp);

    print_r($allProcessed);

    echo "Total processed: " . count($allProcessed) . PHP_EOL;
    echo "Skipped: $skipped" . PHP_EOL;

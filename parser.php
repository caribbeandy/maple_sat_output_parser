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

            // Might be redundant, but whatever
            if ( $i%2==1 ) {
                $matched = preg_match("/$fileNameRegex/", $fileToParseArray[$i-1], $match);
                $fileName = $match[1];

                $output = shell_exec("/u4/a9palmer/maple_sat_output_parser/SAT-features-competition2012/featuresSAT12 ~/Agile/{$fileName} | tail -2");

                $arr = explode("\n", $output);

                $headers = explode(",",$arr[0]);
                $vals = explode(",",$arr[1]);

                $mapping = [];

                foreach($headers as $key => $val) {
                    $mapping[$val] = $vals[$key];
                }

                $newInstance = array_merge($newInstance, $mapping);
                //print_r($newInstance); exit;

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

        if (!empty($newInstance)) {
            print_r($newInstance);
            $allProcessed[] = $newInstance;
        }
    }

    $fp = fopen("test_3.csv", 'w');
    fputcsv($fp, array_keys($allProcessed[0]));

    foreach ($allProcessed as $fields) {
        fputcsv($fp, $fields);
    }

    fclose($fp);

    echo "Total processed: " . count($allProcessed) . PHP_EOL;
    echo "Skipped: $skipped" . PHP_EOL;

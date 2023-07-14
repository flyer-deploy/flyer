<?php

namespace Deployer;

use Symfony\Component\Yaml\Yaml;

task('deploy:logging', function() {
    if (get('logging') == NULL) {
        writeln("Logging config not found. Skipping.");
        return;
    }

    depends([
        'app_id',
        'release_path',
        'release_version',
        'promtail_config_file_path',
    ]);

    // if (!test($promtail_config_file_path)) {
    //     writeln("Invalid PROMTAIL_CONFIG_FILE_PATH. File not exist");
    //     return;
    // }


    $logging_config = get('logging');
    $promtail_config_file_path = get('promtail_config_file_path');
    $app_id = get('app_id');
    $release_path = get('release_path');
    $release_version = get('release_version');
    $promtail_config = Yaml::parseFile($promtail_config_file_path);

    $scrape_configs = $promtail_config['scrape_configs'] ?? [];
    $filtered_scrape_configs = array_filter($scrape_configs, function($config) use ($app_id) {
        return $config['job_name'] !== "flyer_app_logs_$app_id";
    });


    // Scrape Config Logic
    $static_conf = [];
    foreach ($logging_config['files'] as $item) {
        $included = "";
        $excluded = "";

        if (isset($item['file'])) {
            $included = $item['file'];
        }

        if (isset($item['exclude'])) {
            $excluded = $item['exclude'];
        }

        $label = [
            "labels" => [
                "__path__" => "$release_path/$included",
                "__path_exclude__" => "$release_path/$excluded",
                "app_id" => $app_id,
                "release_version" => $release_version,
            ]
        ];


        array_push($static_conf, $label);
    }

    $scrape_config = [
        "job_name" => "flyer_app_logs_$app_id",
        "pipeline_stages" => [
            0 => [
                "match" => [
                    "selector" => "app_id",
                    "stages" => [
                        0 => [
                            "regex" => [
                                "source" => "filename",
                                "expression" => "$release_path/(?P<filename>.+)"
                            ]
                        ],
                        1 => [
                            "labels" => [
                                "short_filename" => ""
                            ]
                        ]
                    ] 
                ]
            ]
        ],
        "static_configs" => $static_conf
    ];

    array_push($filtered_scrape_configs, $scrape_config);

    $promtail_config['scrape_configs'] = array_values($filtered_scrape_configs);

    $promtail_config_content = Yaml::dump($promtail_config, 10, 2);
    file_put_contents($promtail_config_file_path, $promtail_config_content);

    writeln("Logging config applied successfully.");
});